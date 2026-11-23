<?php

namespace CoinsPay\Dle;

use RuntimeException;
use Throwable;

/**
 * The module on a real DLE: the db object of DLE, the balance API of DLE-Billing
 * (\Billing\Api\Balance) and its handlers of services, the globals of DLE for the user and the
 * language.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class DleHost implements Host
{
    /** The log of the module, in the data folder of DLE-Billing; rotated at this size. */
    public const LOG_SIZE = 524288;

    private ?Db $db = null;

    /** The configuration of DLE-Billing (engine/data/billing/config.php). */
    private array $billing;

    public function __construct()
    {
        $this->billing = self::billingConfig();
    }

    /** The configuration of DLE-Billing. */
    public static function billingConfig(): array
    {
        $file = ENGINE_DIR . '/data/billing/config.php';
        $config = is_file($file) ? include $file : [];

        return is_array($config) ? $config : [];
    }

    /** The configuration DLE-Billing keeps for the payment system. */
    public static function paymentConfig(): array
    {
        $file = ENGINE_DIR . '/data/billing/payment.' . Settings::NAME . '.php';
        $config = is_file($file) ? include $file : [];

        return is_array($config) ? $config : [];
    }

    public function db(): Db
    {
        if ($this->db === null) {
            if (!isset($GLOBALS['db']) || !is_object($GLOBALS['db'])) {
                throw new RuntimeException('The database of DLE is not connected');
            }
            $this->db = new Db($GLOBALS['db']);
        }

        return $this->db;
    }

    public function prefix(): string
    {
        return PREFIX;
    }

    public function historyTable(): string
    {
        // Where \Billing\Api\Balance::Comment() writes.
        return PREFIX . '_billing_history';
    }

    public function siteKey(): string
    {
        // Without the scheme: one site, whether a request came over http or https.
        $home = strtolower((string) preg_replace('#^[a-z]+://#i', '', rtrim($this->home(), '/')));

        return $home . '|' . (defined('DBNAME') ? DBNAME : '') . '|' . PREFIX;
    }

    public function adminLanguage(): string
    {
        return (string) ($GLOBALS['config']['langs'] ?? 'Russian');
    }

    public function userLanguage(): string
    {
        $lang = $GLOBALS['selected_language'] ?? '';

        return is_string($lang) && $lang !== '' ? $lang : $this->adminLanguage();
    }

    public function isAdmin(): bool
    {
        return (int) ($GLOBALS['member_id']['user_group'] ?? 0) === 1;
    }

    public function currentUser(): ?array
    {
        $member = $GLOBALS['member_id'] ?? [];
        if (!is_array($member) || (int) ($member['user_id'] ?? 0) <= 0 || (string) ($member['name'] ?? '') === '') {
            return null;
        }

        return ['id' => (int) $member['user_id'], 'name' => (string) $member['name']];
    }

    public function invoice(int $invoiceId): ?array
    {
        $row = $this->db()->one('SELECT * FROM `' . USERPREFIX . '_billing_invoice` WHERE invoice_id = ?', [$invoiceId]);
        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['invoice_id'],
            'user' => (string) $row['invoice_user_name'],
            'get' => Util::formatAmount((string) $row['invoice_get'], Decision::SCALE),
            'pay' => (string) $row['invoice_pay'],
            'paid' => (int) $row['invoice_date_pay'] > 0,
            'handler' => trim((string) $row['invoice_handler']),
            'info' => (string) $row['invoice_payer_info'],
            'created' => (int) $row['invoice_date_creat'],
            'row' => $row,
        ];
    }

    public function user(int $userId): ?array
    {
        $field = $this->balanceField();
        $row = $this->db()->one('SELECT user_id, name, `' . $field . '` AS balance FROM `' . USERPREFIX . '_users` WHERE user_id = ?', [$userId]);
        if ($row === null) {
            return null;
        }

        return ['id' => (int) $row['user_id'], 'name' => (string) $row['name'], 'balance' => Util::formatAmount((string) $row['balance'], Decision::SCALE)];
    }

    public function credited(int $rowId): string
    {
        $row = $this->db()->one(
            'SELECT COALESCE(SUM(history_plus), 0) AS total FROM `' . $this->historyTable() . '` WHERE history_plugin = ? AND history_plugin_id = ?',
            [Settings::NAME, $rowId]
        );

        return Util::formatAmount((string) ($row['total'] ?? '0'), Decision::SCALE);
    }

    public function addFunds(int $userId, string $amount, string $comment, int $rowId): void
    {
        $balance = \Billing\Api\Balance::Init();
        $balance->Transaction();
        try {
            $balance->Comment(
                userId: $userId,
                plus: (float) $amount,
                comment: $comment,
                plugin_id: $rowId,
                plugin_name: Settings::NAME,
                pm: !empty($this->billing['mail_payok_pm']),
                email: !empty($this->billing['mail_payok_email'])
            )->To(userId: $userId, sum: (float) $amount)->sendEvent();
            $balance->Commit();
        } catch (Throwable $e) {
            $balance->Rollback();

            throw $e;
        }
    }

    public function markPaid(int $invoiceId): bool
    {
        return $this->db()->run(
            'UPDATE `' . USERPREFIX . '_billing_invoice` SET invoice_date_pay = ?, invoice_paysys = ? WHERE invoice_id = ? AND invoice_date_pay = 0',
            [$this->now(), Settings::NAME, $invoiceId]
        ) === 1;
    }

    public function payFromBalance(array $invoice, int $userId, string $cost): bool
    {
        $parts = \Billing\DevTools::exInvoiceHandler((string) $invoice['handler']);
        $plugin = (string) ($parts[0] ?? '');
        $file = (string) ($parts[1] ?? '');
        $handler = $plugin !== '' && $file !== '' ? \Billing\DevTools::getHandler($plugin, $file) : null;
        if ($handler === null) {
            throw new RuntimeException('the handler "' . $invoice['handler'] . '" of the invoice is missing');
        }
        $info = \Billing\DevTools::decodeInfo($invoice['info']);
        $history = $handler->desc($info);
        $row = $invoice['row'];
        $row['invoice_paysys'] = Settings::NAME;

        $balance = \Billing\Api\Balance::Init();
        $balance->Transaction();
        try {
            $balance->Comment(
                userId: $userId,
                minus: (float) $cost,
                comment: (string) ($history[0] ?? ''),
                plugin_id: (int) ($history[1] ?? 0),
                plugin_name: $plugin,
                pm: !empty($this->billing['mail_payok_pm']),
                email: !empty($this->billing['mail_payok_email'])
            )->From(userId: $userId, sum: (float) $cost)->sendEvent();
            if (!$this->markPaid((int) $invoice['id'])) {
                throw new RuntimeException('the invoice is paid already');
            }
            if ($handler->pay($row) !== true) {
                throw new RuntimeException('the handler "' . $invoice['handler'] . '" did not take the payment');
            }
            $balance->Commit();

            return true;
        } catch (\Billing\BalanceException $e) {
            $balance->Rollback();
            if ($e->getMessage() === 'balance.check') {
                // The balance does not cover it any more.
                return false;
            }

            throw $e;
        } catch (Throwable $e) {
            $balance->Rollback();

            throw $e;
        }
    }

    public function money(string $amount): string
    {
        try {
            return (string) \Billing\Api\Balance::Init()->Convert(value: $amount, separator_space: true, declension: true);
        } catch (Throwable $e) {
            return $amount;
        }
    }

    public function invoiceUrl(int $invoiceId): string
    {
        return $this->home() . $this->page() . '.html/pay/waiting/id/' . $invoiceId . '/';
    }

    public function handlerUrl(): string
    {
        return $this->home() . $this->page() . '.html/pay/handler/payment/' . Settings::NAME . '/key/' . rawurlencode((string) ($this->billing['secret'] ?? '')) . '/';
    }

    /** The handler address with ?coinspay=poll: the cron of the server polls with it. */
    public function pollUrl(): string
    {
        return $this->handlerUrl() . '?coinspay=poll';
    }

    /** The settings page of the payment system in the admin panel. */
    public function settingsUrl(array $query = []): string
    {
        $base = '?mod=billing&c=payment&p=billing/' . Settings::NAME;

        return $query ? $base . '&' . http_build_query($query, '', '&') : $base;
    }

    public function log(string $level, string $message): void
    {
        $file = self::logFile();
        $line = gmdate('Y-m-d H:i:s') . ' UTC ' . strtoupper($level) . ' ' . str_replace(["\r", "\n"], ' ', $message) . "\n";
        if ($level === 'error') {
            error_log('CoinsPay: ' . $message);
        }
        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            return;
        }
        flock($handle, LOCK_EX);
        $size = (int) (fstat($handle)['size'] ?? 0);
        if ($size > self::LOG_SIZE) {
            // Renamed under the lock: a writer that waited for it writes to the old file, which has the header too.
            @rename($file, substr($file, 0, -8) . '.old.log.php');
            flock($handle, LOCK_UN);
            fclose($handle);
            $handle = @fopen($file, 'c+');
            if ($handle === false) {
                return;
            }
            flock($handle, LOCK_EX);
            $size = (int) (fstat($handle)['size'] ?? 0);
        }
        fseek($handle, 0, SEEK_END);
        // Under the data folder; a hit over the web gets the die() and nothing else.
        fwrite($handle, ($size === 0 ? "<?php die(); ?>\n" : '') . $line);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /** The log of the module. */
    public static function logFile(): string
    {
        return ENGINE_DIR . '/data/billing/coinspay.log.php';
    }

    /** The last lines of the log, the newest last. */
    public static function logTail(int $lines): array
    {
        $file = self::logFile();
        if (!is_file($file)) {
            return [];
        }
        $all = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        array_shift($all);

        return array_slice($all, -$lines);
    }

    /** The address of the site with the trailing slash. */
    public function home(): string
    {
        return rtrim((string) ($GLOBALS['config']['http_home_url'] ?? '/'), '/') . '/';
    }

    /** The page of the cabinet of DLE-Billing ("billing"). */
    private function page(): string
    {
        $page = (string) ($this->billing['page'] ?? 'billing');

        return preg_match('/^[a-zA-Z0-9_-]+$/D', $page) ? $page : 'billing';
    }

    /** The column of the users table with the balance. */
    private function balanceField(): string
    {
        $field = (string) ($this->billing['fname'] ?? 'user_balance');

        return preg_match('/^[a-zA-Z0-9_]+$/D', $field) ? $field : 'user_balance';
    }

    private function now(): int
    {
        return isset($GLOBALS['_TIME']) && is_numeric($GLOBALS['_TIME']) ? (int) $GLOBALS['_TIME'] : time();
    }
}
