<?php

namespace CoinsPay\Dle;

/**
 * Body of POST /invoices for a DLE-Billing invoice, and its idempotency key. From the FOSSBilling
 * module (InvoiceRequest.php).
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class InvoiceRequest
{
    public const MAX_DESCRIPTION = 1000;

    public const MAX_REFERENCE = 190;

    public const MAX_RETURN_URL = 500;

    /**
     * @param array $invoice {reference, currency, amount, description, return_url}.
     */
    public static function body(array $invoice, Settings $settings): array
    {
        // No title: the payment page then names the invoice "Order <reference>" in the buyer's language.
        $body = [
            'currency' => strtoupper((string) $invoice['currency']),
            'amount' => (string) $invoice['amount'],
            'reference' => Util::truncate((string) $invoice['reference'], self::MAX_REFERENCE),
            'lifetime_minutes' => $settings->lifetime(),
        ];
        $description = trim(html_entity_decode(strip_tags((string) $invoice['description']), ENT_QUOTES, 'UTF-8'));
        if ($description !== '') {
            $body['description'] = Util::truncate($description, self::MAX_DESCRIPTION);
        }

        // A link longer than the API takes would fail the whole invoice; without it the buyer still pays.
        $returnUrl = (string) $invoice['return_url'];
        if ($returnUrl !== '' && strlen($returnUrl) <= self::MAX_RETURN_URL) {
            $body['return_url'] = $returnUrl;
            if ($settings->flag('auto_return')) {
                $body['auto_return'] = true;
            }
        }

        foreach (['stablecoins_only', 'buyer_pays_fee', 'buyer_pays_network'] as $flag) {
            if ($settings->flag($flag)) {
                $body[$flag] = true;
            }
        }

        return $body;
    }

    /**
     * Idempotency key: the same invoice, attempt and body give the same key, so a repeated "Pay"
     * gets the CoinsPay invoice already made. The body is part of the key, so a changed amount never
     * meets the "same key, other body" error; the site part keeps a staging copy on the same
     * CoinsPay shop from getting invoices of the live site.
     *
     * @param int $attempt CoinsPay invoice number within the DLE-Billing invoice, from 1.
     */
    public static function idempotencyKey(string $site, int $invoiceId, int $attempt, array $body): string
    {
        return sprintf('dle-%s-%d-%d-%s', substr(md5($site), 0, 8), $invoiceId, $attempt, substr(sha1(Util::json($body)), 0, 12));
    }
}
