<?php

namespace CoinsPay\Dle;

use RuntimeException;

/**
 * SQL on the connection of DLE: the module shares it with DLE-Billing, so a payment and the
 * balance it changes are in one transaction. Arguments go in place of "?" marks, quoted.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
class Db
{
    /** @var object The db object of DLE (engine/classes/mysql.php). */
    private object $db;

    public function __construct(object $db)
    {
        $this->db = $db;
    }

    /**
     * Runs a statement.
     *
     * @return int Rows it changed.
     * @throws RuntimeException The query failed.
     */
    public function run(string $sql, array $args = []): int
    {
        $result = $this->query($sql, $args);
        if (is_object($result)) {
            $this->db->free($result);
        }

        return (int) $this->db->get_affected_rows();
    }

    /** Rows of a SELECT as arrays. */
    public function all(string $sql, array $args = []): array
    {
        $result = $this->query($sql, $args);
        $rows = [];
        if (is_object($result)) {
            while ($row = $this->db->get_row($result)) {
                $rows[] = $row;
            }
            $this->db->free($result);
        }

        return $rows;
    }

    /** The first row of a SELECT, or null. */
    public function one(string $sql, array $args = []): ?array
    {
        $rows = $this->all($sql, $args);

        return $rows[0] ?? null;
    }

    /** The id of the row an INSERT added. */
    public function insertId(): int
    {
        return (int) $this->db->insert_id();
    }

    /** Escapes a string for a quoted SQL literal. */
    public function escape(string $value): string
    {
        return (string) $this->db->safesql($value);
    }

    /** The SQL with its arguments in place: strings quoted, numbers as they are, null as NULL. */
    public function bind(string $sql, array $args): string
    {
        if (!$args) {
            return $sql;
        }
        $parts = explode('?', $sql);
        if (count($parts) !== count($args) + 1) {
            throw new RuntimeException('SQL arguments do not match: ' . $sql);
        }
        $out = array_shift($parts);
        foreach ($args as $i => $arg) {
            if ($arg === null) {
                $value = 'NULL';
            } elseif (is_int($arg)) {
                $value = (string) $arg;
            } elseif (is_bool($arg)) {
                $value = $arg ? '1' : '0';
            } else {
                $value = "'" . $this->escape((string) $arg) . "'";
            }
            $out .= $value . $parts[$i];
        }

        return $out;
    }

    private function query(string $sql, array $args): mixed
    {
        $sql = $this->bind($sql, $args);
        $before = count($this->db->query_errors_list ?? []);
        // Without the error page of DLE: it would end the request with HTML.
        $result = $this->db->query($sql, false, false);
        if ($result === false) {
            $errors = $this->db->query_errors_list ?? [];
            $last = count($errors) > $before ? end($errors) : [];

            throw new RuntimeException('Database query failed: ' . (is_array($last) && isset($last['error']) ? $last['error'] : 'unknown error'));
        }

        return $result;
    }
}
