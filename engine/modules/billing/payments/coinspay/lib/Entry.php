<?php

namespace CoinsPay\Dle;

use Throwable;

/**
 * Where DLE-Billing meets the module: the settings page, the form on the invoice page, and the
 * payment handler (the webhook and the cron). adm.settings.php calls it; a failure here never
 * breaks a page of DLE.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Entry
{
    /** Seconds of an API request inside a page the user or admin waits for. */
    public const PAGE_TIMEOUT = 8;

    /** Polling from pages: at most this often, a few invoices, a few seconds. */
    public const PAGE_POLL_EVERY = 60;

    /** The cron address checks at most this often. */
    public const CRON_EVERY = 30;

    /** The module with the configuration of the payment system. */
    public static function module(?array $config = null, int $timeout = ApiClient::TIMEOUT): Module
    {
        return new Module(new DleHost(), $config ?? DleHost::paymentConfig(), $timeout);
    }

    /**
     * Settings(): the rows of the settings page.
     *
     * @param array $config The configuration of the payment system.
     */
    public static function settings(array $config): array
    {
        try {
            $module = self::module($config, self::PAGE_TIMEOUT);
            $host = $module->host();
            if (!$host instanceof DleHost) {
                return [];
            }

            return (new Panel($module, $host))->rows($config, (string) ($GLOBALS['dle_login_hash'] ?? ''));
        } catch (Throwable $e) {
            error_log('CoinsPay: settings: ' . $e->getMessage());

            return [['CoinsPay', '', '<div style="color:#c62828">' . Util::html($e->getMessage()) . '</div>']];
        }
    }

    /**
     * Form(): what the invoice page shows after the user picked CoinsPay — the form that goes to
     * the payment page (DLE-Billing submits #paysys_form by itself when "redirect" is on), or a
     * message.
     */
    public static function form(int $invoiceId, array $config, array $invoice, string $description): string
    {
        $text = new Text((string) ($GLOBALS['selected_language'] ?? 'Russian'));
        try {
            $module = self::module($config, self::PAGE_TIMEOUT);
            $text = $module->pageText();
            self::pagePoll($module);
            $result = $module->payment()->start($invoiceId, $invoice, $description);
        } catch (Throwable $e) {
            error_log('CoinsPay: form: ' . $e->getMessage());
            $result = ['error' => $text->t('error_api')];
        }

        if (isset($result['redirect'])) {
            return self::redirectForm((string) $result['redirect'], $text);
        }
        if (isset($result['done'])) {
            $back = isset($module) && $module->host() instanceof DleHost ? $module->host()->invoiceUrl($invoiceId) : '';

            return '<div class="billing-pay-done" style="padding:10px 0">' . Util::html((string) $result['done'])
                . ($back !== '' ? ' <a href="' . Util::html($back) . '">' . Util::html($text->t('back_link')) . '</a>' : '') . '</div>';
        }

        return '<div class="billing-pay-error" style="padding:10px 0;color:#c62828">' . Util::html((string) ($result['error'] ?? $text->t('error_api'))) . '</div>';
    }

    /**
     * check_id(): the payment handler of DLE-Billing passed the request to the module — the
     * webhook, or the cron with ?coinspay=poll. The module answers it itself and ends the request.
     */
    public static function handler(): void
    {
        $status = 500;
        $body = 'the module failed';
        try {
            $module = self::module();
            if (isset($_GET['coinspay']) && $_GET['coinspay'] === 'poll') {
                [$status, $body] = self::poll($module);
            } else {
                $raw = (string) file_get_contents('php://input');
                $signature = isset($_SERVER['HTTP_X_CPAY_SIGNATURE']) && is_string($_SERVER['HTTP_X_CPAY_SIGNATURE']) ? $_SERVER['HTTP_X_CPAY_SIGNATURE'] : '';
                [$status, $body] = $module->webhook()->handle($raw, $signature);
            }
        } catch (Throwable $e) {
            error_log('CoinsPay: handler: ' . $e->getMessage());
            if (isset($module)) {
                $module->host()->log('error', 'handler: ' . $e->getMessage());
            }
        }
        self::respond($status, $body);
    }

    /** The cron: open and recently closed CoinsPay invoices. */
    private static function poll(Module $module): array
    {
        if ($module->settings()->apiKey() === '' || !$module->repo()->ensure()) {
            return [200, 'not set up'];
        }
        // The address is known to whoever knows the key of DLE-Billing: a loop of it must not use up
        // the API limit of the shop.
        if (!$module->repo()->takeTurn('cron', self::CRON_EVERY)) {
            return [200, 'skipped: checked less than ' . self::CRON_EVERY . ' seconds ago'];
        }
        $module->repo()->kvSet('poll_cron', (string) time());

        return [200, 'checked ' . $module->poller()->run()];
    }

    /** A few due invoices from a page, at most once a minute for the whole site. */
    private static function pagePoll(Module $module): void
    {
        try {
            $repo = $module->repo();
            if ($module->settings()->apiKey() !== '' && $repo->ready() && $repo->takeTurn('poll_page', self::PAGE_POLL_EVERY)) {
                $module->poller()->run(4, 5);
            }
        } catch (Throwable $e) {
            $module->host()->log('error', 'polling from a page: ' . $e->getMessage());
        }
    }

    /** A GET form to the payment page: its query goes in hidden fields, a form drops it from action. */
    private static function redirectForm(string $url, Text $text): string
    {
        $parts = parse_url($url);
        $action = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($parts['path'] ?? '/');
        $fields = '';
        parse_str((string) ($parts['query'] ?? ''), $query);
        foreach ($query as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $fields .= '<input type="hidden" name="' . Util::html($name) . '" value="' . Util::html($value) . '">';
            }
        }

        return '<form method="get" id="paysys_form" action="' . Util::html($action) . '">' . $fields
            . '<input type="submit" class="btn" value="' . Util::html($text->t('redirect_button')) . '"></form>';
    }

    /** Ends the request with the answer of the module, without the page of DLE around it. */
    private static function respond(int $status, string $body): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
        }
        echo $body;
        exit;
    }
}
