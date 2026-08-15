<?php

/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2026
 */

namespace Billing;

/**
 *
 */
class Database
{
    /**
     * Builder WHERE condition
     */
    public string $where = '';

    /**
     * Database connection instance
     */
    public \db $db;

    /**
     * Balance field name in users table
     */
    public string $balanceField;

    /**
     * Current timestamp
     */
    public int $currentTime;

    /**
     * Allowed characters patterns for sanitization
     */
    private const PATTERN_DEFAULT = '';
    private const PATTERN_ALPHANUMERIC = '/[^a-zA-Z0-9\s]/';
    private const PATTERN_ALPHANUMERIC_EXTENDED = '/[^-_a-zA-Z0-9\s]/';
    private const PATTERN_NUMERIC = '/[^.0-9\s]/';
    private const PATTERN_HANDLER = '/[^.a-z:\s]/';
    private const PATTERN_PAYMENT = '/[^a-zA-Z0-9\s]/';

    /**
     * @param object $db
     * @param string $field
     * @param int $time
     */
    public function __construct(object $db, string $field, int $time)
    {
        $this->db = $db;
        $this->balanceField = $field;
        $this->currentTime = $time;
    }

    /**
     * Get valid coupon by key
     * @param string $coupon
     * @return array|null
     */
    public function getCoupon(string $coupon): ?array
    {
        $coupon = $this->db->safesql($coupon);

        $result = $this->db->super_query(
            "SELECT * FROM " . USERPREFIX . "_billing_coupons
             WHERE coupon_key = '{$coupon}'
               AND coupon_use = ''
               AND (coupon_time_end = 0 OR coupon_time_end > " . time() . ")"
        );

        return $result ?: null;
    }

    /**
     * Mark coupon as used
     * @param array $coupon
     * @param array $invoice
     * @return bool
     */
    public function useCoupon(array $coupon, array $invoice = []): bool
    {
        if (!empty($invoice['invoice_id'])) {
            $this->updateInvoiceWithCoupon($invoice, $coupon);
        }

        return (bool) $this->db->query(
            "UPDATE " . USERPREFIX . "_billing_coupons
             SET coupon_use = '{$invoice['invoice_user_name']}'
             WHERE coupon_id = " . intval($coupon['coupon_id'])
        );
    }

    /**
     * Search users with current WHERE condition
     * @param int|null $limit
     * @return array
     */
    public function searchUsers(?int $limit = 100): array
    {
        $limit = max(1, intval($limit));

        $this->db->query(
            "SELECT * FROM " . USERPREFIX . "_users 
             {$this->where} 
             ORDER BY {$this->balanceField} DESC 
             LIMIT {$limit}"
        );

        $users = [];
        while ($row = $this->db->get_row()) {
            $users[] = $row;
        }

        return $users;
    }

    /**
     * Find user by name or email
     * @param string $search
     * @return array|null
     */
    public function findUserByName(string $search): ?array
    {
        $search = $this->db->safesql($search);

        return $this->db->super_query(
            "SELECT * FROM " . USERPREFIX . "_users
             WHERE name = '{$search}' OR email = '{$search}'"
        ) ?: null;
    }

    /**
     * Get refund by ID
     * @param int $refundId
     * @return array|null
     */
    public function getRefundById(int $refundId): ?array
    {
        return $this->db->super_query(
            "SELECT * FROM " . USERPREFIX . "_billing_refund
             WHERE refund_id = " . intval($refundId)
        ) ?: null;
    }

    /**
     * Update refund status
     * @param int $refundId
     * @param int $status
     * @return bool
     */
    public function updateRefundStatus(int $refundId, int $status = 0): bool
    {
        return (bool) $this->db->query(
            "UPDATE " . USERPREFIX . "_billing_refund
             SET refund_date_return = " . max(0, $status) . "
             WHERE refund_id = " . intval($refundId)
        );
    }

    /**
     * Delete refund
     * @param int $refundId
     * @return bool
     */
    public function deleteRefund(int $refundId): bool
    {
        return (bool) $this->db->query(
            "DELETE FROM " . USERPREFIX . "_billing_refund
             WHERE refund_id = " . intval($refundId)
        );
    }

    /**
     * Cancel refund
     * @param int $refundId
     * @return bool
     */
    public function cancelRefund(int $refundId): bool
    {
        return (bool) $this->db->query(
            "UPDATE " . USERPREFIX . "_billing_refund
             SET refund_date_return = 0, 
                 refund_date_cancel = {$this->currentTime}
             WHERE refund_id = " . intval($refundId)
        );
    }

    /**
     * Get total refunds count
     * @return int
     */
    public function getRefundsCount(): int
    {
        $result = $this->db->super_query(
            "SELECT COUNT(*) as count 
             FROM " . USERPREFIX . "_billing_refund {$this->where}"
        );

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get paginated refunds
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getRefunds(int $page = 1, int $perPage = 30): array
    {
        [$offset, $perPage] = $this->preparePagination($page, $perPage);

        $this->db->query(
            "SELECT * FROM " . USERPREFIX . "_billing_refund {$this->where}
             ORDER BY refund_id DESC 
             LIMIT {$offset}, {$perPage}"
        );

        $refunds = [];
        while ($row = $this->db->get_row()) {
            $refunds[] = $row;
        }

        return $refunds;
    }

    /**
     * Delete invoices matching current WHERE condition
     * @return bool
     */
    public function deleteInvoices(): bool
    {
        return (bool) $this->db->query(
            "DELETE FROM " . USERPREFIX . "_billing_invoice {$this->where}"
        );
    }

    /**
     * Get total invoices count
     * @return int
     */
    public function getInvoicesCount(): int
    {
        $result = $this->db->super_query(
            "SELECT COUNT(*) as count 
             FROM " . USERPREFIX . "_billing_invoice {$this->where}"
        );

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get today's invoices sum
     * @return float
     */
    public function getTodayInvoicesSum(): float
    {
        $today = mktime(0, 0, 0);

        $result = $this->db->super_query(
            "SELECT SUM(invoice_get) as total 
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_get > 0 
               AND invoice_date_pay > {$today}"
        );

        return (float) ($result['total'] ?? 0);
    }

    /**
     * Get paginated invoices
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getInvoices(int $page = 1, int $perPage = 30): array
    {
        [$offset, $perPage] = $this->preparePagination($page, $perPage);

        $this->db->query(
            "SELECT * FROM " . USERPREFIX . "_billing_invoice {$this->where}
             ORDER BY invoice_id DESC 
             LIMIT {$offset}, {$perPage}"
        );

        $invoices = [];
        while ($row = $this->db->get_row()) {
            $invoices[] = $row;
        }

        return $invoices;
    }

    /**
     * Get total transactions count
     * @return int
     */
    public function getHistoryCount(): int
    {
        $result = $this->db->super_query(
            "SELECT COUNT(*) as count 
             FROM " . USERPREFIX . "_billing_history {$this->where}"
        );

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get paginated transactions
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getHistory(int $page = 1, int $perPage = 30): array
    {
        [$offset, $perPage] = $this->preparePagination($page, $perPage);

        $this->db->query(
            "SELECT * FROM " . USERPREFIX . "_billing_history {$this->where}
             ORDER BY history_id DESC 
             LIMIT {$offset}, {$perPage}"
        );

        $history = [];
        while ($row = $this->db->get_row()) {
            $history[] = $row;
        }

        return $history;
    }

    public function getTransactionById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->db->super_query(
            "SELECT *, `user_data`.*
                    FROM " . USERPREFIX . "_billing_history `transaction`
                    LEFT JOIN " . USERPREFIX . "_users `user_data`
                        ON user_data.name = transaction.history_user_name
             WHERE history_id = " . $id
        ) ?: null;
    }

    /**
     * Delete transaction by ID
     * @param int $historyId
     * @return bool
     */
    public function deleteHistory(int $historyId): bool
    {
        return (bool) $this->db->query(
            "DELETE FROM " . USERPREFIX . "_billing_history 
             WHERE history_id = " . intval($historyId)
        );
    }

    /**
     * Get invoice by ID
     * @param int $id
     * @return array|null
     */
    public function getInvoiceById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->db->super_query(
            "SELECT *, `user_data`.*
                    FROM " . USERPREFIX . "_billing_invoice `invoice`
                    LEFT JOIN " . USERPREFIX . "_users `user_data`
                        ON user_data.name = invoice.invoice_user_name
             WHERE invoice_id = " . $id
        ) ?: null;
    }

    /**
     * Update invoice
     * @param int $invoiceId
     * @param bool $wait
     * @param string|null $paymentSystem
     * @param float|null $amountPay
     * @param string|null $payerRequisites
     * @return bool
     */
    public function updateInvoice(
        int $invoiceId,
        bool $wait = false,
        ?string $paymentSystem = null,
        ?float $amountPay = null,
        ?string $payerRequisites = null
    ): bool
    {
        $time = $wait ? 0 : $this->currentTime;

        $updates = ["invoice_date_pay = '{$time}'"];

        if ($paymentSystem !== null) {
            $updates[] = "invoice_paysys = '" . $this->sanitize($paymentSystem) . "'";
        }

        if ($amountPay !== null) {
            $updates[] = "invoice_pay = '" . $this->sanitizeNumeric($amountPay) . "'";
        }

        if ($payerRequisites !== null) {
            $updates[] = "invoice_payer_requisites = '" . $this->sanitize($payerRequisites) . "'";
        }

        return (bool) $this->db->query(
            "UPDATE " . USERPREFIX . "_billing_invoice 
             SET " . implode(', ', $updates) . "
             WHERE invoice_id = " . intval($invoiceId)
        );
    }

    /**
     * Mark invoice as paid only if it is still unpaid
     * @param int $invoiceId
     * @param string|null $paymentSystem
     * @param string|null $payerRequisites
     * @return bool
     */
    public function claimInvoice(
        int $invoiceId,
        ?string $paymentSystem = null,
        ?string $payerRequisites = null
    ): bool
    {
        $updates = ["invoice_date_pay = '{$this->currentTime}'"];

        if ($paymentSystem !== null) {
            $updates[] = "invoice_paysys = '" . $this->sanitize($paymentSystem) . "'";
        }

        if ($payerRequisites !== null) {
            $updates[] = "invoice_payer_requisites = '" . $this->sanitize($payerRequisites) . "'";
        }

        $this->db->query(
            "UPDATE " . USERPREFIX . "_billing_invoice 
             SET " . implode(', ', $updates) . "
             WHERE invoice_id = " . intval($invoiceId) . "
               AND invoice_date_pay = 0"
        );

        return (int) $this->db->get_affected_rows() === 1;
    }

    /**
     * Delete invoice by ID
     * @param int $invoiceId
     * @return bool
     */
    public function deleteInvoice(int $invoiceId): bool
    {
        return (bool) $this->db->query(
            "DELETE FROM " . USERPREFIX . "_billing_invoice 
             WHERE invoice_id = " . intval($invoiceId)
        );
    }

    /**
     * Create refund request
     * @param string $username
     * @param float $amount
     * @param float $commission
     * @param string $requisites
     * @return int
     */
    public function createRefund(
        string $username,
        float $amount,
        float $commission,
        string $requisites
    ): int {
        $username = $this->sanitize($username);
        $requisites = $this->sanitize($requisites);
        $amount = $this->sanitizeNumeric($amount);
        $commission = $this->sanitizeNumeric($commission);

        $this->db->query(
            "INSERT INTO " . USERPREFIX . "_billing_refund
             (refund_date, refund_user, refund_summa, refund_commission, refund_requisites)
             VALUES
             ('{$this->currentTime}', '{$username}', '{$amount}', 
              '{$commission}', '{$requisites}')"
        );

        return $this->db->insert_id();
    }

    /**
     * Set WHERE conditions
     * @param array $conditions
     * @return $this
     */
    public function where(array $conditions = []): self
    {
        $this->where = '';

        foreach ($conditions as $field => $value)
        {
            $value = trim($value);

            if ($value === '')
            {
                continue;
            }

            $value = $this->sanitize($value);
            $condition = str_replace('{s}', $value, $field);

            if (empty($this->where))
            {
                $this->where = "WHERE {$condition}";
            }
            else
            {
                $this->where .= " AND {$condition}";
            }
        }

        return $this;
    }

    /**
     * Sanitize string value
     * @param $value
     * @param string $pattern
     * @return string
     */
    public function sanitize(&$value, string $pattern = self::PATTERN_DEFAULT): string
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->sanitize($item, $pattern);
            }
            return $value;
        }

        $value = trim((string) $value);

        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        $value = preg_replace('#\s+#', ' ', $value);

        return $this->db->safesql($value);
    }

    /**
     * Sanitize numeric value
     * @param $value
     * @return string
     */
    private function sanitizeNumeric($value): string
    {
        return $this->sanitize($value, self::PATTERN_NUMERIC);
    }

    /**
     * Prepare pagination parameters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function preparePagination(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage)); // Limit max per page to 100

        $offset = ($page - 1) * $perPage;

        return [$offset, $perPage];
    }

    /**
     * Prepare payer info for storage
     * @param $payerInfo
     * @return string
     */
    private function preparePayerInfo($payerInfo): string
    {
        if (is_array($payerInfo)) {
            array_walk_recursive($payerInfo, function (&$item) {
                $item = preg_replace('/[^ a-z&#;@а-яA-ZА-Я\d.]/ui', '', (string) $item);
            });
            return json_encode($payerInfo, JSON_UNESCAPED_UNICODE);
        }

        return $this->db->safesql((string) $payerInfo);
    }

    /**
     * Update invoice with coupon data
     * @param array $invoice
     * @param array $coupon
     * @return void
     */
    private function updateInvoiceWithCoupon(array $invoice, array $coupon): void
    {
        $payerData = !empty($invoice['invoice_payer_info'])
            ? \Billing\DevTools::decodeInfo($invoice['invoice_payer_info'])
            : [];

        $payerData['coupon'] = $coupon;

        $this->db->query(
            "UPDATE " . USERPREFIX . "_billing_invoice
             SET invoice_payer_info = '" . $this->db->safesql(json_encode($payerData, JSON_UNESCAPED_UNICODE)) . "'
             WHERE invoice_id = " . intval($invoice['invoice_id'])
        );
    }
}