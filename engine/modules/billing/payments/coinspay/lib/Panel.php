<?php

namespace CoinsPay\Dle;

use Throwable;

/**
 * The settings of the payment system in the admin panel of DLE-Billing ("Integration" tab): the
 * fields of the module and the state of the connection — the shop of the key, the webhook and
 * cron addresses, payments that need the admin, the newest invoices, the log.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Panel
{
    /** The answer of GET /shop is kept this long. */
    public const SHOP_CACHE = 600;

    /** Payments that need the admin are listed for this long. */
    public const ATTENTION_DAYS = 30;

    /** Without a poll for this long the cron counts as stopped. */
    public const CRON_LATE = 900;

    private Module $module;

    private DleHost $host;

    private Text $text;

    public function __construct(Module $module, DleHost $host)
    {
        $this->module = $module;
        $this->host = $host;
        $this->text = $module->pageText();
    }

    /**
     * Rows for Settings(): {0: title, 1: description, 2: HTML of the field}.
     *
     * @param array  $config The configuration of the payment system.
     * @param string $hash   The CSRF token of DLE (dle_login_hash) for the links of the panel.
     */
    public function rows(array $config, string $hash): array
    {
        $t = $this->text;
        $settings = $this->module->settings();
        $notice = '';
        try {
            $this->module->repo()->ensure();
            $notice = $this->action($hash);
            $this->copyIcon();
            // What came while nobody looked: the cron may be missing, and DLE-Billing gives no
            // webhook to a payment system that is switched off.
            if ($settings->apiKey() !== '' && $this->module->repo()->takeTurn('poll_admin', 30)) {
                $this->module->poller()->run(8, 10);
            }
        } catch (Throwable $e) {
            $this->host->log('error', 'panel: ' . $e->getMessage());
            $notice = $this->line(false, $t->t('panel_error', $e->getMessage()));
        }

        $rows = [];
        $rows[] = [$t->t('field_state'), $t->t('field_state_help'), $notice . $this->state($hash)];
        $rows[] = [$t->t('field_api_key'), $t->t('field_api_key_help'), $this->input('api_key', $settings->get('api_key'), 'password')];
        $rows[] = [$t->t('field_webhook_secret'), $t->t('field_webhook_secret_help'), $this->input('webhook_secret', $settings->get('webhook_secret'), 'password')];
        $rows[] = [$t->t('field_currency'), $t->t('field_currency_help'), $this->currencySelect($settings->currency())];
        $rows[] = [$t->t('field_lifetime'), $t->t('field_lifetime_help'), $this->input('lifetime', (string) $settings->lifetime(), 'number', 'min="' . Settings::LIFETIME_MIN . '" max="' . Settings::LIFETIME_MAX . '" style="width: 120px"')];
        foreach (['auto_return', 'stablecoins_only', 'buyer_pays_fee', 'buyer_pays_network', 'debug'] as $flag) {
            $rows[] = [$t->t('field_' . $flag), $t->t('field_' . $flag . '_help'), $this->yesNo($flag, $settings->flag($flag))];
        }
        $rows[] = [$t->t('field_api_url'), $t->t('field_api_url_help'), $this->input('api_url', $settings->get('api_url'), 'text', 'placeholder="' . Settings::DEFAULT_API_URL . '"')];

        return $rows;
    }

    /** A link of the panel: check a payment, resolve a note, poll now. */
    private function action(string $hash): string
    {
        $action = isset($_GET['coinspay_action']) && is_string($_GET['coinspay_action']) ? $_GET['coinspay_action'] : '';
        if ($action === '') {
            return '';
        }
        $given = isset($_GET['coinspay_hash']) && is_string($_GET['coinspay_hash']) ? $_GET['coinspay_hash'] : '';
        if ($hash === '' || !Util::equals($hash, $given)) {
            return $this->line(false, $this->text->t('panel_bad_hash'));
        }
        $repo = $this->module->repo();
        $row = isset($_GET['coinspay_row']) && ctype_digit((string) $_GET['coinspay_row']) ? $repo->byId((int) $_GET['coinspay_row']) : null;
        switch ($action) {
            case 'resolve':
                if ($row) {
                    $repo->resolve((int) $row['id']);

                    return $this->line(true, $this->text->t('panel_resolved', $row['dle_invoice']));
                }
                break;
            case 'check':
                if ($row) {
                    $ok = $this->module->poller()->checkNow($row);

                    return $this->line($ok, $this->text->t($ok ? 'panel_checked' : 'panel_check_failed', $row['dle_invoice']));
                }
                break;
            case 'poll':
                $count = $this->module->poller()->run(20);

                return $this->line(true, $this->text->t('panel_polled', $count));
        }

        return '';
    }

    /** The state block: what works and what needs the admin. */
    private function state(string $hash): string
    {
        $t = $this->text;
        $module = $this->module;
        $settings = $module->settings();
        $repo = $module->repo();
        $out = '';

        if (!$settings->active()) {
            $out .= $this->line(false, $t->t('status_off'));
        }
        if ($settings->apiKey() === '') {
            $out .= $this->line(false, $t->t('status_no_key'));
        } else {
            $shop = $this->shop();
            if (isset($shop['error'])) {
                $out .= $this->line(false, $t->t('status_api_error', $shop['error']));
            } else {
                $test = !empty($shop['key']['test']);
                $out .= $this->line(true, $t->t('status_shop', Util::value($shop['shop'] ?? [], 'name'), $t->t($test ? 'status_mode_test' : 'status_mode_live')));
                if (Util::value($shop['key'] ?? [], 'access') === 'full') {
                    $out .= $this->line(false, $t->t('status_full_access'));
                }
                $currency = $settings->currency();
                if (!$module->currencies()->accepts($currency)) {
                    $out .= $this->line(false, $t->t('status_currency_no', $currency));
                }
            }
        }
        $rate = $settings->get('convert');
        if (!Util::positive($rate)) {
            $out .= $this->line(false, $t->t('status_no_rate'));
        } else {
            $out .= $this->line(null, $t->t('status_rate', $this->host->money('1'), Util::normalize($rate), $settings->currency()));
        }

        $out .= $this->line(null, $t->t('status_webhook'), $this->host->handlerUrl());
        if ($settings->webhookSecret() === '') {
            $out .= $this->line(false, $t->t('status_no_secret'));
        } else {
            $seen = $repo->kvGet('webhook_test');
            $out .= $seen !== null && (int) $seen > 0
                ? $this->line(true, $t->t('status_webhook_seen', gmdate('Y-m-d H:i', (int) $seen) . ' UTC'))
                : $this->line(null, $t->t('status_webhook_unseen'));
        }
        $out .= $this->line(null, $t->t('status_cron'), $this->host->pollUrl());
        $polled = (int) ($repo->kvGet('poll_cron') ?? 0);
        if ($polled > 0) {
            $out .= $this->line($polled >= time() - self::CRON_LATE, $t->t('status_cron_last', gmdate('Y-m-d H:i', $polled) . ' UTC'));
        }
        if (!$this->iconInTemplate()) {
            $out .= $this->line(false, $t->t('status_icon', 'templates/' . $this->skin() . '/billing/icons/' . Settings::NAME . '.png'));
        }

        $attention = $repo->attention(10, time() - self::ATTENTION_DAYS * 86400);
        if ($attention) {
            $out .= '<div style="margin:10px 0 4px;font-weight:600">' . Util::html($t->t('status_attention', self::ATTENTION_DAYS)) . '</div>';
            foreach ($attention as $row) {
                $links = $this->link($t->t('panel_check'), 'check', $hash, (int) $row['id']);
                if ($row['attention'] !== null) {
                    $links .= ' · ' . $this->link($t->t('panel_resolve'), 'resolve', $hash, (int) $row['id']);
                }
                $out .= '<div style="margin:2px 0 6px">' . Util::html(sprintf('#%d — %s', $row['dle_invoice'], trim(($row['problem'] ?? '') . ' ' . ($row['attention'] ?? '')))) . ' <span style="white-space:nowrap">' . $links . '</span></div>';
            }
        }

        $latest = $repo->latest(10);
        if ($latest) {
            $out .= '<details style="margin-top:10px"><summary style="cursor:pointer">' . Util::html($t->t('panel_latest')) . '</summary><table class="table table-sm" style="margin-top:6px">';
            foreach ($latest as $row) {
                $snapshot = json_decode((string) ($row['snapshot'] ?? ''), true);
                $number = is_array($snapshot) ? Util::value($snapshot, 'number') : '';
                $out .= '<tr><td>#' . (int) $row['dle_invoice'] . '</td><td>' . Util::html($row['user_name']) . '</td><td>'
                    . Util::html($row['amount'] . ' ' . $row['currency']) . ($row['test'] ? ' (test)' : '') . '</td><td>'
                    . Util::html(($number !== '' ? '№ ' . $number . ', ' : '') . $row['status']) . '</td><td>'
                    . $this->link($t->t('panel_check'), 'check', $hash, (int) $row['id']) . '</td></tr>';
            }
            $out .= '</table>' . $this->link($t->t('panel_poll'), 'poll', $hash) . '</details>';
        }

        $log = DleHost::logTail(15);
        if ($log) {
            $out .= '<details style="margin-top:6px"><summary style="cursor:pointer">' . Util::html($t->t('panel_log')) . '</summary><pre style="white-space:pre-wrap;font-size:11px;max-height:300px;overflow:auto">'
                . Util::html(implode("\n", $log)) . '</pre></details>';
        }

        return '<div style="text-align:left">' . $out . '</div>';
    }

    /** GET /shop, or {error}; kept for 10 minutes per key. */
    private function shop(): array
    {
        $repo = $this->module->repo();
        $settings = $this->module->settings();
        $key = 'shop_' . substr(hash('sha256', $settings->apiUrl() . '|' . $settings->apiKey()), 0, 16);
        $cached = $repo->kvGet($key, self::SHOP_CACHE);
        if ($cached !== null) {
            $data = json_decode($cached, true);
            if (is_array($data)) {
                return $data;
            }
        }
        try {
            $data = $this->module->client()->shop();
        } catch (ApiError $e) {
            $data = ['error' => $e->summary()];
        }
        // An error is asked again on the next visit.
        if (!isset($data['error'])) {
            $repo->kvSet($key, Util::json($data));
        }

        return $data;
    }

    /** The icon of the payment system in the template of the site, for the invoice page. */
    private function copyIcon(): void
    {
        $target = ROOT_DIR . '/templates/' . $this->skin() . '/billing/icons/' . Settings::NAME . '.png';
        $source = dirname(__DIR__) . '/icon.png';
        if (!is_file($target) && is_file($source) && is_dir(dirname($target)) && is_writable(dirname($target))) {
            @copy($source, $target);
        }
    }

    private function iconInTemplate(): bool
    {
        $folder = ROOT_DIR . '/templates/' . $this->skin() . '/billing/icons';

        return !is_dir($folder) || is_file($folder . '/' . Settings::NAME . '.png');
    }

    private function skin(): string
    {
        $skin = (string) ($GLOBALS['config']['skin'] ?? '');

        return preg_match('/^[a-zA-Z0-9_ -]+$/D', $skin) ? $skin : 'Default';
    }

    private function currencySelect(string $current): string
    {
        $codes = $this->module->currencies()->fiat();
        if (!$codes) {
            $codes = ['USD', 'EUR', 'RUB'];
        }
        if (!in_array($current, $codes, true)) {
            array_unshift($codes, $current);
        }
        $out = '<select name="save_con[invoice_currency]" class="uniform">';
        foreach ($codes as $code) {
            $out .= '<option value="' . Util::html($code) . '"' . ($code === $current ? ' selected' : '') . '>' . Util::html($code) . '</option>';
        }

        return $out . '</select>';
    }

    private function yesNo(string $name, bool $value): string
    {
        return '<select name="save_con[' . $name . ']" class="uniform">'
            . '<option value="1"' . ($value ? ' selected' : '') . '>' . Util::html($this->text->t('field_yes')) . '</option>'
            . '<option value="0"' . (!$value ? ' selected' : '') . '>' . Util::html($this->text->t('field_no')) . '</option>'
            . '</select>';
    }

    private function input(string $name, string $value, string $type, string $extra = 'style="width: 100%"'): string
    {
        return '<input name="save_con[' . $name . ']" class="form-control" type="' . $type . '" value="' . Util::html($value) . '" autocomplete="off" ' . $extra . '>';
    }

    /** A line of the state: ✔ ok, ✘ not ok, plain information; an address after it in a field to copy. */
    private function line(?bool $ok, string $text, string $copy = ''): string
    {
        $mark = $ok === true ? '<span style="color:#2e7d32">&#10004;</span> ' : ($ok === false ? '<span style="color:#c62828">&#10008;</span> ' : '');
        $field = $copy !== '' ? '<input type="text" class="form-control" readonly value="' . Util::html($copy) . '" onclick="this.select()" style="margin-top:3px">' : '';

        return '<div style="margin:3px 0">' . $mark . Util::html($text) . $field . '</div>';
    }

    private function link(string $label, string $action, string $hash, int $row = 0): string
    {
        $query = ['coinspay_action' => $action, 'coinspay_hash' => $hash];
        if ($row > 0) {
            $query['coinspay_row'] = $row;
        }

        return '<a href="' . Util::html($this->host->settingsUrl($query)) . '" style="text-decoration:underline">' . Util::html($label) . '</a>';
    }
}
