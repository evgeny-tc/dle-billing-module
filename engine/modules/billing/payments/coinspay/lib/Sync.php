<?php

namespace CoinsPay\Dle;

use RuntimeException;
use Throwable;

/**
 * Applies the state of a CoinsPay invoice to its DLE-Billing invoice: lock → fresh invoice →
 * decision → money on the balance of the user → the invoice closed from it. From the FOSSBilling
 * module (Sync.php).
 *
 * The named lock of the invoice, the claim of the row and the balance history of DLE-Billing
 * (plugin "coinspay", the row id) keep a payment from being added twice: before adding anything
 * the module reads what it added already.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Sync
{
    /** Open invoices are polled every 2 minutes at most, closed ones every 30 minutes. */
    public const POLL_OPEN = 120;

    public const POLL_CLOSED = 1800;

    /** A claim older than this belongs to a request that died: see Repo::claim(). */
    public const CLAIM_STALE = 600;

    /** Code of the exception when another request holds the payment: no failure to report. */
    public const BUSY = 409;

    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * @param array $invoice CoinsPay invoice from the API.
     * @return string What happened, for the log and the webhook answer.
     *
     * @throws RuntimeException The DLE-Billing invoice is busy, or the money did not get added;
     *                          the caller retries later.
     */
    public function apply(array $invoice): string
    {
        $repo = $this->module->repo();
        $row = $repo->find(Util::value($invoice, 'id'));
        if (!$row) {
            return 'not an invoice of this site';
        }
        $dleInvoice = (int) $row['dle_invoice'];
        $site = $this->module->host()->siteKey();
        if (!$repo->lock($site, $dleInvoice)) {
            throw new RuntimeException('the invoice is busy', self::BUSY);
        }
        try {
            $result = $this->applyLocked($row['invoice_id'], $invoice);
        } finally {
            $repo->unlock($site, $dleInvoice);
        }
        if ($result[1]) {
            // The invoice got paid: other open CoinsPay invoices of it are not wanted any more.
            $this->module->payment()->cancelReplaced($dleInvoice, $row['invoice_id']);
        }

        return $result[0];
    }

    /** @return array {0: what happened, 1: whether the DLE-Billing invoice got paid by it} */
    private function applyLocked(string $invoiceId, array $invoice): array
    {
        $host = $this->module->host();
        $repo = $this->module->repo();
        $text = $this->module->adminText();
        // Fresh copies: another request may have changed both while we waited for the lock.
        $row = $repo->find($invoiceId);
        if (!$row) {
            return ['not an invoice of this site', false];
        }

        $status = Util::value($invoice, 'status');
        $repo->update($invoiceId, ['status' => $status, 'snapshot' => Util::json(self::snapshot($invoice))]);
        $fingerprint = Decision::fingerprint($invoice);
        // A payment interrupted halfway is never "no change": it is checked again below.
        if ($row['fingerprint'] === $fingerprint && $row['apply_state'] !== 'applying') {
            $this->settle($invoiceId, $status, $row);

            return ['no change', false];
        }

        $dleInvoice = (int) $row['dle_invoice'];
        $dle = self::same($host->invoice($dleInvoice), $row);
        $user = $host->user((int) $row['user_id']);
        $decision = Decision::decide(
            $invoice,
            [
                'status' => $dle === null ? 'deleted' : ($dle['paid'] ? 'paid' : 'unpaid'),
                'requested' => $row['due'],
                'credited' => $user ? $host->credited((int) $row['id']) : '0',
                // The invoice was deleted and its id given to an invoice of another user.
                'user_changed' => $dle !== null && (string) $dle['user'] !== (string) $row['user_name'],
                'user_missing' => $user === null,
                'test_admin' => !empty($row['test_admin']),
                'money' => fn (string $amount): string => $host->money($amount),
            ],
            $text
        );
        $action = $decision['action'];
        $note = $decision['note'];
        $attention = !empty($decision['attention']);
        $paidNow = false;
        // Closing the invoice failed: the next webhook or poll must try again.
        $retry = false;

        if ($action === Decision::CREDIT) {
            $comment = $text->t('balance_comment', Util::value($invoice, 'number'), $dleInvoice);
            try {
                $this->credit($row, $decision['amount'], $comment);
            } catch (RuntimeException $e) {
                // Received, not on the balance: the admin sees it until a retry adds it.
                if ($e->getCode() !== self::BUSY) {
                    $repo->update($invoiceId, ['problem' => $text->t('log_not_added', Util::value($invoice, 'number'), $host->money($decision['amount']), $e->getMessage())]);
                }

                throw $e;
            }
        }
        if (($action === Decision::CREDIT || $action === Decision::PAY) && $decision['pay']) {
            // The invoice now, not as the decision saw it: paid or deleted meanwhile, it is left alone.
            $now = self::same($host->invoice($dleInvoice), $row);
            if ($now !== null && !$now['paid']) {
                [$paidNow, $retry, $why] = $this->close($now, $row);
                if ($why !== '') {
                    $note = trim($note . ' ' . $why);
                    $attention = true;
                }
            } elseif ($action === Decision::CREDIT) {
                $note .= ' ' . $text->t($now === null ? 'log_invoice_gone' : 'log_invoice_paid');
                $attention = true;
            }
        }

        // Before the log: a log the web server cannot write must not lose the note for the admin.
        if ($attention) {
            $repo->update($invoiceId, ['attention' => $note]);
        }
        if ($note !== '') {
            $host->log($action === Decision::HOLD || $attention ? 'warning' : 'info', sprintf('#%d: %s', $dleInvoice, $note));
        }

        // Only after the action went through: had it failed, the next webhook or poll must decide
        // again, not see "no change" and lose the payment.
        if ($retry) {
            $repo->update($invoiceId, [
                'problem' => $text->t('log_not_closed', Util::value($invoice, 'number'), $dleInvoice),
                'next_poll' => time() + self::POLL_OPEN < (int) $row['poll_until'] ? time() + self::POLL_OPEN : null,
            ]);
        } else {
            // A held payment is decided anew each time: "check" in the panel adds it once the
            // admin removed the cause.
            $repo->update($invoiceId, ['fingerprint' => $action === Decision::HOLD ? null : $fingerprint, 'problem' => null]);
            $this->settle($invoiceId, $status, $row);
        }
        $this->module->debug(sprintf('invoice #%d, CoinsPay %s (%s): %s', $dleInvoice, $invoiceId, $status, $action));

        return [$action . ($note !== '' ? ': ' . $note : ''), $paidNow];
    }

    /**
     * Closes an unpaid DLE-Billing invoice with what its CoinsPay invoices brought, when that
     * covers it. Called with the lock of the invoice held.
     *
     * @param array $dle The invoice, as Host::invoice() gives it.
     * @param array $row The CoinsPay invoice that came now.
     * @return array {0: whether it is paid now, 1: whether closing failed, 2: a note for the admin}
     */
    public function close(array $dle, array $row): array
    {
        $host = $this->module->host();
        $userId = (int) $row['user_id'];
        $rows = array_values(array_filter(
            $this->module->repo()->forInvoice((int) $dle['id']),
            fn (array $other): bool => (int) $other['user_id'] === $userId && self::same($dle, $other) !== null
        ));
        if (!$rows || (string) $dle['user'] !== (string) $row['user_name'] || self::same($dle, $row) === null) {
            return [false, false, ''];
        }
        $brought = '0';
        foreach ($rows as $other) {
            $brought = Util::add($brought, $host->credited((int) $other['id']), Decision::SCALE);
        }

        if ((string) $dle['handler'] === '') {
            // A top-up is the balance itself: paid once its CoinsPay invoices brought its amount.
            if (Util::compare($brought, (string) $dle['get'], Decision::SCALE) < 0) {
                return [false, false, ''];
            }

            return $this->guarded(fn (): bool => $host->markPaid((int) $dle['id']));
        }

        // A service: paid from the balance by its price as the user saw it (the newest CoinsPay
        // invoice of it keeps it, less a coupon), only with what its CoinsPay invoices brought.
        $cost = (string) ($rows[0]['cost'] !== '' ? $rows[0]['cost'] : $dle['get']);
        if (Util::compare($brought, $cost, Decision::SCALE) < 0) {
            return [false, false, ''];
        }
        $user = $host->user($userId);
        if ($user === null) {
            return [false, false, ''];
        }
        if (Util::compare((string) $user['balance'], $cost, Decision::SCALE) < 0) {
            // Spent meanwhile: the user pays the rest; the admin sees why the invoice waits.
            return [false, false, $this->module->adminText()->t('log_balance_short', (int) $dle['id'], $host->money($cost))];
        }

        $result = $this->guarded(fn (): bool => $host->payFromBalance($dle, $userId, $cost));
        if (!$result[0] && !$result[1]) {
            // The balance went below the price between the check and the payment.
            $result[2] = $this->module->adminText()->t('log_balance_short', (int) $dle['id'], $host->money($cost));
        }

        return $result;
    }

    /**
     * Brings the money of a CoinsPay invoice on the balance up to $target, exactly once: under
     * the claim of the row, after reading what the balance history holds for it already.
     *
     * @throws RuntimeException Another request adds it now, or it did not get added.
     */
    private function credit(array $row, string $target, string $comment): void
    {
        $host = $this->module->host();
        $repo = $this->module->repo();
        $rowId = (int) $row['id'];
        if (!$repo->claim($row['invoice_id'], self::CLAIM_STALE)) {
            throw new RuntimeException('the payment of invoice #' . $row['dle_invoice'] . ' is being added by another request', self::BUSY);
        }
        $done = false;
        try {
            // The request that held the claim before may have added it and died.
            $credited = $host->credited($rowId);
            if (Util::compare($target, $credited, Decision::SCALE) > 0) {
                $missing = Util::left($target, $credited, Decision::SCALE);
                try {
                    $host->addFunds((int) $row['user_id'], $missing, $comment, $rowId);
                } catch (Throwable $e) {
                    // Whether it got added is read below.
                    $host->log('error', 'adding to the balance: ' . $e->getMessage());
                }
            }
            $done = Util::compare($host->credited($rowId), $target, Decision::SCALE) >= 0;
        } finally {
            $repo->update($row['invoice_id'], ['apply_state' => $done ? 'applied' : 'none', 'claimed' => null]);
        }
        if (!$done) {
            throw new RuntimeException('DLE-Billing did not add the payment of invoice #' . $row['dle_invoice'] . ' to the balance');
        }
    }

    /**
     * Runs a write to DLE-Billing. A failure is left to the next webhook or poll.
     *
     * @return array {0: whether the invoice is paid now, 1: whether it failed, 2: ''}
     */
    private function guarded(callable $write): array
    {
        try {
            return [(bool) $write(), false, ''];
        } catch (Throwable $e) {
            $this->module->host()->log('error', 'closing the invoice: ' . $e->getMessage());

            return [false, true, ''];
        }
    }

    /**
     * Polling of the invoice after this state: open — every 2 minutes, paid — never, expired or
     * cancelled — every 30 minutes until the end of polling: a late payment may come.
     */
    private function settle(string $invoiceId, string $status, array $row): void
    {
        $next = match ($status) {
            'open' => time() + self::POLL_OPEN,
            'paid' => null,
            default => time() + self::POLL_CLOSED,
        };
        if ($next !== null && $next >= (int) $row['poll_until']) {
            $next = null;
        }
        $this->module->repo()->update($invoiceId, ['next_poll' => $next]);
    }

    /**
     * The DLE-Billing invoice a row of the module was made for, or null when its id now belongs to
     * another invoice: DLE-Billing deletes old unpaid invoices, and a restart of MySQL 5.7 may give
     * the id out again. Rows made before the time was kept (0) match any.
     */
    public static function same(?array $dle, array $row): ?array
    {
        if ($dle === null) {
            return null;
        }
        $made = (int) ($row['dle_created'] ?? 0);

        return $made > 0 && (int) ($dle['created'] ?? 0) !== $made ? null : $dle;
    }

    /** What the module keeps of an invoice: for the panel and the next decision. */
    public static function snapshot(array $invoice): array
    {
        $copy = [];
        foreach (['id', 'number', 'status', 'test', 'currency', 'amount', 'amount_paid', 'amount_pending', 'amount_due', 'amount_overpaid', 'pay_url', 'expires_at', 'paid_late', 'paid_manually'] as $field) {
            $copy[$field] = $invoice[$field] ?? null;
        }

        return $copy;
    }
}
