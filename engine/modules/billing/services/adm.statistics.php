<?php
/**
 * DLE Billing — статистика (админ)
 *
 * @copyright Copyright (c) 2012-2026
 */

namespace Billing\Services\Admin;

use Billing\Api\Balance;
use Billing\Dashboard;
use Billing\StatisticsData;

class Statistics
{
    public Dashboard $Dashboard;

    private ?StatisticsData $data = null;
    private int $chartCounter = 0;
    private string $currentTab = '';
    private string $periodPreset = 'month';

    private function resolvePeriod(): void
    {
        if (isset($_POST['sort'])) {
            $this->Dashboard->CheckHash($_POST['user_hash'] ?? '');

            $start = $this->parseDate($_POST['date_edit_start'] ?? '', strtotime(date('Y-m-01')));
            $end = $this->parseDate($_POST['date_edit_end'] ?? '', strtotime(date('Y-m-t 23:59:59')), true);
            $tab = preg_replace('/[^a-z]/', '', $_GET['m'] ?? '');

            header(
                'Location: ?mod=billing&c=statistics'
                . ($tab ? '&m=' . $tab : '')
                . '&period=custom&from=' . urlencode(date('Y-m-d', $start))
                . '&to=' . urlencode(date('Y-m-d', $end))
            );
            exit;
        }

        $preset = preg_replace('/[^a-z]/', '', $_GET['period'] ?? 'month') ?: 'month';

        if (!empty($_GET['from']) && !empty($_GET['to'])) {
            $start = $this->parseDate($_GET['from'], strtotime(date('Y-m-01')));
            $end = $this->parseDate($_GET['to'], strtotime(date('Y-m-t 23:59:59')), true);
            $preset = 'custom';
        } elseif ($preset === 'custom') {
            $start = strtotime(date('Y-m-01'));
            $end = strtotime(date('Y-m-t 23:59:59'));
        } else {
            [$start, $end] = StatisticsData::presetRange($preset);
        }

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        $this->periodPreset = $preset;
        $this->currentTab = preg_replace('/[^a-z]/', '', $_GET['m'] ?? '') ?: '';

        $this->data = new StatisticsData(
            $this->Dashboard->LQuery->db,
            $this->Dashboard->LQuery->balanceField,
            $this->Dashboard->lang,
            $start,
            $end
        );
    }

    private function parseDate(string $date, int $default, bool $endOfDay = false): int
    {
        if (!$date) {
            return $default;
        }

        $ts = strtotime($date);

        return $endOfDay ? strtotime('23:59:59', $ts) : $ts;
    }

    private function data(): StatisticsData
    {
        if ($this->data === null) {
            $this->resolvePeriod();
        }

        return $this->data;
    }

    private function formatMoney(float $amount): string
    {
        return number_format(Balance::Init()->Convert($amount), 2, '.', ' ')
            . ' ' . Balance::Init()->Declension($amount);
    }

    private function percentChange(float $current, float $previous): string
    {
        if ($current == 0.0 && $previous == 0.0) {
            return '';
        }

        $base = $previous > 0 ? $previous : ($current ?: 1);
        $percent = (int) round(($current - $previous) * 100 / $base);

        if ($percent === 0) {
            return '';
        }

        $symbol = $percent > 0 ? '▲' : '▼';
        $color = $percent > 0 ? '#2ecc71' : '#e74c3c';
        $tooltip = htmlspecialchars(sprintf(
            $this->Dashboard->lang['statistics_dashboard_yesterday_up'],
            Balance::Init()->Convert($current),
            Balance::Init()->Declension($current),
            Balance::Init()->Convert($previous),
            Balance::Init()->Declension($previous)
        ));

        return "<span class=\"billing-stat-trend tip\" style=\"color:{$color}\" title=\"{$tooltip}\">{$symbol} {$percent}%</span>";
    }

    private function pageShell(string $content, string $sidebarExtra = ''): string
    {
        return <<<HTML
            <div class="row billing-stat-layout">
                <div class="col-md-3">{$this->renderSidebar($sidebarExtra)}</div>
                <div class="col-md-9">{$content}</div>
            </div>
HTML;
    }

    private function tabUrl(string $tab, ?string $period = null): string
    {
        $period = $period ?? $this->periodPreset;
        $url = '?mod=billing&c=statistics';

        if ($tab !== '') {
            $url .= '&m=' . $tab;
        }

        if ($period !== 'month') {
            $url .= '&period=' . $period;
        }

        if ($period === 'custom') {
            $url .= '&from=' . urlencode(date('Y-m-d', $this->data()->getStart()));
            $url .= '&to=' . urlencode(date('Y-m-d', $this->data()->getEnd()));
        }

        return $url;
    }

    private function renderSidebar(string $extra = ''): string
    {
        $presets = [
            'week' => $this->Dashboard->lang['statistics_period_week'],
            'month' => $this->Dashboard->lang['statistics_period_month'],
            'quarter' => $this->Dashboard->lang['statistics_period_quarter'],
            'year' => $this->Dashboard->lang['statistics_period_year'],
        ];

        $presetLinks = '';

        foreach ($presets as $key => $label) {
            $active = $this->periodPreset === $key ? ' billing-stat-preset--active' : '';
            $presetLinks .= '<a class="billing-stat-preset' . $active . '" href="' . $this->tabUrl($this->currentTab, $key) . '">' . $label . '</a>';
        }

        $from = date('Y-m-d', $this->data()->getStart());
        $to = date('Y-m-d', $this->data()->getEnd());
        $calendar = '<table width="100%"><tr>';
        $calendar .= '<td>' . $this->Dashboard->MakeCalendar('date_edit_start', $from, 'form-control') . '</td>';
        $calendar .= '<td> — </td>';
        $calendar .= '<td>' . $this->Dashboard->MakeCalendar('date_edit_end', $to, 'form-control') . '</td>';
        $calendar .= '<input type="hidden" name="user_hash" value="' . htmlspecialchars($this->Dashboard->hash) . '">';
        $calendar .= '<td><button class="btn bg-teal btn-sm" name="sort" type="submit" title="' . htmlspecialchars($this->Dashboard->lang['statistics_show']) . '"><i class="fa fa-filter"></i></button></td>';
        $calendar .= '</tr></table>';

        $periodHint = sprintf(
            $this->Dashboard->lang['statistics_period_hint'],
            date('d.m.Y', $this->data()->getStart()),
            date('d.m.Y', $this->data()->getEnd())
        );

        $menu = $this->buildMenuHtml([
            '' => $this->Dashboard->lang['statistics_7'],
            'billings' => $this->Dashboard->lang['statistics_2_title'],
            'plugins' => $this->Dashboard->lang['statistics_3_title'],
            'users' => $this->Dashboard->lang['statistics_4_title'],
            'clean' => $this->Dashboard->lang['statistics_5'],
        ]);

        return <<<HTML
            <div class="panel panel-default billing-stat-panel">
                <div class="panel-heading">{$this->Dashboard->lang['statistics_interval']}</div>
                <div class="panel-body">
                    <div class="billing-stat-presets">{$presetLinks}</div>
                    <form method="post" class="billing-stat-range-form">{$calendar}</form>
                    <p class="billing-stat-period-hint text-muted">{$periodHint}</p>
                </div>
            </div>
            {$extra}
            <div class="navbar navbar-default navbar-component navbar-xs billing-stat-nav">
                <ul class="nav navbar-nav billing-stat-menu">{$menu}</ul>
            </div>
            {$this->renderSidebarSummary()}
HTML;
    }

    private function buildMenuHtml(array $menu): string
    {
        $html = '';

        foreach ($menu as $tag => $name) {
            $active = $tag === $this->currentTab ? ' class="active"' : '';
            $html .= '<li' . $active . '><a href="' . $this->tabUrl($tag) . '">' . $name . '</a></li>';
        }

        return $html;
    }

    private function renderSidebarSummary(): string
    {
        $global = $this->data()->getGlobalOverview();
        $period = $this->data()->getPeriodSummary();
        $trend = $this->percentChange($global['deposit_today'], $global['deposit_yesterday']);

        return <<<HTML
            <div class="panel panel-body billing-stat-side-kpi">
                <div class="text-muted text-size-small">{$this->Dashboard->lang['statistics_dashboard_all']}</div>
                <div class="billing-stat-side-kpi__value">{$this->formatMoney($global['balance_sum'])}</div>
                <div class="text-muted">{$global['balance_users']} {$this->Dashboard->lang['statistics_users_count']}</div>
            </div>
            <div class="panel panel-body billing-stat-side-kpi">
                <div class="text-muted text-size-small">{$this->Dashboard->lang['statistics_period_net']}</div>
                <div class="billing-stat-side-kpi__value">{$this->formatMoney($period['net'])}</div>
                <div class="text-muted">{$period['ops']} {$this->Dashboard->lang['statistics_period_ops']}</div>
            </div>
            <div class="panel panel-body billing-stat-side-kpi">
                <div class="text-muted text-size-small">{$this->Dashboard->lang['statistics_dashboard_today']}</div>
                <div class="billing-stat-side-kpi__value">{$this->formatMoney($global['deposit_today'])} {$trend}</div>
            </div>
HTML;
    }

    private function renderKpiCards(array $cards): string
    {
        $html = '<div class="row billing-stat-kpis">';

        foreach ($cards as $card) {
            $sub = $card['sub'] ?? '';
            $link = !empty($card['link'])
                ? '<a href="' . $card['link'] . '" class="billing-stat-kpi__link">' . ($card['link_text'] ?? '') . '</a>'
                : '';

            $html .= <<<HTML
                <div class="col-sm-6 col-lg-3">
                    <div class="panel panel-body billing-stat-kpi">
                        <div class="billing-stat-kpi__icon"><i class="fa {$card['icon']}"></i></div>
                        <div class="billing-stat-kpi__body">
                            <div class="text-muted text-size-small">{$card['title']}</div>
                            <div class="billing-stat-kpi__value">{$card['value']}</div>
                            <div class="text-muted text-size-small">{$sub}</div>
                            {$link}
                        </div>
                    </div>
                </div>
HTML;
        }

        return $html . '</div>';
    }

    public function mainPage(): string
    {
        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $global = $this->data()->getGlobalOverview();
        $period = $this->data()->getPeriodSummary();
        $admin = $GLOBALS['PHP_SELF'] ?? '';

        $kpis = $this->renderKpiCards([
            [
                'icon' => 'fa-arrow-down',
                'title' => $this->Dashboard->lang['statistics_period_in'],
                'value' => $this->formatMoney($period['plus']),
                'sub' => $this->formatMoney($period['deposits_sum']) . ' · ' . $period['deposits_cnt'] . ' ' . $this->Dashboard->lang['statistics_invoice_short'],
            ],
            [
                'icon' => 'fa-arrow-up',
                'title' => $this->Dashboard->lang['statistics_period_out'],
                'value' => $this->formatMoney($period['minus']),
                'sub' => $this->Dashboard->lang['statistics_period_hint_ops'],
            ],
            [
                'icon' => 'fa-credit-card',
                'title' => $this->Dashboard->lang['statistics_dashboard_pay'],
                'value' => $this->formatMoney($global['invoice_paid']),
                'sub' => $this->Dashboard->lang['statistics_dashboard_to_pay'] . ': ' . $this->formatMoney($global['invoice_wait']),
                'link' => "{$admin}?mod=billing&c=invoice",
                'link_text' => $this->Dashboard->lang['statistics_dashboard_invoices'],
            ],
            [
                'icon' => 'fa-exchange',
                'title' => $this->Dashboard->lang['statistics_dashboard_refund'],
                'value' => $this->formatMoney($global['refund_done']),
                'sub' => $this->Dashboard->lang['statistics_dashboard_to_refund'] . ': ' . $this->formatMoney($global['refund_wait']),
                'link' => "{$admin}?mod=billing&c=refund",
                'link_text' => $this->Dashboard->lang['statistics_dashboard_all_refund'],
            ],
        ]);

        $chart = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_new_1_graf']);
        $chart .= $this->renderFlowChart();
        $chart .= $this->Dashboard->ThemeHeadClose();

        $globalRow = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_global_title']);
        $globalRow .= $this->renderGlobalTable($global);
        $globalRow .= $this->Dashboard->ThemeHeadClose();

        return $this->pageShell($kpis . $chart . $globalRow) . $this->Dashboard->ThemeEchoFoother();
    }

    /**
     * Совместимость: старые ссылки ?m=board
     */
    public function boardPage(): string
    {
        return $this->mainPage();
    }

    private function renderGlobalTable(array $global): string
    {
        $admin = $GLOBALS['PHP_SELF'] ?? '';
        $transferNet = ($global['transfer_out'] ?? 0) - ($global['transfer_in'] ?? 0);

        return <<<HTML
            <table class="table table-striped statistics_table">
                <tbody>
                    <tr>
                        <td>{$this->Dashboard->lang['statistics_dashboard_transfer']}</td>
                        <td><strong>{$this->formatMoney($global['transfer_out'])}</strong></td>
                        <td class="text-muted">{$this->Dashboard->lang['statistics_dashboard_comission']}: {$this->formatMoney($transferNet)}</td>
                        <td><a href="{$admin}?mod=billing&c=transactions">{$this->Dashboard->lang['statistics_dashboard_search_reansfer']}</a></td>
                    </tr>
                    <tr>
                        <td>{$this->Dashboard->lang['statistics_dashboard_all']}</td>
                        <td colspan="2"><strong>{$this->formatMoney($global['balance_sum'])}</strong> ({$global['balance_users']} {$this->Dashboard->lang['statistics_users_count']})</td>
                        <td><a href="{$admin}?mod=billing&c=users">{$this->Dashboard->lang['statistics_dashboard_search_user']}</a></td>
                    </tr>
                </tbody>
            </table>
HTML;
    }

    public function billingsPage(): string
    {
        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $up = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_2']);
        $up .= $this->renderBillingChart();
        $up .= $this->Dashboard->ThemeHeadClose();

        $exp = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_2_tab_2']);
        $exp .= $this->renderDepositChart();
        $exp .= $this->Dashboard->ThemeHeadClose();

        return $this->pageShell($up . $exp) . $this->Dashboard->ThemeEchoFoother();
    }

    public function pluginsPage(): string
    {
        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);
        $period = $this->data()->getPeriodSummary();
        $totalMinus = $period['minus'] ?: 1;
        $totalPlus = $period['plus'] ?: 1;

        $popular = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_3_tab2']);
        $popular .= '<div class="row"><div class="col-md-6">';
        $popular .= $this->renderPluginPie('minus', $totalMinus, $this->Dashboard->lang['statistics_d_title1'], $period['minus']);
        $popular .= '</div><div class="col-md-6">';
        $popular .= $this->renderPluginPie('plus', $totalPlus, $this->Dashboard->lang['statistics_d_title2'], $period['plus']);
        $popular .= '</div></div>';
        $popular .= $this->Dashboard->ThemeHeadClose();

        $costs = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_3']);
        $costs .= $this->renderFlowChart('column');
        $costs .= $this->Dashboard->ThemeHeadClose();

        return $this->pageShell($popular . $costs) . $this->Dashboard->ThemeEchoFoother();
    }

    public function usersPage(array $get): string
    {
        if (isset($_POST['search_btn'])) {
            $name = $this->Dashboard->LQuery->sanitize($_POST['search_user'] ?? '');
            $adminPath = $this->Dashboard->dle['admin_path'] ?? 'admin.php';
            header('Location: /' . $adminPath . '?mod=billing&c=statistics&m=users&p=user/' . rawurlencode($name));
            exit;
        }

        $user = $this->getUserInfo($get);

        if (empty($user['user_id'])) {
            return $this->Dashboard->ThemeMsg(
                $this->Dashboard->lang['error'],
                $this->Dashboard->lang['statistics_users_error'],
                '?mod=billing&c=statistics&m=users&p=user/' . rawurlencode($this->Dashboard->member_id['name'] ?? '')
            );
        }

        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $search = '<form method="post" class="billing-stat-user-search">';
        $search .= '<input type="hidden" name="user_hash" value="' . $this->Dashboard->hash . '">';
        $search .= '<input name="search_user" class="form-control" type="text" value="' . htmlspecialchars($user['name']) . '" required>';
        $search .= $this->Dashboard->MakeButton('search_btn', $this->Dashboard->lang['users_btn'], 'green');
        $search .= '</form>';

        $content = $this->renderUserHeader($user);
        $content .= $this->renderUserTabs($user);

        return $this->pageShell($content, $search) . $this->Dashboard->ThemeEchoFoother();
    }

    public function cleanPage(): string
    {
        if (isset($_POST['act'])) {
            $this->processCleanup();
        }

        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $content = $this->Dashboard->MakeMsgInfo(
            $this->Dashboard->lang['statistics_clean_info'],
            'icon-warning-sign',
            'red'
        );
        $content .= $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_5_title']);
        $content .= $this->renderCleanupForm();
        $content .= $this->Dashboard->ThemePadded(
            $this->Dashboard->MakeButton('act', $this->Dashboard->lang['act'], 'gold', true)
        );
        $content .= $this->Dashboard->ThemeHeadClose();

        return $this->pageShell($content) . $this->Dashboard->ThemeEchoFoother();
    }

    private function renderFlowChart(string $chartType = 'area', ?string $userName = null): string
    {
        $series = $this->data()->getFlowSeries($userName);

        if (empty($series['dates'])) {
            return $this->Dashboard->lang['statistics_null'];
        }

        return $this->renderChart([
            'type' => $chartType,
            'categories' => $series['dates'],
            'series' => [
                ['name' => $this->Dashboard->lang['statistics_graph_plus'], 'data' => $series['plus']],
                ['name' => $this->Dashboard->lang['statistics_graph_minus'], 'data' => $series['minus']],
            ],
            'stacking' => $chartType === 'area' ? 'normal' : null,
        ]);
    }

    private function renderDepositChart(?string $userName = null): string
    {
        $series = $this->data()->getDepositSeries($userName);

        if (empty($series['dates'])) {
            return $this->Dashboard->lang['statistics_null'];
        }

        return $this->renderChart([
            'type' => 'area',
            'categories' => $series['dates'],
            'series' => [
                ['name' => $this->Dashboard->lang['statistics_graph_get'], 'data' => $series['values']],
            ],
            'stacking' => 'normal',
        ]);
    }

    private function renderBillingChart(?string $userName = null): string
    {
        $billing = $this->data()->getBillingByPaysys($userName);
        $payments = $this->Dashboard->Payments();
        $payments[''] = ['title' => $this->Dashboard->lang['pay_not_payment']];

        if (empty($billing)) {
            return $this->Dashboard->lang['statistics_null'];
        }

        $categories = [];
        $paid = [];
        $wait = [];

        foreach ($billing as $code => $row) {
            $total = $row['ok_rows'] + $row['wait_rows'];
            $title = $payments[$code]['title'] ?? $code;
            $categories[] = $title . ' (' . $row['ok_rows'] . '/' . $total . ')';
            $paid[] = Balance::Init()->Convert($row['ok_get']);
            $wait[] = Balance::Init()->Convert($row['wait_get']);
        }

        return $this->renderChart([
            'type' => 'bar',
            'categories' => $categories,
            'series' => [
                ['name' => $this->Dashboard->lang['invoice_payok'], 'data' => $paid],
                ['name' => $this->Dashboard->lang['refund_wait'], 'data' => $wait],
            ],
        ]);
    }

    private function renderPluginPie(string $mode, float $total, string $title, float $sum, ?string $userName = null): string
    {
        $plugins = $this->Dashboard->Plugins();
        $plugins['pay'] = ['title' => $this->Dashboard->lang['statistics_pay']];
        $plugins['users'] = ['title' => $this->Dashboard->lang['statistics_admin']];

        $rows = $this->data()->getPluginBreakdown($mode, $userName);
        $points = [];

        foreach ($rows as $row) {
            if ($row['pay'] <= 0) {
                continue;
            }

            $name = $plugins[$row['plugin']]['title'] ?? $row['plugin'];
            $points[] = [
                'name' => $name . '<br>' . $this->formatMoney($row['pay']) . '<br>(' . $row['rows'] . $this->Dashboard->lang['statistics_d_per'] . ')',
                'y' => round($row['pay'] / ($total / 100), 2),
            ];
        }

        if (empty($points)) {
            return $this->Dashboard->lang['statistics_null'];
        }

        $subtitle = sprintf(
            $this->Dashboard->lang['statistics_d_subtitle'],
            Balance::Init()->Convert($sum),
            Balance::Init()->Declension($sum)
        );

        return $this->renderChart([
            'type' => 'pie',
            'title' => $title,
            'subtitle' => $subtitle,
            'series' => [['name' => $this->Dashboard->lang['statistics_d_end'], 'data' => $points]],
        ]);
    }

    private function renderChart(array $config): string
    {
        $this->chartCounter++;
        $id = 'billing_chart_' . $this->chartCounter;
        $payload = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
        $currency = Balance::Init()->Declension(10);
        $yTitle = $this->Dashboard->lang['history_summa'] . ' (' . $currency . ')';

        return <<<HTML
<div id="{$id}" class="billing-stat-chart"></div>
<script>
$(function () {
    BillingStatistics.render({$payload}, '{$id}', '{$yTitle}');
});
</script>
HTML;
    }

    private function getUserInfo(array $get): array
    {
        if (!empty($get['user'])) {
            return $this->Dashboard->LQuery->findUserByName(
                $this->Dashboard->LQuery->sanitize($get['user'])
            ) ?? [];
        }

        return $this->Dashboard->LQuery->findUserByName($this->Dashboard->member_id['name'] ?? '') ?? [];
    }

    private function renderUserHeader(array $user): string
    {
        $refundWait = $this->data()->getUserRefundWait($user['name']);

        return <<<HTML
            <div class="panel panel-default billing-stat-user-head">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-1">
                            <img src="{$this->Dashboard->Foto($user['foto'])}" class="billing-stat-user-avatar" alt="">
                        </div>
                        <div class="col-md-3">
                            {$this->Dashboard->ThemeInfoUser($user['name'])}<br>
                            <span class="text-muted">{$this->renderUserGroup($user)}</span>
                        </div>
                        <div class="col-md-2">
                            <div class="billing-stat-kpi__value">{$this->formatMoney($user[$this->Dashboard->LQuery->balanceField] ?? 0)}</div>
                            <div class="text-muted text-size-small">{$this->Dashboard->lang['statistics_users_balance']}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="billing-stat-kpi__value">{$this->formatMoney($refundWait)}</div>
                            <div class="text-muted text-size-small">{$this->Dashboard->lang['statistics_users_refund']}</div>
                        </div>
                        <div class="col-md-4 text-right">
                            <a href="/index.php?do=pm&doaction=newpm&username={$user['name']}" target="_blank" class="btn btn-default btn-sm tip" title="{$this->Dashboard->lang['statistics_users_9']}"><i class="fa fa-comments"></i></a>
                            <a href="/index.php?do=feedback&user={$user['user_id']}" target="_blank" class="btn btn-default btn-sm tip" title="{$this->Dashboard->lang['statistics_users_10']}"><i class="fa fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>
HTML;
    }

    private function renderUserTabs(array $user): string
    {
        $name = $user['name'];

        return $this->Dashboard->PanelTabs([
            [
                'id' => 'up',
                'title' => $this->Dashboard->lang['statistics_2'],
                'content' => $this->renderBillingChart($name),
            ],
            [
                'id' => 'lvl',
                'title' => $this->Dashboard->lang['statistics_2_tab_2'],
                'content' => $this->renderDepositChart($name),
            ],
            [
                'id' => 'costs',
                'title' => $this->Dashboard->lang['statistics_3_user'],
                'content' => $this->renderFlowChart('column', $name),
            ],
            [
                'id' => 'popular',
                'title' => $this->Dashboard->lang['statistics_3_tab2'],
                'content' => $this->renderUserPopularTab($name),
            ],
        ]);
    }

    private function renderUserPopularTab(string $userName): string
    {
        $period = $this->data()->getPeriodSummary($userName);

        $html = '<div class="row"><div class="col-md-6">';
        $html .= $this->renderPluginPie('minus', $period['minus'] ?: 1, $this->Dashboard->lang['statistics_d_title1'], $period['minus'], $userName);
        $html .= '</div><div class="col-md-6">';
        $html .= $this->renderPluginPie('plus', $period['plus'] ?: 1, $this->Dashboard->lang['statistics_d_title2'], $period['plus'], $userName);
        $html .= '</div></div>';

        return $html;
    }

    private function processCleanup(): void
    {
        if (empty($_POST['user_hash']) || $_POST['user_hash'] !== $this->Dashboard->hash) {
            die('Hacking attempt! User not found');
        }

        foreach ($_POST['clean_plugins'] ?? [] as $plugin) {
            $safe = $this->Dashboard->LQuery->db->safesql($plugin);
            $this->Dashboard->LQuery->db->query(
                "DELETE FROM " . USERPREFIX . "_billing_history WHERE history_plugin='{$safe}'"
            );
        }

        $invoiceQuery = match ($_POST['clear_invoice'] ?? '') {
            'all' => 'DELETE FROM ' . USERPREFIX . '_billing_invoice',
            'ok' => 'DELETE FROM ' . USERPREFIX . '_billing_invoice WHERE invoice_date_pay != 0',
            'no' => 'DELETE FROM ' . USERPREFIX . '_billing_invoice WHERE invoice_date_pay = 0',
            default => null,
        };

        if ($invoiceQuery) {
            $this->Dashboard->LQuery->db->query($invoiceQuery);
        }

        $refundQuery = match ($_POST['clear_refund'] ?? '') {
            'all' => 'DELETE FROM ' . USERPREFIX . '_billing_refund',
            'ok' => 'DELETE FROM ' . USERPREFIX . '_billing_refund WHERE refund_date_return != 0',
            'no' => 'DELETE FROM ' . USERPREFIX . '_billing_refund WHERE refund_date_return = 0',
            default => null,
        };

        if ($refundQuery) {
            $this->Dashboard->LQuery->db->query($refundQuery);
        }

        if (!empty($_POST['clear_balance'])) {
            $field = $this->Dashboard->LQuery->balanceField;
            $this->Dashboard->LQuery->db->query(
                'UPDATE ' . USERPREFIX . "_users SET {$field} = 0"
            );
        }

        $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['ok'],
            $this->Dashboard->lang['statistics_clean_1_ok']
        );
    }

    private function renderCleanupForm(): string
    {
        $plugins = $this->Dashboard->Plugins();
        $plugins['pay'] = ['title' => $this->Dashboard->lang['statistics_pay']];
        $plugins['users'] = ['title' => $this->Dashboard->lang['statistics_admin']];

        $html = '<div class="checkbox"><label><input type="checkbox" value="" onclick="BillingJS.checkAll(this)"> ';
        $html .= $this->Dashboard->lang['statistics_clean_2'] . '</label></div>';

        $this->Dashboard->LQuery->db->query(
            'SELECT history_plugin FROM ' . USERPREFIX . '_billing_history GROUP BY history_plugin'
        );

        while ($row = $this->Dashboard->LQuery->db->get_row()) {
            $title = $plugins[$row['history_plugin']]['title'] ?? $row['history_plugin'];
            $html .= '<div class="checkbox"><label>';
            $html .= '<input type="checkbox" name="clean_plugins[]" value="' . htmlspecialchars($row['history_plugin']) . '"> ';
            $html .= htmlspecialchars($title) . '</label></div>';
        }

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_3'],
            $this->Dashboard->lang['statistics_clean_3d'],
            $html
        );
        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_4'],
            $this->Dashboard->lang['statistics_clean_4d'],
            $this->Dashboard->GetSelect($this->Dashboard->lang['statistics_clean_invoice'], 'clear_invoice')
        );
        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_5'],
            $this->Dashboard->lang['statistics_clean_5d'],
            $this->Dashboard->GetSelect($this->Dashboard->lang['statistics_clean_refund'], 'clear_refund')
        );
        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_6'],
            $this->Dashboard->lang['statistics_clean_6d'],
            $this->Dashboard->GetSelect($this->Dashboard->lang['statistics_clean_balance'], 'clear_balance')
        );

        return '<input type="hidden" name="user_hash" value="' . $this->Dashboard->hash . '">' . $this->Dashboard->ThemeParserStr();
    }

    private function renderUserGroup(array $userInfo): string
    {
        global $user_group;

        $answer = ($userInfo['banned'] ?? '') === 'yes' ? $this->Dashboard->lang['statistics_users_2'] : '';

        if (!empty($user_group[$userInfo['user_group']]['time_limit'])) {
            if (!empty($userInfo['time_limit'])) {
                $date = langdate('j F Y H:i', $userInfo['time_limit']);
                $answer .= ' <span class="tip" title="' . $this->Dashboard->lang['statistics_users_21'] . ' ' . $date . '"><i class="fa fa-info-circle"></i></span>';
            } else {
                $answer .= $this->Dashboard->lang['statistics_users_22'];
            }
        }

        return ($user_group[$userInfo['user_group']]['group_name'] ?? '') . $answer;
    }
}
