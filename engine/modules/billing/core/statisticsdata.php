<?php
/**
 * DLE Billing — слой данных статистики
 *
 * @copyright Copyright (c) 2012-2026
 */

namespace Billing;

class StatisticsData
{
    public const SECTOR_DAY = 'D';
    public const SECTOR_MONTH = 'M';
    public const SECTOR_YEAR = 'Y';

    private const THRESHOLD_MONTH = 2678400;
    private const THRESHOLD_YEAR = 32140800;

    private object $db;
    private string $balanceField;
    private array $lang;
    private int $start;
    private int $end;
    private string $sector;

    private ?array $globalOverview = null;

    /** @var array<string, array> */
    private array $periodSummaryCache = [];

    public function __construct(object $db, string $balanceField, array $lang, int $start, int $end, ?string $sector = null)
    {
        $this->db = $db;
        $this->balanceField = Database::safeField($balanceField);
        $this->lang = $lang;
        $this->start = $start;
        $this->end = $end;
        $this->sector = $sector ?? self::detectSector($end - $start);
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getSector(): string
    {
        return $this->sector;
    }

    public static function detectSector(int $diff): string
    {
        return match (true) {
            $diff > self::THRESHOLD_YEAR => self::SECTOR_YEAR,
            $diff > self::THRESHOLD_MONTH => self::SECTOR_MONTH,
            default => self::SECTOR_DAY,
        };
    }

    public static function presetRange(string $preset): array
    {
        $todayEnd = strtotime('today 23:59:59');

        return match ($preset) {
            'week' => [strtotime('monday this week 00:00:00'), $todayEnd],
            'year' => [strtotime('January 1 00:00:00'), $todayEnd],
            'quarter' => [strtotime('-2 months', strtotime(date('Y-m-01 00:00:00'))), $todayEnd],
            default => [strtotime(date('Y-m-01 00:00:00')), strtotime(date('Y-m-t 23:59:59'))],
        };
    }

    public function getGlobalOverview(): array
    {
        if ($this->globalOverview !== null) {
            return $this->globalOverview;
        }

        $todayStart = mktime(0, 0, 0);
        $yesterdayStart = $todayStart - 86400;
        $field = $this->balanceField;

        $balance = $this->db->super_query(
            "SELECT COUNT(name) AS cnt, SUM({$field}) AS sum
             FROM " . USERPREFIX . "_users
             WHERE {$field} != 0"
        );

        $depositToday = $this->db->super_query(
            "SELECT COALESCE(SUM(invoice_get), 0) AS sum
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_paysys != 'balance' AND invoice_date_pay >= '{$todayStart}'"
        );

        $depositYesterday = $this->db->super_query(
            "SELECT COALESCE(SUM(invoice_get), 0) AS sum
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_paysys != 'balance'
               AND invoice_date_pay >= '{$yesterdayStart}'
               AND invoice_date_pay < '{$todayStart}'"
        );

        $refund = $this->db->super_query(
            "SELECT
                COALESCE(SUM(CASE WHEN refund_date_return != 0 THEN refund_summa ELSE 0 END), 0) AS done_sum,
                COALESCE(SUM(CASE WHEN refund_date_return != 0 THEN refund_commission ELSE 0 END), 0) AS done_comm,
                COALESCE(SUM(CASE WHEN refund_date_return = 0 THEN refund_summa ELSE 0 END), 0) AS wait_sum
             FROM " . USERPREFIX . "_billing_refund"
        );

        $invoice = $this->db->super_query(
            "SELECT
                COALESCE(SUM(CASE WHEN invoice_date_pay != 0 THEN invoice_get ELSE 0 END), 0) AS paid,
                COALESCE(SUM(CASE WHEN invoice_date_pay = 0 THEN invoice_get ELSE 0 END), 0) AS wait
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_paysys != 'balance'"
        );

        $transfer = $this->db->super_query(
            "SELECT COALESCE(SUM(history_plus), 0) AS plus, COALESCE(SUM(history_minus), 0) AS minus
             FROM " . USERPREFIX . "_billing_history
             WHERE history_plugin = 'transfer'"
        );

        return $this->globalOverview = [
            'balance_sum' => (float) ($balance['sum'] ?? 0),
            'balance_users' => (int) ($balance['cnt'] ?? 0),
            'deposit_today' => (float) ($depositToday['sum'] ?? 0),
            'deposit_yesterday' => (float) ($depositYesterday['sum'] ?? 0),
            'refund_done' => (float) ($refund['done_sum'] ?? 0),
            'refund_commission' => (float) ($refund['done_comm'] ?? 0),
            'refund_wait' => (float) ($refund['wait_sum'] ?? 0),
            'invoice_paid' => (float) ($invoice['paid'] ?? 0),
            'invoice_wait' => (float) ($invoice['wait'] ?? 0),
            'transfer_out' => (float) ($transfer['minus'] ?? 0),
            'transfer_in' => (float) ($transfer['plus'] ?? 0),
        ];
    }

    public function getPeriodSummary(?string $userName = null): array
    {
        $cacheKey = $userName ?? '_all';

        if (isset($this->periodSummaryCache[$cacheKey])) {
            return $this->periodSummaryCache[$cacheKey];
        }

        $userHistorySql = $this->userWhere($userName, 'history_user_name');
        $userInvoiceSql = $userName
            ? " AND invoice_user_name = '" . $this->db->safesql($userName) . "'"
            : " AND invoice_paysys != 'balance'";

        $row = $this->db->super_query(
            "SELECT
                COALESCE(SUM(history_plus), 0) AS plus,
                COALESCE(SUM(history_minus), 0) AS minus,
                COUNT(*) AS ops
             FROM " . USERPREFIX . "_billing_history
             WHERE history_date >= '{$this->start}' AND history_date <= '{$this->end}'{$userHistorySql}"
        );

        $deposits = $this->db->super_query(
            "SELECT COALESCE(SUM(invoice_get), 0) AS sum, COUNT(*) AS cnt
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_date_pay != 0
               AND invoice_date_pay >= '{$this->start}'
               AND invoice_date_pay <= '{$this->end}'{$userInvoiceSql}"
        );

        $plus = (float) ($row['plus'] ?? 0);
        $minus = (float) ($row['minus'] ?? 0);

        return $this->periodSummaryCache[$cacheKey] = [
            'plus' => $plus,
            'minus' => $minus,
            'net' => $plus - $minus,
            'ops' => (int) ($row['ops'] ?? 0),
            'deposits_sum' => (float) ($deposits['sum'] ?? 0),
            'deposits_cnt' => (int) ($deposits['cnt'] ?? 0),
        ];
    }

    public function getFlowSeries(?string $userName = null): array
    {
        $userSql = $this->userWhere($userName, 'history_user_name');

        return $this->queryTimeSeries(
            USERPREFIX . '_billing_history',
            'history_date',
            'COALESCE(SUM(history_plus), 0) AS plus, COALESCE(SUM(history_minus), 0) AS minus',
            "history_date >= '{$this->start}' AND history_date <= '{$this->end}'{$userSql}",
            true
        );
    }

    public function getInvoiceConversion(?int $expireBefore = null, ?string $userName = null): array
    {
        $userSql = $this->invoiceUserWhere($userName);
        $expiredExpr = $this->expiredCountExpr($expireBefore);

        $row = $this->db->super_query(
            "SELECT
                COUNT(*) AS created,
                SUM(CASE WHEN invoice_date_pay != 0 THEN 1 ELSE 0 END) AS paid,
                SUM(CASE WHEN invoice_date_pay = 0 THEN 1 ELSE 0 END) AS unpaid,
                {$expiredExpr} AS expired,
                COALESCE(SUM(invoice_get), 0) AS created_sum,
                COALESCE(SUM(CASE WHEN invoice_date_pay != 0 THEN invoice_get ELSE 0 END), 0) AS paid_sum,
                COALESCE(SUM(CASE WHEN invoice_date_pay = 0 THEN invoice_get ELSE 0 END), 0) AS unpaid_sum,
                SUM(CASE WHEN invoice_paysys = '' THEN 1 ELSE 0 END) AS no_paysys
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_date_creat >= '{$this->start}'
               AND invoice_date_creat <= '{$this->end}'
               AND invoice_paysys != 'balance'{$userSql}"
        );

        $created = (int) ($row['created'] ?? 0);
        $paid = (int) ($row['paid'] ?? 0);
        $unpaid = (int) ($row['unpaid'] ?? 0);
        $expired = (int) ($row['expired'] ?? 0);

        return [
            'created' => $created,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'expired' => $expired,
            'waiting' => max(0, $unpaid - $expired),
            'no_paysys' => (int) ($row['no_paysys'] ?? 0),
            'created_sum' => (float) ($row['created_sum'] ?? 0),
            'paid_sum' => (float) ($row['paid_sum'] ?? 0),
            'unpaid_sum' => (float) ($row['unpaid_sum'] ?? 0),
            'conversion_rate' => $created > 0 ? round($paid * 100 / $created, 1) : 0.0,
            'dropoff_rate' => $created > 0 ? round(($created - $paid) * 100 / $created, 1) : 0.0,
        ];
    }

    /**
     * @return array<string, array<string, float|int|string>>
     */
    public function getInvoiceConversionByPaysys(?int $expireBefore = null, ?string $userName = null): array
    {
        $userSql = $this->invoiceUserWhere($userName);
        $expiredExpr = $this->expiredCountExpr($expireBefore);

        $this->db->query(
            "SELECT invoice_paysys,
                    COUNT(*) AS created,
                    SUM(CASE WHEN invoice_date_pay != 0 THEN 1 ELSE 0 END) AS paid,
                    SUM(CASE WHEN invoice_date_pay = 0 THEN 1 ELSE 0 END) AS unpaid,
                    {$expiredExpr} AS expired,
                    COALESCE(SUM(invoice_get), 0) AS created_sum,
                    COALESCE(SUM(CASE WHEN invoice_date_pay != 0 THEN invoice_get ELSE 0 END), 0) AS paid_sum,
                    COALESCE(SUM(CASE WHEN invoice_date_pay = 0 THEN invoice_get ELSE 0 END), 0) AS unpaid_sum
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_date_creat >= '{$this->start}'
               AND invoice_date_creat <= '{$this->end}'
               AND invoice_paysys != 'balance'{$userSql}
             GROUP BY invoice_paysys
             ORDER BY created DESC"
        );

        $data = [];

        while ($row = $this->db->get_row()) {
            $key = (string) ($row['invoice_paysys'] ?? '');
            $created = (int) ($row['created'] ?? 0);
            $paid = (int) ($row['paid'] ?? 0);
            $unpaid = (int) ($row['unpaid'] ?? 0);
            $expired = (int) ($row['expired'] ?? 0);

            $data[$key] = [
                'paysys' => $key,
                'created' => $created,
                'paid' => $paid,
                'unpaid' => $unpaid,
                'expired' => $expired,
                'waiting' => max(0, $unpaid - $expired),
                'created_sum' => (float) ($row['created_sum'] ?? 0),
                'paid_sum' => (float) ($row['paid_sum'] ?? 0),
                'unpaid_sum' => (float) ($row['unpaid_sum'] ?? 0),
                'conversion_rate' => $created > 0 ? round($paid * 100 / $created, 1) : 0.0,
                'dropoff_rate' => $created > 0 ? round(($created - $paid) * 100 / $created, 1) : 0.0,
            ];
        }

        uasort(
            $data,
            static fn(array $a, array $b) => $b['dropoff_rate'] <=> $a['dropoff_rate'] ?: $b['created'] <=> $a['created']
        );

        return $data;
    }

    public function getInvoiceConversionSeries(?string $userName = null): array
    {
        $userSql = $this->invoiceUserWhere($userName);
        [$groupSelect, $groupBy] = $this->groupParts('invoice_date_creat');

        $this->db->query(
            "SELECT {$groupSelect},
                    COUNT(*) AS created,
                    SUM(CASE WHEN invoice_date_pay != 0 THEN 1 ELSE 0 END) AS paid
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_date_creat >= '{$this->start}'
               AND invoice_date_creat <= '{$this->end}'
               AND invoice_paysys != 'balance'{$userSql}
             GROUP BY {$groupBy}
             ORDER BY {$groupBy}"
        );

        $dates = [];
        $created = [];
        $paid = [];
        $conversion = [];

        while ($row = $this->db->get_row()) {
            $dates[] = $this->formatLabel($row);
            $createdCount = (int) ($row['created'] ?? 0);
            $paidCount = (int) ($row['paid'] ?? 0);

            $created[] = $createdCount;
            $paid[] = $paidCount;
            $conversion[] = $createdCount > 0 ? round($paidCount * 100 / $createdCount, 1) : 0.0;
        }

        return [
            'dates' => $dates,
            'created' => $created,
            'paid' => $paid,
            'conversion' => $conversion,
        ];
    }

    public function getBillingByPaysys(?string $userName = null): array
    {
        $userSql = $userName
            ? " AND invoice_user_name = '" . $this->db->safesql($userName) . "'"
            : " AND invoice_paysys != 'balance'";

        $this->db->query(
            "SELECT invoice_paysys,
                    SUM(CASE WHEN invoice_date_pay != 0 THEN 1 ELSE 0 END) AS ok_rows,
                    SUM(CASE WHEN invoice_date_pay != 0 THEN invoice_get ELSE 0 END) AS ok_get,
                    SUM(CASE WHEN invoice_date_pay = 0 THEN 1 ELSE 0 END) AS wait_rows,
                    SUM(CASE WHEN invoice_date_pay = 0 THEN invoice_get ELSE 0 END) AS wait_get
             FROM " . USERPREFIX . "_billing_invoice
             WHERE invoice_date_creat >= '{$this->start}' AND invoice_date_creat <= '{$this->end}'{$userSql}
             GROUP BY invoice_paysys"
        );

        $data = [];

        while ($row = $this->db->get_row()) {
            $key = (string) ($row['invoice_paysys'] ?? '');
            $data[$key] = [
                'ok_rows' => (int) ($row['ok_rows'] ?? 0),
                'ok_get' => (float) ($row['ok_get'] ?? 0),
                'wait_rows' => (int) ($row['wait_rows'] ?? 0),
                'wait_get' => (float) ($row['wait_get'] ?? 0),
            ];
        }

        return $data;
    }

    public function getDepositSeries(?string $userName = null): array
    {
        $extra = $userName
            ? " AND invoice_user_name = '" . $this->db->safesql($userName) . "'"
            : " AND invoice_paysys != 'balance'";

        return $this->queryTimeSeries(
            USERPREFIX . '_billing_invoice',
            'invoice_date_pay',
            'COALESCE(SUM(invoice_get), 0) AS sum',
            "invoice_date_pay >= '{$this->start}' AND invoice_date_pay <= '{$this->end}' AND invoice_date_pay != 0{$extra}",
            false
        );
    }

    public function getPluginBreakdown(string $mode, ?string $userName = null): array
    {
        $userSql = $this->userWhere($userName, 'history_user_name');

        if ($mode === 'plus') {
            $where = 'history_minus = 0 AND history_plus > 0';
            $sumExpr = 'history_plus';
        } else {
            $where = 'history_minus > 0';
            $sumExpr = 'history_minus';
        }

        $this->db->query(
            "SELECT history_plugin, COUNT(*) AS rows_cnt, COALESCE(SUM({$sumExpr}), 0) AS pay
             FROM " . USERPREFIX . "_billing_history
             WHERE {$where}
               AND history_date >= '{$this->start}' AND history_date <= '{$this->end}'{$userSql}
             GROUP BY history_plugin
             ORDER BY pay DESC
             LIMIT 12"
        );

        $list = [];

        while ($row = $this->db->get_row()) {
            $list[] = [
                'plugin' => (string) ($row['history_plugin'] ?? ''),
                'rows' => (int) ($row['rows_cnt'] ?? 0),
                'pay' => (float) ($row['pay'] ?? 0),
            ];
        }

        return $list;
    }

    public function getUserRefundWait(string $userName): float
    {
        $name = $this->db->safesql($userName);

        $row = $this->db->super_query(
            "SELECT COALESCE(SUM(refund_summa), 0) AS sum
             FROM " . USERPREFIX . "_billing_refund
             WHERE refund_date_return = 0 AND refund_user = '{$name}'"
        );

        return (float) ($row['sum'] ?? 0);
    }

    public function formatLabel(array $row): string
    {
        return match ($this->sector) {
            self::SECTOR_DAY => ($row['D'] ?? '') . ' ' . ($this->lang['months'][(int) ($row['M'] ?? 0)] ?? ''),
            self::SECTOR_MONTH => $this->lang['months_full'][(int) ($row['M'] ?? 0)] ?? '',
            default => (string) ($row['Y'] ?? ''),
        };
    }

    private function userWhere(?string $userName, string $column = 'history_user_name'): string
    {
        if (!$userName) {
            return '';
        }

        return " AND {$column} = '" . $this->db->safesql($userName) . "'";
    }

    private function invoiceUserWhere(?string $userName): string
    {
        if (!$userName) {
            return '';
        }

        return " AND invoice_user_name = '" . $this->db->safesql($userName) . "'";
    }

    private function expiredCountExpr(?int $expireBefore): string
    {
        if ($expireBefore === null) {
            return '0';
        }

        return "SUM(CASE WHEN invoice_date_pay = 0 AND invoice_date_creat < {$expireBefore} THEN 1 ELSE 0 END)";
    }

    /**
     * @return array{dates: string[], values: float[]}|array{dates: string[], plus: float[], minus: float[]}
     */
    private function queryTimeSeries(string $table, string $dateColumn, string $selectAgg, string $where, bool $dual): array
    {
        [$groupSelect, $groupBy] = $this->groupParts($dateColumn);

        $this->db->query(
            "SELECT {$groupSelect}, {$selectAgg}
             FROM {$table}
             WHERE {$where}
             GROUP BY {$groupBy}
             ORDER BY {$groupBy}"
        );

        $dates = [];
        $values = [];
        $plus = [];
        $minus = [];

        while ($row = $this->db->get_row()) {
            $dates[] = $this->formatLabel($row);

            if ($dual) {
                $plus[] = (float) ($row['plus'] ?? 0);
                $minus[] = (float) ($row['minus'] ?? 0);
            } else {
                $values[] = (float) ($row['sum'] ?? 0);
            }
        }

        return $dual
            ? ['dates' => $dates, 'plus' => $plus, 'minus' => $minus]
            : ['dates' => $dates, 'values' => $values];
    }

    private function groupParts(string $dateColumn): array
    {
        $dt = "FROM_UNIXTIME({$dateColumn})";

        return match ($this->sector) {
            self::SECTOR_YEAR => [
                "YEAR({$dt}) AS Y, 1 AS M, 1 AS D",
                "YEAR({$dt})",
            ],
            self::SECTOR_MONTH => [
                "YEAR({$dt}) AS Y, MONTH({$dt}) AS M, 1 AS D",
                "YEAR({$dt}), MONTH({$dt})",
            ],
            default => [
                "YEAR({$dt}) AS Y, MONTH({$dt}) AS M, DAY({$dt}) AS D",
                "YEAR({$dt}), MONTH({$dt}), DAY({$dt})",
            ],
        };
    }
}
