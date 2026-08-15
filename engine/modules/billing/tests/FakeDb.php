<?php

class FakeDb
{
    public array $users = [];
    public array $invoices = [];
    public array $coupons = [];
    public array $history = [];
    public array $queries = [];
    public int $affected = 0;
    public int $insertId = 0;

    private array $rows = [];
    private ?array $snapshot = null;

    public function safesql($value): string
    {
        return addslashes((string) $value);
    }

    public function get_affected_rows(): int
    {
        return $this->affected;
    }

    public function insert_id(): int
    {
        return $this->insertId;
    }

    public function get_row(): mixed
    {
        return array_shift($this->rows) ?: null;
    }

    public function super_query(string $sql): mixed
    {
        $this->query($sql);

        return $this->get_row();
    }

    public function query(string $sql): bool
    {
        $this->queries[] = $sql;
        $this->affected = 0;
        $this->rows = [];

        $normalized = trim($sql);

        if (preg_match('/^START TRANSACTION/i', $normalized)) {
            $this->snapshot = [
                'users' => $this->users,
                'invoices' => $this->invoices,
                'coupons' => $this->coupons,
                'history' => $this->history,
            ];

            return true;
        }

        if (preg_match('/^COMMIT/i', $normalized)) {
            $this->snapshot = null;

            return true;
        }

        if (preg_match('/^ROLLBACK/i', $normalized)) {
            if ($this->snapshot !== null) {
                $this->users = $this->snapshot['users'];
                $this->invoices = $this->snapshot['invoices'];
                $this->coupons = $this->snapshot['coupons'];
                $this->history = $this->snapshot['history'];
            }

            $this->snapshot = null;

            return true;
        }

        if (preg_match('/FROM\s+\S+_users\s+WHERE\s+user_id\s*=\s*(\d+)/i', $normalized, $m)) {
            $user = $this->users[(int) $m[1]] ?? null;
            $this->rows = $user ? [$user] : [];

            return true;
        }

        if (preg_match("/FROM\s+\S+_users\s+WHERE\s+name\s*=\s*'([^']*)'/i", $normalized, $m)) {
            foreach ($this->users as $user) {
                if (($user['name'] ?? '') === stripslashes($m[1])) {
                    $this->rows = [$user];

                    return true;
                }
            }

            return true;
        }

        if (preg_match('/UPDATE\s+\S+_users\s+SET\s+(\S+)\s*=\s*\1\s*([+-])\s*([0-9.]+)\s+WHERE\s+user_id\s*=\s*(\d+)(?:\s+AND\s+\1\s*>=\s*([0-9.]+))?/i', $normalized, $m)) {
            $field = $m[1];
            $delta = (float) $m[3];
            $userId = (int) $m[4];
            $min = isset($m[5]) ? (float) $m[5] : null;

            if (!isset($this->users[$userId])) {
                return true;
            }

            $balance = (float) ($this->users[$userId][$field] ?? 0);

            if ($m[2] === '-' && $min !== null && $balance < $min) {
                return true;
            }

            $this->users[$userId][$field] = $m[2] === '-' ? $balance - $delta : $balance + $delta;
            $this->affected = 1;

            return true;
        }

        if (preg_match('/INSERT\s+INTO\s+\S+_billing_history/i', $normalized)) {
            $this->history[] = $normalized;
            $this->insertId = count($this->history);
            $this->affected = 1;

            return true;
        }

        if (preg_match('/UPDATE\s+\S+_billing_invoice\s+SET\s+(.+)\s+WHERE\s+invoice_id\s*=\s*(\d+)(.*)/is', $normalized, $m)) {
            $id = (int) $m[2];
            $requireUnpaid = (bool) preg_match('/invoice_date_pay\s*=\s*0/', $m[3]);

            if (!isset($this->invoices[$id])) {
                return true;
            }

            if ($requireUnpaid && (int) ($this->invoices[$id]['invoice_date_pay'] ?? 0) !== 0) {
                return true;
            }

            if (preg_match("/invoice_date_pay\s*=\s*'?(\d+)'?/", $m[1], $pay)) {
                $this->invoices[$id]['invoice_date_pay'] = (int) $pay[1];
            }

            if (preg_match("/invoice_paysys\s*=\s*'([^']*)'/", $m[1], $sys)) {
                $this->invoices[$id]['invoice_paysys'] = stripslashes($sys[1]);
            }

            $this->affected = 1;

            return true;
        }

        if (preg_match('/SELECT\s+.+FROM\s+\S+_billing_invoice\s+WHERE\s+invoice_date_pay\s*=\s*0\s+AND\s+invoice_date_creat\s*<\s*(\d+)/is', $normalized, $m)) {
            $before = (int) $m[1];

            foreach ($this->invoices as $invoice) {
                if ((int) ($invoice['invoice_date_pay'] ?? 0) === 0
                    && (int) ($invoice['invoice_date_creat'] ?? 0) < $before) {
                    $this->rows[] = $invoice;
                }
            }

            return true;
        }

        if (preg_match("/UPDATE\s+\S+_billing_coupons\s+SET\s+coupon_use\s*=\s*'([^']*)'\s+WHERE\s+coupon_id\s*=\s*(\d+)(.*)/is", $normalized, $m)) {
            $value = stripslashes($m[1]);
            $id = (int) $m[2];
            $extra = $m[3];

            if (!isset($this->coupons[$id])) {
                return true;
            }

            $current = (string) ($this->coupons[$id]['coupon_use'] ?? '');

            if (str_contains($extra, "coupon_use = ''") && $current !== '') {
                return true;
            }

            if (str_contains($extra, "coupon_use != ''") && $current === '') {
                return true;
            }

            $this->coupons[$id]['coupon_use'] = $value;
            $this->affected = 1;

            return true;
        }

        if (preg_match('/DELETE\s+FROM\s+\S+_billing_invoice/i', $normalized)) {
            $deleted = 0;

            foreach ($this->invoices as $id => $invoice) {
                if (str_contains($normalized, 'invoice_date_pay')
                    && (int) ($invoice['invoice_date_pay'] ?? 0) !== 0) {
                    continue;
                }

                if (preg_match('/invoice_date_creat\s*<\s*[\'"]?(\d+)/', $normalized, $m)
                    && (int) ($invoice['invoice_date_creat'] ?? 0) >= (int) $m[1]) {
                    continue;
                }

                unset($this->invoices[$id]);
                $deleted++;
            }

            $this->affected = $deleted;

            return true;
        }

        return true;
    }

    public function addUser(int $id, string $name, float $balance, string $email = ''): void
    {
        $this->users[$id] = [
            'user_id' => $id,
            'name' => $name,
            'email' => $email ?: $name . '@example.test',
            'user_balance' => $balance,
        ];
    }
}
