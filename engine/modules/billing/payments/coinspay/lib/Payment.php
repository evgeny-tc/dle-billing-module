<?php

namespace CoinsPay\Dle;

use RuntimeException;

/**
 * Paying a DLE-Billing invoice: makes or reuses a CoinsPay invoice for what is still missing and
 * sends the user to its payment page. DLE-Billing calls the payment system when the user picks it
 * on the invoice page (Form()).
 *
 * What is missing is the price less what earlier CoinsPay invoices of the same invoice brought (a
 * part of an expired one waits on the balance). For a service it counts only while it is still on
 * the balance.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Payment
{
    /** A closed invoice is polled one more day for a late payment. */
    public const POLL_AFTER_CLOSE = 86400;

    /** "Pay" pressed again within this many seconds reuses the open invoice without asking the API. */
    public const REUSE = 10;

    /** Currency of the CoinsPay invoice being made, for the error message. */
    private string $currency = '';

    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * @param int    $dleInvoice  DLE-Billing invoice.
     * @param array  $passed      The invoice as DLE-Billing passes it to Form(): invoice_get is
     *                            the price in units of the balance (less a coupon), invoice_pay
     *                            what to pay in the currency of the payment system.
     * @param string $description Description DLE-Billing gives the payment.
     * @return array {redirect}, {done: a message} or {error: a message}.
     */
    public function start(int $dleInvoice, array $passed, string $description): array
    {
        $host = $this->module->host();
        $settings = $this->module->settings();
        $lang = Text::code($host->userLanguage());
        $text = new Text($lang);

        if (!$settings->active() || $settings->apiKey() === '') {
            return $this->unavailable($text, $dleInvoice, 'the payment system is off or has no API key');
        }
        // A test key pays without money: only an administrator may use it.
        if ($settings->isTestMode() && !$host->isAdmin()) {
            return $this->unavailable($text, $dleInvoice, 'a test key, and the user is not an admin');
        }
        $user = $host->currentUser();
        if ($user === null) {
            return $this->unavailable($text, $dleInvoice, 'nobody is signed in');
        }

        $repo = $this->module->repo();
        if (!$repo->ensure()) {
            return $this->unavailable($text, $dleInvoice, 'the tables of the module are missing');
        }
        $site = $host->siteKey();
        if (!$repo->lock($site, $dleInvoice)) {
            return ['error' => $text->t('error_busy')];
        }
        try {
            return $this->startLocked($dleInvoice, $passed, $description, $user, $lang, $text);
        } catch (ApiError $e) {
            $host->log('error', 'invoice #' . $dleInvoice . ': ' . $e->summary());
            $key = $e->errorCode() === 'CURRENCY_NOT_SUPPORTED' ? 'error_currency' : 'error_api';

            return ['error' => $text->t($key, $this->currency)];
        } catch (RuntimeException $e) {
            $host->log('error', 'invoice #' . $dleInvoice . ': ' . $e->getMessage());

            return ['error' => $text->t('error_busy')];
        } finally {
            $repo->unlock($site, $dleInvoice);
        }
    }

    private function startLocked(int $dleInvoice, array $passed, string $description, array $user, string $lang, Text $text): array
    {
        $host = $this->module->host();
        $repo = $this->module->repo();
        $settings = $this->module->settings();
        $done = ['done' => $text->t('done_paid')];

        // The invoice now, not as the page saw it: paid or deleted meanwhile — nothing to pay.
        $dle = $host->invoice($dleInvoice);
        if (!$dle || $dle['paid']) {
            return $done;
        }
        if ((string) $dle['user'] !== (string) $user['name']) {
            return $this->unavailable($text, $dleInvoice, 'the invoice is of another user');
        }
        $cost = $this->cost($dle, $passed);
        if (!Util::positive($cost)) {
            return $this->unavailable($text, $dleInvoice, 'nothing to pay: ' . $cost . ' to the balance');
        }
        $currency = $this->currency = $settings->currency();
        $scale = min(Decision::SCALE, $this->module->currencies()->scale($currency));

        $covered = $this->covered($dle, (int) $user['id']);
        $due = $covered === null ? null : Util::left($cost, $covered, Decision::SCALE);
        if ($due === null) {
            return $this->unavailable($text, $dleInvoice, 'the user is gone');
        }
        if (!Util::positive($due)) {
            // What came covers it: closed from the balance, no CoinsPay invoice needed.
            return $this->closeNow($dle) ? $done : ['error' => $text->t('error_busy')];
        }
        $amount = $this->amount($due, $scale);
        if (!Util::positive($amount)) {
            return $this->unavailable($text, $dleInvoice, 'the amount rounds to zero');
        }
        $test = $settings->isTestMode();
        $admin = $host->isAdmin();
        $client = $this->module->client();

        $current = $repo->current($dleInvoice);
        // A test invoice of an admin is not handed to a user: its payment would be added.
        if ($current && $current['status'] === 'open' && Util::compare((string) $current['due'], $due, Decision::SCALE) === 0
            && (string) $current['amount'] === $amount && $current['currency'] === $currency
            && (bool) $current['test'] === $test && (!$test || (bool) $current['test_admin'] === $admin)
            && (int) $current['user_id'] === (int) $user['id'] && Sync::same($dle, $current) !== null) {
            // Pressed again within seconds: the page it was given, without asking the API each time.
            if ((string) $current['pay_url'] !== '' && !$repo->takeTurn('start_' . $dleInvoice, self::REUSE)) {
                return ['redirect' => self::payPage(['pay_url' => $current['pay_url']], $lang)];
            }
            try {
                $invoice = $client->invoice($current['invoice_id']);
                if (Util::value($invoice, 'status') === 'open') {
                    return ['redirect' => self::payPage($invoice, $lang)];
                }
                // Paid or closed meanwhile: its state goes to DLE-Billing, and a new invoice is made if still due.
                $this->module->sync()->apply($invoice);
                $dle = $host->invoice($dleInvoice);
                if (!$dle || $dle['paid']) {
                    return $done;
                }
                $covered = $this->covered($dle, (int) $user['id']);
                $due = $covered === null ? null : Util::left($cost, $covered, Decision::SCALE);
                if ($due === null || !Util::positive($due)) {
                    return $done;
                }
                $amount = $this->amount($due, $scale);
            } catch (ApiError $e) {
                if (!$e->isNotFound()) {
                    throw $e;
                }
            }
        }

        $attempt = 1;
        foreach ($repo->forInvoice($dleInvoice) as $row) {
            $attempt = max($attempt, (int) $row['attempt'] + 1);
        }
        $body = InvoiceRequest::body(
            [
                'reference' => (string) $dleInvoice,
                'currency' => $currency,
                'amount' => $amount,
                'description' => $description !== '' ? $description : $text->t('invoice_description', $dleInvoice),
                'return_url' => $host->invoiceUrl($dleInvoice),
            ],
            $settings
        );
        $invoice = $client->createInvoice($body, InvoiceRequest::idempotencyKey($host->siteKey(), $dleInvoice, $attempt, $body));
        if (Util::value($invoice, 'status') !== 'open') {
            // The key met an invoice made earlier and closed since — the tables were wiped, say. One more attempt.
            $attempt++;
            $invoice = $client->createInvoice($body, InvoiceRequest::idempotencyKey($host->siteKey(), $dleInvoice, $attempt, $body));
            if (Util::value($invoice, 'status') !== 'open') {
                // Never adopt an old invoice: its money belongs to another payment.
                throw new ApiError(0, 'INVOICE_NOT_OPEN', 'The idempotency key met closed invoices twice');
            }
        }

        $invoiceId = ApiClient::id(Util::value($invoice, 'id'));
        $page = self::payPage($invoice, $lang);
        if (!$repo->find($invoiceId)) {
            $repo->add([
                'invoice_id' => $invoiceId,
                'dle_invoice' => $dleInvoice,
                'dle_created' => (int) ($dle['created'] ?? 0),
                'user_id' => (int) $user['id'],
                'user_name' => (string) $user['name'],
                'service' => (string) $dle['handler'] !== '' ? 1 : 0,
                'attempt' => $attempt,
                'amount' => $amount,
                'currency' => $currency,
                'due' => $due,
                'cost' => $cost,
                'test' => !empty($invoice['test']) ? 1 : 0,
                'test_admin' => $admin ? 1 : 0,
                'status' => Util::value($invoice, 'status'),
                'pay_url' => Util::value($invoice, 'pay_url'),
                'snapshot' => Util::json(Sync::snapshot($invoice)),
                'next_poll' => time() + Sync::POLL_OPEN,
                'poll_until' => time() + $settings->lifetime() * 60 + self::POLL_AFTER_CLOSE,
            ]);
            $this->module->debug(sprintf('invoice #%d: CoinsPay invoice %s for %s %s', $dleInvoice, $invoiceId, $amount, $currency));
            // "Pay" pressed again within seconds gets this page without asking the API.
            $repo->takeTurn('start_' . $dleInvoice, self::REUSE);
        }

        $this->cancelReplaced($dleInvoice, $invoiceId);
        // An older CoinsPay invoice found paid while cancelling may have paid it meanwhile.
        $now = $host->invoice($dleInvoice);
        if ($now === null || $now['paid']) {
            return $done;
        }

        return ['redirect' => $page];
    }

    /**
     * An amount as DLE-Billing formats it (Balance::Convert): with a space between thousands when
     * a "format" is set, "1 200.00".
     */
    private static function number(mixed $value): string
    {
        $value = preg_replace('/[\s\x{00A0}]+/u', '', is_scalar($value) ? (string) $value : '0');

        return is_string($value) && is_numeric($value) ? $value : '0';
    }

    /** "Not available" for the user; why, in the debug log. */
    private function unavailable(Text $text, int $dleInvoice, string $why): array
    {
        $this->module->debug('invoice #' . $dleInvoice . ': CoinsPay not offered: ' . $why);

        return ['error' => $text->t('error_unavailable')];
    }

    /**
     * What earlier CoinsPay invoices of an invoice brought for its user and still counts: all of
     * it for a top-up; for a service no more than the balance holds now.
     *
     * @return string|null Null when the user is gone.
     */
    public function covered(array $dle, int $userId): ?string
    {
        $host = $this->module->host();
        $user = $host->user($userId);
        if ($user === null) {
            return null;
        }
        $brought = '0';
        foreach ($this->module->repo()->forInvoice((int) $dle['id']) as $row) {
            if ((int) $row['user_id'] === $userId && Sync::same($dle, $row) !== null) {
                $brought = Util::add($brought, $host->credited((int) $row['id']), Decision::SCALE);
            }
        }
        if ((string) $dle['handler'] !== '') {
            $balance = Util::positive((string) $user['balance']) ? Util::formatAmount((string) $user['balance'], Decision::SCALE) : '0';
            $brought = Util::min($brought, $balance, Decision::SCALE);
        }

        return $brought;
    }

    /**
     * What the CoinsPay invoice asks for $due units of the balance: by the rate of the payment
     * system ("convert"; none — 1 to 1, as DLE-Billing counts), rounded up to the decimals CoinsPay
     * takes for the currency. Not the amount DLE-Billing passes: it rounds that by the format of the
     * balance, to whole units with "int", and a rounded-down amount would buy the whole price.
     */
    private function amount(string $due, int $scale): string
    {
        $rate = self::number($this->module->settings()->get('convert'));
        $rate = Util::positive($rate) ? $rate : '1';
        $factor = 10 ** max(0, $scale);

        return Util::formatAmount(ceil((float) $due * (float) $rate * $factor - 1e-6) / $factor, $scale);
    }

    /**
     * The price of an invoice in units of the balance: as DLE-Billing keeps it. A lower price on the
     * page is a coupon; it counts only once DLE-Billing used the coupon for this invoice and wrote
     * the new amount to pay into it — not when the coupon was taken by another invoice meanwhile.
     */
    private function cost(array $dle, array $passed): string
    {
        $cost = Util::formatAmount((string) $dle['get'], Decision::SCALE);
        $shown = Util::formatAmount(self::number($passed['invoice_get'] ?? '0'), Decision::SCALE);
        $toPay = self::number($passed['invoice_pay'] ?? '0');
        if ((string) $dle['handler'] !== '' && Util::positive($shown) && Util::compare($shown, $cost, Decision::SCALE) < 0
            && Util::positive($toPay) && Util::compare($toPay, self::number($dle['pay']), Decision::SCALE) === 0) {
            return $shown;
        }

        return $cost;
    }

    /** Closes an invoice that what came covers already. */
    private function closeNow(array $dle): bool
    {
        $row = $this->module->repo()->current((int) $dle['id']);
        if ($row === null) {
            return false;
        }
        [$paid] = $this->module->sync()->close($dle, $row);
        $now = $this->module->host()->invoice((int) $dle['id']);

        return $paid || ($now !== null && $now['paid']);
    }

    /**
     * The payment page in the user's language. Only an http(s) address goes to the browser.
     *
     * @throws ApiError BAD_RESPONSE for anything else.
     */
    private static function payPage(array $invoice, string $lang): string
    {
        $url = Util::value($invoice, 'pay_url');
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['https', 'http'], true) || (string) parse_url($url, PHP_URL_HOST) === '') {
            throw new ApiError(0, 'BAD_RESPONSE', 'The payment page address is not a web address');
        }

        return self::withLang($url, $lang);
    }

    /**
     * Cancels the open CoinsPay invoices of a DLE-Billing invoice that hold no money: replaced by
     * a new one, or not wanted any more — the invoice was paid otherwise or deleted. CoinsPay
     * cancels an open invoice even with a payment on its way, so each one is read fresh first;
     * what still lands in the gap is decided as for any closed invoice.
     *
     * @param string $keep CoinsPay invoice to leave open; '' cancels all.
     */
    public function cancelReplaced(int $dleInvoice, string $keep = ''): void
    {
        foreach ($this->module->repo()->forInvoice($dleInvoice) as $row) {
            if ($row['invoice_id'] !== $keep) {
                $this->cancelRow($row);
            }
        }
    }

    /** Cancels one open CoinsPay invoice if it holds no money, and applies its state. */
    public function cancelRow(array $row): void
    {
        if ($row['status'] !== 'open') {
            return;
        }
        $dleInvoice = (int) $row['dle_invoice'];
        try {
            $invoice = $this->module->client()->invoice($row['invoice_id']);
            $hasMoney = Util::positive(Util::value($invoice, 'amount_paid')) || Util::positive(Util::value($invoice, 'amount_pending'));
            if (Util::value($invoice, 'status') === 'open' && !$hasMoney) {
                $invoice = $this->module->client()->cancelInvoice($row['invoice_id']);
            }
            $this->module->sync()->apply($invoice);
        } catch (ApiError $e) {
            // Money came meanwhile (INVOICE_NOT_OPEN) or the API is away: its real state comes with polling.
            $this->module->host()->log(
                in_array($e->status(), [404, 409], true) ? 'info' : 'error',
                sprintf('invoice #%d: CoinsPay invoice %s not cancelled: %s', $dleInvoice, $row['invoice_id'], $e->summary())
            );
        } catch (RuntimeException $e) {
            // Busy, or money DLE-Billing did not add: the next webhook or poll applies it.
            $this->module->host()->log('error', sprintf('invoice #%d: CoinsPay invoice %s: %s', $dleInvoice, $row['invoice_id'], $e->getMessage()));
        }
    }

    /** The payment page in the user's language. */
    public static function withLang(string $payUrl, string $lang): string
    {
        if ($payUrl === '') {
            return '';
        }

        return $payUrl . (str_contains($payUrl, '?') ? '&' : '?') . 'lang=' . ($lang === 'ru' ? 'ru' : 'en');
    }
}
