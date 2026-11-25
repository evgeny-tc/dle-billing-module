<?php

namespace CoinsPay\Dle;

/**
 * What a state of a CoinsPay invoice means for its DLE-Billing invoice. A pure function: unit
 * tests check every rule without DLE. From the FOSSBilling module (Decision.php).
 *
 * What a CoinsPay invoice brought goes to the balance of the user, in units of the balance, and
 * the DLE-Billing invoice is closed from it (a top-up is the balance itself; a service is paid
 * from the balance, as DLE-Billing does when the user pays with the balance):
 * - paid: what it was made for, and more when the user paid more;
 * - expired or cancelled with confirmed money: the part received — the user pays the rest with a
 *   new CoinsPay invoice, which asks only what is still missing;
 * - open: nothing yet, the user may still pay the rest on the same page.
 * The amount only grows: a late payment of an expired invoice raises it to the whole. Money for
 * an invoice that was deleted or paid otherwise meanwhile stays on the balance, and the admin is
 * told.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Decision
{
    /** Bring the amount on the balance up to this total for the CoinsPay invoice. */
    public const CREDIT = 'credit';

    /** Nothing new to add: close the DLE-Billing invoice, if what came covers it now. */
    public const PAY = 'pay';

    /** Money came, but it needs a decision of the admin: nothing is added. */
    public const HOLD = 'hold';

    /** Only a line of the log. */
    public const LOG = 'log';

    public const NONE = 'none';

    /** DLE-Billing keeps balances with 2 decimals. */
    public const SCALE = 2;

    /**
     * @param array $invoice CoinsPay invoice from the API.
     * @param array $state   The DLE-Billing invoice now and what the module knows:
     *                       status — unpaid, paid or deleted;
     *                       requested — what the CoinsPay invoice stands for on it, in units of
     *                       the balance;
     *                       credited — what is on the balance for it already;
     *                       user_changed, user_missing, test_admin;
     *                       money — formats an amount of the balance for notes (optional).
     * @param Text  $text    Language of the admin.
     * @return array {action, amount, pay, note, attention}: amount — the total for the CoinsPay
     *               invoice on the balance; pay — whether to close the invoice from it after;
     *               attention — the note goes to the admin.
     */
    public static function decide(array $invoice, array $state, Text $text): array
    {
        $status = Util::value($invoice, 'status');
        $number = Util::value($invoice, 'number');
        $currency = Util::value($invoice, 'currency');
        $paid = Util::value($invoice, 'amount_paid');
        $pending = Util::value($invoice, 'amount_pending');
        $total = Util::value($invoice, 'amount');
        $requested = (string) $state['requested'];
        $credited = (string) ($state['credited'] ?? '');
        $credited = $credited === '' ? '0' : $credited;
        $dleStatus = (string) $state['status'];
        $money = isset($state['money']) && is_callable($state['money'])
            ? $state['money']
            : static fn (string $amount): string => $amount;
        $test = !empty($invoice['test']);
        $hasMoney = Util::positive($paid) || Util::positive($pending);
        $closed = $status === 'expired' || $status === 'cancelled';

        // All of what came, in the share of the invoice: more than requested when overpaid.
        $received = Util::share($paid, $requested, $total, self::SCALE, false);
        if ($status === 'paid') {
            // Paid in full, perhaps marked so by hand in the dashboard: at least what was asked.
            $target = Util::compare($received, $requested, self::SCALE) > 0 ? $received : Util::formatAmount($requested, self::SCALE);
        } elseif ($closed) {
            $target = $received;
        } else {
            $target = '0';
        }
        $grows = Util::positive($target) && Util::compare($target, $credited, self::SCALE) > 0;

        // A test payment moves no money: only an invoice an admin opened gets it.
        if ($test && $grows && empty($state['test_admin'])) {
            return self::result(self::HOLD, '', false, $text->t('log_test_foreign', $number), true);
        }

        if ($grows) {
            $amount = $money($target);
            if (!empty($state['user_missing'])) {
                return self::result(self::HOLD, '', false, $text->t('log_user_gone', $number, $paid . ' ' . $currency), true);
            }
            if (!empty($state['user_changed'])) {
                return self::result(self::HOLD, '', false, $text->t('log_user_changed', $number, $paid . ' ' . $currency), true);
            }
            if ($status === 'paid') {
                $note = $text->t('log_paid', $number, $paid . ' ' . $currency, self::payments($invoice, $text), $amount);
                if (!empty($invoice['paid_manually'])) {
                    $note .= ' ' . $text->t('log_paid_manually');
                }
                if (!empty($invoice['paid_late'])) {
                    $note .= ' ' . $text->t('log_paid_late');
                }
            } else {
                $note = $text->t($status === 'expired' ? 'log_expired_credited' : 'log_cancelled_credited', $number, $paid . ' ' . $currency, $total . ' ' . $currency, $amount);
            }
            $attention = false;
            if (Util::compare($target, $requested, self::SCALE) > 0) {
                $surplus = Util::formatAmount((float) $target - (float) $requested, self::SCALE);
                $note .= ' ' . $text->t('log_overpaid', $money($surplus));
                $attention = true;
            }
            // Deleted or paid otherwise meanwhile: the money only goes to the balance.
            if ($dleStatus !== 'unpaid') {
                $note .= ' ' . $text->t($dleStatus === 'deleted' ? 'log_invoice_gone' : 'log_invoice_paid');

                return self::result(self::CREDIT, $target, false, $note, true);
            }

            return self::result(self::CREDIT, $target, true, $note, $attention);
        }

        if ($status === 'open' && $hasMoney) {
            $note = Util::positive($paid)
                ? $text->t('log_partial', $number, $paid . ' ' . $currency, Util::value($invoice, 'amount_due') . ' ' . $currency)
                : $text->t('log_pending', $number, self::payments($invoice, $text));

            return self::result(self::LOG, '', false, $note);
        }

        if ($status === 'paid' || $closed) {
            if ($closed && Util::positive($pending)) {
                // Money still on its way: counted when confirmed, polling goes on.
                return self::result(self::LOG, '', false, $text->t('log_closed_pending', $number, self::payments($invoice, $text)));
            }
            if (Util::positive($credited) && $dleStatus === 'unpaid') {
                // On the balance already: closing the invoice failed before, or what came did not
                // cover it then.
                return self::result(self::PAY, '', true, '');
            }
            if ($closed && !Util::positive($paid)) {
                return self::result(self::LOG, '', false, $text->t($status === 'expired' ? 'log_expired' : 'log_cancelled', $number));
            }
        }

        return self::result(self::NONE, '', false, '');
    }

    /** What of the invoice changes the decision: equal for repeated webhooks of one state. */
    public static function fingerprint(array $invoice): string
    {
        $parts = [];
        foreach (['status', 'amount_paid', 'amount_pending', 'paid_late', 'paid_manually'] as $field) {
            $parts[] = isset($invoice[$field]) ? Util::json($invoice[$field]) : 'null';
        }
        if (!empty($invoice['payments']) && is_array($invoice['payments'])) {
            foreach ($invoice['payments'] as $payment) {
                $parts[] = Util::value($payment, 'deposit_id') . ':' . Util::value($payment, 'status');
            }
        }

        return md5(implode('|', $parts));
    }

    /** Payments in one line: "49.9 USDT (tron, abc…123)". */
    public static function payments(array $invoice, Text $text): string
    {
        $lines = [];
        if (!empty($invoice['payments']) && is_array($invoice['payments'])) {
            foreach ($invoice['payments'] as $payment) {
                $hash = Util::value($payment, 'tx_hash');
                $lines[] = sprintf(
                    '%s %s (%s, %s)',
                    Util::value($payment, 'amount'),
                    Util::value($payment, 'asset'),
                    Util::value($payment, 'chain'),
                    strlen($hash) > 16 ? substr($hash, 0, 8) . "\u{2026}" . substr($hash, -6) : $hash
                );
            }
        }

        return $lines ? implode(', ', $lines) : $text->t('log_no_payments');
    }

    private static function result(string $action, string $amount, bool $pay, string $note, bool $attention = false): array
    {
        return ['action' => $action, 'amount' => $amount, 'pay' => $pay, 'note' => $note, 'attention' => $attention];
    }
}
