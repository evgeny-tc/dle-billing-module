<?php

namespace CoinsPay\Dle;

/**
 * Tables of the module: CoinsPay invoices of DLE-Billing invoices, and a small key-value store.
 * Created on first use: the module is copied to the site, there is no installer.
 *
 * The id of a row is what the balance history of DLE-Billing keeps for its payments
 * (history_plugin "coinspay", history_plugin_id the row): what a CoinsPay invoice brought is read
 * from there. Tables made anew start their ids past the old history rows, so they never meet.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
class Repo
{
    private Db $db;

    private string $prefix;

    private string $history;

    private ?bool $ready = null;

    /**
     * @param string $prefix  Prefix of the DLE tables (PREFIX, "dle").
     * @param string $history The balance history table of DLE-Billing.
     */
    public function __construct(Db $db, string $prefix, string $history)
    {
        $this->db = $db;
        $this->prefix = $prefix;
        $this->history = $history;
    }

    public function invoices(): string
    {
        return $this->prefix . '_coinspay_invoice';
    }

    public function kv(): string
    {
        return $this->prefix . '_coinspay_kv';
    }

    /**
     * Creates the tables. `attention` — a payment that needs a look of the admin; `problem` — a
     * payment received but not added or not paid yet, cleared when a retry goes through.
     */
    public function install(): void
    {
        $charset = $this->charset();
        $this->db->run(
            'CREATE TABLE IF NOT EXISTS `' . $this->invoices() . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `invoice_id` CHAR(36) NOT NULL,
                `dle_invoice` INT UNSIGNED NOT NULL,
                `dle_created` INT UNSIGNED NOT NULL DEFAULT 0,
                `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `user_name` VARCHAR(100) NOT NULL DEFAULT \'\',
                `service` TINYINT(1) NOT NULL DEFAULT 0,
                `attempt` INT UNSIGNED NOT NULL,
                `amount` VARCHAR(40) NOT NULL,
                `currency` VARCHAR(10) NOT NULL,
                `due` VARCHAR(40) NOT NULL DEFAULT \'\',
                `cost` VARCHAR(40) NOT NULL DEFAULT \'\',
                `test` TINYINT(1) NOT NULL DEFAULT 0,
                `test_admin` TINYINT(1) NOT NULL DEFAULT 0,
                `status` VARCHAR(16) NOT NULL DEFAULT \'open\',
                `pay_url` VARCHAR(500) NOT NULL DEFAULT \'\',
                `snapshot` TEXT NULL,
                `fingerprint` CHAR(32) NULL,
                `apply_state` VARCHAR(10) NOT NULL DEFAULT \'none\',
                `claimed` INT UNSIGNED NULL,
                `attention` TEXT NULL,
                `problem` TEXT NULL,
                `next_poll` INT UNSIGNED NULL,
                `poll_until` INT UNSIGNED NOT NULL DEFAULT 0,
                `created` INT UNSIGNED NOT NULL,
                `updated` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `invoice_id` (`invoice_id`),
                KEY `dle_invoice` (`dle_invoice`),
                KEY `next_poll` (`next_poll`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset
        );
        $this->db->run(
            'CREATE TABLE IF NOT EXISTS `' . $this->kv() . '` (
                `k` VARCHAR(64) NOT NULL,
                `v` TEXT NOT NULL,
                `updated` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`k`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $charset
        );
        // Ids past the history rows of earlier tables of the module.
        $empty = $this->db->one('SELECT COUNT(*) AS n FROM `' . $this->invoices() . '`');
        if ($empty !== null && (int) $empty['n'] === 0) {
            $last = $this->db->one('SELECT COALESCE(MAX(history_plugin_id), 0) AS n FROM `' . $this->history . '` WHERE history_plugin = ?', [Settings::NAME]);
            $next = (int) ($last['n'] ?? 0) + 1;
            if ($next > 1) {
                $this->db->run('ALTER TABLE `' . $this->invoices() . '` AUTO_INCREMENT = ' . $next);
            }
        }
        $this->ready = true;
    }

    /**
     * The character set of the tables: the one of the balance history of DLE-Billing, so the
     * tables of the module compare as the tables of DLE do. MySQL 8 would take utf8mb4_0900_ai_ci
     * otherwise, and DLE keeps utf8mb4_general_ci.
     */
    private function charset(): string
    {
        $row = $this->db->one('SELECT table_collation AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$this->history]);
        $collation = (string) ($row['c'] ?? '');
        if (!preg_match('/^(utf8mb4|utf8mb3|utf8)_[a-z0-9_]+$/D', $collation)) {
            $collation = 'utf8mb4_general_ci';
        }

        return substr($collation, 0, strpos($collation, '_')) . ' COLLATE ' . $collation;
    }

    /** The tables, created when they are missing. */
    public function ensure(): bool
    {
        if (!$this->ready()) {
            $this->install();
        }

        return $this->ready();
    }

    /** Whether the tables exist. */
    public function ready(): bool
    {
        if ($this->ready === null) {
            $row = $this->db->one('SELECT 1 AS found FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$this->invoices()]);
            $this->ready = $row !== null;
        }

        return $this->ready;
    }

    /**
     * @param array $row Columns of a new CoinsPay invoice.
     * @return int Its id.
     */
    public function add(array $row): int
    {
        $now = time();
        $row += ['created' => $now, 'updated' => $now, 'apply_state' => 'none'];
        $columns = array_keys($row);
        $this->db->run(
            'INSERT INTO `' . $this->invoices() . '` (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            array_values($row)
        );

        return $this->db->insertId();
    }

    public function find(string $invoiceId): ?array
    {
        if (!$this->ready()) {
            return null;
        }

        return $this->db->one('SELECT * FROM `' . $this->invoices() . '` WHERE invoice_id = ?', [strtolower($invoiceId)]);
    }

    public function byId(int $id): ?array
    {
        if (!$this->ready()) {
            return null;
        }

        return $this->db->one('SELECT * FROM `' . $this->invoices() . '` WHERE id = ?', [$id]);
    }

    /** CoinsPay invoices of a DLE-Billing invoice, the newest first. */
    public function forInvoice(int $dleInvoice): array
    {
        if (!$this->ready()) {
            return [];
        }

        return $this->db->all('SELECT * FROM `' . $this->invoices() . '` WHERE dle_invoice = ? ORDER BY attempt DESC', [$dleInvoice]);
    }

    /** The newest CoinsPay invoice of a DLE-Billing invoice: the one the user pays now. */
    public function current(int $dleInvoice): ?array
    {
        $rows = $this->forInvoice($dleInvoice);

        return $rows[0] ?? null;
    }

    public function update(string $invoiceId, array $columns): void
    {
        $columns['updated'] = time();
        $sets = [];
        foreach (array_keys($columns) as $column) {
            $sets[] = '`' . $column . '` = ?';
        }
        $args = array_values($columns);
        $args[] = strtolower($invoiceId);
        $this->db->run('UPDATE `' . $this->invoices() . '` SET ' . implode(', ', $sets) . ' WHERE invoice_id = ?', $args);
    }

    /** Invoices to poll now: open ones, and closed ones a little longer for a late payment. */
    public function due(int $limit): array
    {
        if (!$this->ready()) {
            return [];
        }
        $now = time();

        return $this->db->all(
            'SELECT * FROM `' . $this->invoices() . '` WHERE next_poll IS NOT NULL AND next_poll <= ? AND poll_until > ? ORDER BY next_poll LIMIT ' . max(1, $limit),
            [$now, $now]
        );
    }

    /** CoinsPay invoices with money that need a look of the admin, the newest first. */
    public function attention(int $limit, int $since): array
    {
        if (!$this->ready()) {
            return [];
        }

        return $this->db->all(
            'SELECT * FROM `' . $this->invoices() . '` WHERE (attention IS NOT NULL OR problem IS NOT NULL) AND updated >= ? ORDER BY updated DESC LIMIT ' . max(1, $limit),
            [$since]
        );
    }

    /** The newest CoinsPay invoices, for the panel. */
    public function latest(int $limit): array
    {
        if (!$this->ready()) {
            return [];
        }

        return $this->db->all('SELECT * FROM `' . $this->invoices() . '` ORDER BY id DESC LIMIT ' . max(1, $limit));
    }

    /** The admin has looked at a payment: its note leaves the list. A problem stays until a retry clears it. */
    public function resolve(int $id): void
    {
        $this->db->run('UPDATE `' . $this->invoices() . '` SET attention = NULL WHERE id = ?', [$id]);
    }

    public function kvGet(string $key, int $maxAge = 0): ?string
    {
        if (!$this->ready()) {
            return null;
        }
        $row = $this->db->one('SELECT v, updated FROM `' . $this->kv() . '` WHERE k = ?', [$key]);
        if (!$row || ($maxAge > 0 && (int) $row['updated'] < time() - $maxAge)) {
            return null;
        }

        return (string) $row['v'];
    }

    public function kvSet(string $key, string $value): void
    {
        if (!$this->ready()) {
            return;
        }
        $this->db->run(
            'INSERT INTO `' . $this->kv() . '` (k, v, updated) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v), updated = VALUES(updated)',
            [$key, $value, time()]
        );
    }

    /**
     * Whether this request may do a periodic job now: one request takes it, the others skip.
     *
     * @param int $every Seconds.
     */
    public function takeTurn(string $key, int $every): bool
    {
        if (!$this->ready()) {
            return false;
        }
        $now = time();
        $this->db->run('INSERT IGNORE INTO `' . $this->kv() . '` (k, v, updated) VALUES (?, \'\', 0)', [$key]);

        return $this->db->run('UPDATE `' . $this->kv() . '` SET updated = ? WHERE k = ? AND updated <= ?', [$now, $key, $now - $every]) === 1;
    }

    /**
     * Claims the payment of a CoinsPay invoice for this request: one row update, so of two
     * requests at once only one gets it, whatever the named lock did. A claim left by a request
     * that died is taken over after $stale seconds.
     */
    public function claim(string $invoiceId, int $stale): bool
    {
        $now = time();

        return $this->db->run(
            'UPDATE `' . $this->invoices() . '` SET apply_state = \'applying\', claimed = ?, updated = ?
            WHERE invoice_id = ? AND (apply_state <> \'applying\' OR claimed IS NULL OR claimed < ?)',
            [$now, $now, strtolower($invoiceId), $now - $stale]
        ) === 1;
    }

    /**
     * Takes the lock of a DLE-Billing invoice: requests of one invoice go in turn. claim() is the
     * guard of the payment itself.
     *
     * @param string $site Host::siteKey(): one lock for the webhook, the cron and the user.
     * @return bool False when another request held it for 10 seconds.
     */
    public function lock(string $site, int $dleInvoice): bool
    {
        $row = $this->db->one('SELECT GET_LOCK(?, 10) AS locked', [$this->lockName($site, $dleInvoice)]);

        return $row !== null && (string) $row['locked'] === '1';
    }

    public function unlock(string $site, int $dleInvoice): void
    {
        $this->db->one('SELECT RELEASE_LOCK(?) AS released', [$this->lockName($site, $dleInvoice)]);
    }

    private function lockName(string $site, int $dleInvoice): string
    {
        return 'coinspay_dle_' . substr(md5($site), 0, 12) . '_' . $dleInvoice;
    }
}
