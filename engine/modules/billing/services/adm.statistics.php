<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Services\Admin;

use Billing\Dashboard;
use Billing\Api\Balance;

class Statistics
{
    public Dashboard $Dashboard;

    private int $startTime;
    private int $endTime;
    private string $sectorTime;
    private array $queries;
    private int $chartCounter = 0;

    private const SECTOR_DAY = 'D';
    private const SECTOR_MONTH = 'M';
    private const SECTOR_YEAR = 'Y';

    private const TIME_SECTOR_THRESHOLD_YEAR = 32140800;
    private const TIME_SECTOR_THRESHOLD_MONTH = 2678400;

    public function __construct()
    {
        $this->initializeSession();
        $this->initializeTimePeriod();
        $this->queries = include MODULE_PATH . '/helpers/statistics.querys.php';
    }

    /**
     * Initialize session data
     */
    private function initializeSession(): void
    {
        session_start();

        if (isset($_POST['sort'])) {
            $this->updateSessionFromPost();
        }

        if (empty($_SESSION['billingTime']) && !isset($_POST['sort'])) {
            $_SESSION['billingTime'] = 'month';
        }
    }

    /**
     * Update session with POST data
     */
    private function updateSessionFromPost(): void
    {
        $_SESSION['billingTime'] = '';
        $_SESSION['billingTimeStart'] = $this->getTimestampFromDate($_POST['date_edit_start'] ?? '', strtotime(date("Y-m-01")));
        $_SESSION['billingTimeEnd'] = $this->getTimestampFromDate($_POST['date_edit_end'] ?? '', strtotime(date("Y-m-t")));
        $_SESSION['billingTimeSector'] = $this->determineSector(
            $_SESSION['billingTimeEnd'] - $_SESSION['billingTimeStart']
        );
    }

    /**
     * Get timestamp from date string
     */
    private function getTimestampFromDate(string $date, int $default): int
    {
        return $date ? strtotime($date) : $default;
    }

    /**
     * Determine time sector based on difference
     */
    private function determineSector(int $diff): string
    {
        return match (true) {
            $diff > self::TIME_SECTOR_THRESHOLD_YEAR => self::SECTOR_YEAR,
            $diff > self::TIME_SECTOR_THRESHOLD_MONTH => self::SECTOR_MONTH,
            default => self::SECTOR_DAY
        };
    }

    /**
     * Initialize time period from session
     */
    private function initializeTimePeriod(): void
    {
        $this->startTime = (int)($_SESSION['billingTimeStart'] ?? strtotime(date("Y-m-01")));
        $this->endTime = (int)($_SESSION['billingTimeEnd'] ?? strtotime(date("Y-m-t")));
        $this->sectorTime = in_array($_SESSION['billingTimeSector'] ?? '', [self::SECTOR_DAY, self::SECTOR_MONTH, self::SECTOR_YEAR])
            ? $_SESSION['billingTimeSector']
            : self::SECTOR_DAY;
    }

    /**
     * Navigation menu
     */
    private function renderLeftBar(string $filter = ''): string
    {
        $dateRange = $this->Dashboard->MakeCalendar("date_edit_start", date("Y-m-d", $this->startTime), 'border:none; width: 35%; text-align: right; color:white;margin-right:10px');
        $dateRange .= '- ' . $this->Dashboard->MakeCalendar("date_edit_end", date("Y-m-d", $this->endTime), 'border:none; width: 35%; color:white;margin-left:5px');
        $dateRange .= '<button class="btn bg-teal btn-sm" name="sort" type="submit"><i class="fa fa-filter"></i></button>';

        $menu = $this->buildMenuItems();
        $menuHtml = $this->buildMenuHtml($menu);

        return <<<HTML
            <div class="bg-primary-700" style="padding:10px; margin-bottom:10px">
                <form method="post">{$dateRange}</form>
            </div>
            {$filter}
            <div style="padding-right:0" class="navbar navbar-default navbar-component navbar-xs systemsettings">
                <ul class="nav navbar-nav visible-xs-block">
                    <li class="full-width text-center">
                        <a data-toggle="collapse" data-target="#navbar-filter" class="legitRipple">
                            <i class="fa fa-bars"></i>
                        </a>
                    </li>
                </ul>
                <div class="navbar-collapse collapse" id="navbar-filter">
                    <ul class="nav navbar-nav">{$menuHtml}</ul>
                </div>
            </div>
            {$this->renderStatsSummary()}
HTML;
    }

    /**
     * Build menu items
     */
    private function buildMenuItems(): array
    {
        return [
            '' => $this->Dashboard->lang['statistics_0'],
            'board' => $this->Dashboard->lang['statistics_7'],
            'billings' => $this->Dashboard->lang['statistics_2_title'],
            'plugins' => $this->Dashboard->lang['statistics_3_title'],
            'users' => $this->Dashboard->lang['statistics_4_title'],
            'clean' => $this->Dashboard->lang['statistics_5']
        ];
    }

    /**
     * Build menu HTML
     */
    private function buildMenuHtml(array $menu): string
    {
        $currentTab = $_GET['m'] ?? '';
        $html = '';

        foreach ($menu as $tag => $name) {
            $activeClass = $tag === $currentTab ? 'class="active"' : '';
            $url = '?mod=billing&c=statistics' . ($tag ? '&m=' . $tag : '');
            $title = $this->Dashboard->lang['statistics_menu'][$tag] ?? '';

            $html .= "<li {$activeClass} style=\"width:100%\">";
            $html .= "<a href=\"{$url}\" class=\"tip legitRipple\" title=\"{$title}\">{$name}</a>";
            $html .= "</li>";
        }

        return $html;
    }

    /**
     * Render statistics summary
     */
    private function renderStatsSummary(): string
    {
        $balanceAll = $this->Dashboard->LQuery->db->super_query(
            sprintf($this->queries['balance_all'], $this->Dashboard->config['fname'])
        );

        $balanceToday = $this->Dashboard->LQuery->db->super_query(
            sprintf($this->queries['balance_today'], mktime(0, 0, 0))
        );

        $balancePrev = $this->Dashboard->LQuery->db->super_query(
            sprintf($this->queries['balance_yesterday'], mktime(0, 0, 0) - 86400, mktime(0, 0, 0))
        );

        $percentChange = $this->calculatePercentChange($balanceToday['sum'] ?? 0, $balancePrev['sum'] ?? 0);
        $percentHtml = $this->formatPercentChange($percentChange, $balanceToday['sum'] ?? 0, $balancePrev['sum'] ?? 0);

        return <<<HTML
            <div class="bg-success" style="padding:10px; text-align:center; border-radius:5px; border:1px solid #ececec;">
                <h4 class="tip" data-placement="right" title="{$this->Dashboard->lang['statistics_dashboard_all']}">
                    <span style="float:left; padding-left:20px"><i class="fa fa-university"></i></span>
                    {$this->formatBalance($balanceAll['sum'] ?? 0)} {$percentHtml}
                </h4>
            </div>
HTML;
    }

    /**
     * Calculate percent change
     */
    private function calculatePercentChange(float $current, float $previous): int
    {
        if ($current < $previous) {
            $divisor = $previous ?: 1;
            return (int)(($current - $previous) * 100 / $divisor);
        }

        $divisor = $current ?: 1;
        return (int)(($current - $previous) * 100 / $divisor);
    }

    /**
     * Format percent change
     */
    private function formatPercentChange(int $percent, float $today, float $prev): string
    {
        if ($percent === 0) {
            return '';
        }

        $symbol = $percent > 0 ? '▲' : '▼';
        $color = $percent > 0 ? 'green' : 'red';

        $tooltip = sprintf(
            $this->Dashboard->lang['statistics_dashboard_yesterday_up'],
            Balance::Init()->Convert($today),
            Balance::Init()->Declension($today),
            Balance::Init()->Convert($prev),
            Balance::Init()->Declension($prev)
        );

        return "<font color=\"{$color}\" class=\"tip\" title=\"{$tooltip}\">{$symbol} {$percent}%</font>";
    }

    /**
     * Format balance
     */
    private function formatBalance(float $amount): string
    {
        return number_format(Balance::Init()->Convert($amount), 2, '.', ' ')
            . ' ' . Balance::Init()->Declension($amount);
    }

    /**
     * Main page
     */
    public function mainPage(): string
    {
        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $chartContent = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_new_1_graf']);
        $chartContent .= $this->renderMainChart();
        $chartContent .= $this->Dashboard->ThemeHeadClose();

        $content = <<<HTML
            <div class="row">
                <div class="col-md-3">{$this->renderLeftBar()}</div>
                <div class="col-md-9">{$chartContent}</div>
            </div>
HTML;

        return $content . $this->Dashboard->ThemeEchoFoother();
    }

    /**
     * Payments page
     */
    public function billingsPage(): string
    {
        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $upStats = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_2']);
        $upStats .= $this->renderPaymentsStatsUp(
            sprintf($this->queries['billing_up'], $this->startTime, $this->endTime),
            sprintf($this->queries['billing_up_null'], $this->startTime, $this->endTime)
        );
        $upStats .= $this->Dashboard->ThemeHeadClose();

        $expStats = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_2_tab_2']);
        $expStats .= $this->renderPaymentsExp(
            sprintf($this->queries['billing_exp'], $this->startTime, $this->endTime, $this->sectorTime)
        );
        $expStats .= $this->Dashboard->ThemeHeadClose();

        $content = <<<HTML
            <div class="row">
                <div class="col-md-3">{$this->renderLeftBar()}</div>
                <div class="col-md-9">{$upStats}{$expStats}</div>
            </div>
HTML;

        return $content . $this->Dashboard->ThemeEchoFoother();
    }

    /**
     * Board page
     */
    public function boardPage(): string
    {
        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $stats = $this->fetchBoardStatistics();
        $content = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_0_title']);
        $content .= $this->renderBoardStatistics($stats);
        $content .= $this->Dashboard->ThemeHeadClose();

        $html = <<<HTML
            <div class="row">
                <div class="col-md-3">{$this->renderLeftBar()}</div>
                <div class="col-md-9">{$content}</div>
            </div>
HTML;

        return $html . $this->Dashboard->ThemeEchoFoother();
    }

    /**
     * Fetch board statistics
     */
    private function fetchBoardStatistics(): array
    {
        return [
            'balanceAll' => $this->Dashboard->LQuery->db->super_query(
                sprintf($this->queries['balance_all'], $this->Dashboard->config['fname'])
            ),
            'balanceToday' => $this->Dashboard->LQuery->db->super_query(
                sprintf($this->queries['balance_today'], mktime(0, 0, 0))
            ),
            'refundAll' => $this->Dashboard->LQuery->db->super_query($this->queries['refund_all']),
            'refundWait' => $this->Dashboard->LQuery->db->super_query($this->queries['refund_wait']),
            'invoiceAll' => $this->Dashboard->LQuery->db->super_query($this->queries['pay_all']),
            'invoiceWait' => $this->Dashboard->LQuery->db->super_query($this->queries['pay_wait']),
            'transferAll' => $this->Dashboard->LQuery->db->super_query($this->queries['transfer']),
        ];
    }

    /**
     * Render board statistics
     */
    private function renderBoardStatistics(array $stats): string
    {
        $todayClass = empty($stats['balanceToday']['sum']) ? '' : 'money_plus';
        $refundClass = empty($stats['refundAll']['commission']) ? '' : 'money_plus';
        $invoiceClass = empty($stats['invoiceWait']['sum']) ? '' : 'money_plus';
        $transferDiff = ($stats['transferAll']['minus'] ?? 0) - ($stats['transferAll']['plus'] ?? 0);
        $transferClass = empty($transferDiff) ? '' : 'money_plus';

        return <<<HTML
            <table class="statistics_table">
                <tr>
                    <td valign="top">
                        {$this->Dashboard->lang['statistics_dashboard_all']}
                        <h4>{$this->formatBalance($stats['balanceAll']['sum'] ?? 0)}</h4>
                        <span class="{$todayClass}">{$this->formatBalance($stats['balanceToday']['sum'] ?? 0)}</span>
                        <br><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_today']}</span>
                    </td>
                    <td valign="top">
                        {$this->Dashboard->lang['statistics_dashboard_refund']}
                        <h4>{$this->formatBalance($stats['refundAll']['sum'] ?? 0)}</h4>
                        <span class="{$refundClass}">{$this->formatBalance($stats['refundAll']['commission'] ?? 0)}</span>
                        <br><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_comission']}</span>
                        <p>
                            <br><span>{$this->formatBalance($stats['refundWait']['sum'] ?? 0)}</span>
                            <br><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_to_refund']}</span>
                        </p>
                    </td>
                    <td valign="top">
                        {$this->Dashboard->lang['statistics_dashboard_pay']}
                        <h4>{$this->formatBalance($stats['invoiceAll']['sum'] ?? 0)}</h4>
                        <span class="{$invoiceClass}">{$this->formatBalance($stats['invoiceWait']['sum'] ?? 0)}</span>
                        <br><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_to_pay']}</span>
                    </td>
                    <td valign="top">
                        {$this->Dashboard->lang['statistics_dashboard_transfer']}
                        <h4>{$this->formatBalance($stats['transferAll']['minus'] ?? 0)}</h4>
                        <span class="{$transferClass}">{$this->formatBalance($transferDiff)}</span>
                        <br><span class="statistics_table_desc">{$this->Dashboard->lang['statistics_dashboard_comission']}</span>
                    </td>
                </tr>
                <tr>
                    <td><a href="{$GLOBALS['PHP_SELF']}?mod=billing&c=users">{$this->Dashboard->lang['statistics_dashboard_search_user']}</a></td>
                    <td><a href="{$GLOBALS['PHP_SELF']}?mod=billing&c=refund">{$this->Dashboard->lang['statistics_dashboard_all_refund']}</a></td>
                    <td><a href="{$GLOBALS['PHP_SELF']}?mod=billing&c=invoice">{$this->Dashboard->lang['statistics_dashboard_invoices']}</a></td>
                    <td><a href="{$GLOBALS['PHP_SELF']}?mod=billing&c=transactions">{$this->Dashboard->lang['statistics_dashboard_search_reansfer']}</a></td>
                </tr>
            </table><br>
HTML;
    }

    /**
     * Plugins page
     */
    public function pluginsPage(): string
    {
        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $mainStats = $this->Dashboard->LQuery->db->super_query(
            sprintf($this->queries['plugins_main'], $this->startTime, $this->endTime)
        );

        $popularStats = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_3_tab2']);
        $popularStats .= <<<HTML
            <div class="row">
                <div class="col-md-6">
                    {$this->renderPluginsPopulars(
            sprintf($this->queries['plugins_populars_minus'], $this->startTime, $this->endTime),
            ($mainStats['minus'] ?? 0) / 100,
            $this->Dashboard->lang['statistics_d_title1'],
            sprintf($this->Dashboard->lang['statistics_d_subtitle'],
                Balance::Init()->Convert($mainStats['minus'] ?? 0),
                Balance::Init()->Declension($mainStats['minus'] ?? 0)
            )
        )}
                </div>
                <div class="col-md-6">
                    {$this->renderPluginsPopulars(
            sprintf($this->queries['plugins_populars_plus'], $this->startTime, $this->endTime),
            ($mainStats['plus'] ?? 0) / 100,
            $this->Dashboard->lang['statistics_d_title2'],
            sprintf($this->Dashboard->lang['statistics_d_subtitle'],
                Balance::Init()->Convert($mainStats['plus'] ?? 0),
                Balance::Init()->Declension($mainStats['plus'] ?? 0)
            )
        )}
                </div>
            </div>
HTML;
        $popularStats .= $this->Dashboard->ThemeHeadClose();

        $costsStats = $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_3']);
        $costsStats .= $this->renderPluginsCosts(
            sprintf($this->queries['plugins_cost'], $this->startTime, $this->endTime, $this->sectorTime)
        );
        $costsStats .= $this->Dashboard->ThemeHeadClose();

        $content = <<<HTML
            <div class="row">
                <div class="col-md-3">{$this->renderLeftBar()}</div>
                <div class="col-md-9">{$popularStats}{$costsStats}</div>
            </div>
HTML;

        return $content . $this->Dashboard->ThemeEchoFoother();
    }

    /**
     * Users page
     */
    public function usersPage(array $get): string
    {
        if (isset($_POST['search_btn'])) {
            $this->redirectToUserSearch($_POST['search_user'] ?? '');
        }

        $user = $this->getUserInfo($get);
        if (empty($user['user_id'])) {
            return $this->showUserNotFoundError();
        }

        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);
        $content = $this->renderUserHeader($user);
        $content .= $this->renderUserTabs($user);

        $searchPanel = $this->renderUserSearchPanel($user['name'] ?? '');

        $html = <<<HTML
            <div class="row">
                <div class="col-md-3">{$this->renderLeftBar($searchPanel)}</div>
                <div class="col-md-9">{$content}</div>
            </div>
HTML;

        return $html;
    }

    /**
     * Redirect to user search
     */
    private function redirectToUserSearch(string $username): void
    {
        $adminPath = $this->Dashboard->dle['admin_path'] ?? '';
        $sanitized = $this->Dashboard->LQuery->sanitize($username);
        header("Location: /{$adminPath}?mod=billing&c=statistics&m=users&p=user/{$sanitized}");
        exit;
    }

    /**
     * Get user info
     */
    private function getUserInfo(array $get): array
    {
        if (!empty($get['user'])) {
            return $this->Dashboard->LQuery->findUserByName($this->Dashboard->LQuery->sanitize($get['user']));
        }

        return $this->Dashboard->LQuery->findUserByName($this->Dashboard->member_id['name'] ?? '');
    }

    /**
     * Show user not found error
     */
    private function showUserNotFoundError(): string
    {
        $adminPath = $GLOBALS['PHP_SELF'] ?? '';
        return $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['error'],
            $this->Dashboard->lang['statistics_users_error'],
            "{$adminPath}?mod=billing&c=statistics&m=users&p=user/{$this->Dashboard->member_id['name']}"
        );
    }

    /**
     * Render user header
     */
    private function renderUserHeader(array $user): string
    {
        $refundWait = $this->Dashboard->LQuery->db->super_query(
            sprintf($this->queries['users_refund'], $user['name'])
        );

        return <<<HTML
            <div class="row" style="padding:10px; padding-bottom:10px">
                <div class="col-md-1">
                    <img src="{$this->Dashboard->Foto($user['foto'])}" 
                         style="max-width:42px; border-radius:5px" 
                         title="{$user['name']}" alt="{$user['name']}">
                </div>
                <div class="col-md-3">
                    {$this->Dashboard->ThemeInfoUser($user['name'])}<br>({$this->renderUserGroup($user)})
                </div>
                <div class="col-md-3">
                    {$this->formatBalance($user[$this->Dashboard->config['fname']] ?? 0)}
                    <div style="margin:0;font-size:11px; color:#ccc">{$this->Dashboard->lang['statistics_users_balance']}</div>
                </div>
                <div class="col-md-3">
                    {$this->formatBalance($refundWait['sum'] ?? 0)}
                    <div style="margin:0;font-size:11px; color:#ccc">{$this->Dashboard->lang['statistics_users_refund']}</div>
                </div>
                <div class="col-md-2" style="padding-top:5px">
                    <a href="/index.php?do=pm&doaction=newpm&username={$user['name']}" 
                       target="_blank" class="tip" title="{$this->Dashboard->lang['statistics_users_9']}">
                        <i class="fa fa-comments" style="font-size:24px; margin-right:10px; color:#428bca"></i>
                    </a>
                    <a href="/index.php?do=feedback&user={$user['user_id']}" 
                       target="_blank" class="tip" title="{$this->Dashboard->lang['statistics_users_10']}">
                        <i class="fa fa-envelope" style="font-size:24px; color:#428bca"></i>
                    </a>
                </div>
            </div>
HTML;
    }

    /**
     * Render user tabs
     */
    private function renderUserTabs(array $user): string
    {
        $mainStats = $this->Dashboard->LQuery->db->super_query(
            sprintf($this->queries['users_plugins_main'], $this->startTime, $this->endTime, $user['name'])
        );

        $tabs = [
            [
                'id' => 'up',
                'title' => $this->Dashboard->lang['statistics_2'],
                'content' => $this->renderPaymentsStatsUp(
                    sprintf($this->queries['users_billing_up'], $this->startTime, $this->endTime, $user['name']),
                    sprintf($this->queries['users_billing_up_null'], $this->startTime, $this->endTime, $user['name'])
                )
            ],
            [
                'id' => 'lvl',
                'title' => $this->Dashboard->lang['statistics_2_tab_2'],
                'content' => $this->renderPaymentsExp(
                    sprintf($this->queries['users_billing_exp'], $this->startTime, $this->endTime, $user['name'], $this->sectorTime)
                )
            ],
            [
                'id' => 'costs',
                'title' => $this->Dashboard->lang['statistics_3_user'],
                'content' => $this->renderPluginsCosts(
                    sprintf($this->queries['users_plugins_cost'], $this->startTime, $this->endTime, $user['name'], $this->sectorTime)
                )
            ],
            [
                'id' => 'popular',
                'title' => $this->Dashboard->lang['statistics_3_tab2'],
                'content' => <<<HTML
                    <div class="row">
                        <div class="col-md-6">
                            {$this->renderPluginsPopulars(
                    sprintf($this->queries['users_plugins_populars_minus'], $this->startTime, $this->endTime, $user['name']),
                    ($mainStats['minus'] ?? 0) / 100,
                    $this->Dashboard->lang['statistics_d_title1'],
                    sprintf($this->Dashboard->lang['statistics_d_subtitle'],
                        Balance::Init()->Convert($mainStats['minus'] ?? 0),
                        Balance::Init()->Declension($mainStats['minus'] ?? 0)
                    )
                )}
                        </div>
                        <div class="col-md-6">
                            {$this->renderPluginsPopulars(
                    sprintf($this->queries['users_plugins_populars_plus'], $this->startTime, $this->endTime, $user['name']),
                    ($mainStats['plus'] ?? 0) / 100,
                    $this->Dashboard->lang['statistics_d_title2'],
                    sprintf($this->Dashboard->lang['statistics_d_subtitle'],
                        Balance::Init()->Convert($mainStats['plus'] ?? 0),
                        Balance::Init()->Declension($mainStats['plus'] ?? 0)
                    )
                )}
                        </div>
                    </div>
HTML
            ]
        ];

        return $this->Dashboard->PanelTabs($tabs);
    }

    /**
     * Render user search panel
     */
    private function renderUserSearchPanel(string $username): string
    {
        return <<<HTML
            <form method="post" style="text-align:center">
                {$this->Dashboard->MakeMsgInfo(
            '<input name="search_user" class="form-control" type="text" style="width:60%" value="' . htmlspecialchars($username) . '" required>' .
            $this->Dashboard->MakeButton("search_btn", $this->Dashboard->lang['users_btn'], "green"),
            "icon-user",
            "green"
        )}
            </form>
HTML;
    }

    /**
     * Clean page
     */
    public function cleanPage(): string
    {
        if (isset($_POST['act'])) {
            $this->processCleanup();
        }

        $this->Dashboard->ThemeEchoHeader($this->Dashboard->lang['menu_5']);

        $content = $this->Dashboard->MakeMsgInfo(
            $this->Dashboard->lang['statistics_clean_info'],
            "icon-warning-sign",
            "red"
        );

        $content .= $this->Dashboard->ThemeHeadStart($this->Dashboard->lang['statistics_5_title']);
        $content .= $this->renderCleanupForm();
        $content .= $this->Dashboard->ThemePadded(
            $this->Dashboard->MakeButton("act", $this->Dashboard->lang['act'], "gold", true)
        );
        $content .= $this->Dashboard->ThemeHeadClose();

        $html = <<<HTML
            <div class="row">
                <div class="col-md-3">{$this->renderLeftBar()}</div>
                <div class="col-md-9">{$content}</div>
            </div>
HTML;

        return $html . $this->Dashboard->ThemeEchoFoother();
    }

    /**
     * Process cleanup
     */
    private function processCleanup(): void
    {
        if (empty($_POST['user_hash']) || $_POST['user_hash'] !== $this->Dashboard->hash) {
            echo "Hacking attempt! User not found";
            exit;
        }

        $this->cleanupPlugins($_POST['clean_plugins'] ?? []);
        $this->cleanupInvoices($_POST['clear_invoice'] ?? '');
        $this->cleanupRefunds($_POST['clear_refund'] ?? '');
        $this->cleanupBalance(!empty($_POST['clear_balance']));

        $this->Dashboard->ThemeMsg(
            $this->Dashboard->lang['ok'],
            $this->Dashboard->lang['statistics_clean_1_ok']
        );
    }

    /**
     * Cleanup plugins
     */
    private function cleanupPlugins(array $plugins): void
    {
        foreach ($plugins as $plugin) {
            $safePlugin = $this->Dashboard->LQuery->db->safesql($plugin);
            $this->Dashboard->LQuery->db->super_query(
                "DELETE FROM " . USERPREFIX . "_billing_history WHERE history_plugin='{$safePlugin}'"
            );
        }
    }

    /**
     * Cleanup invoices
     */
    private function cleanupInvoices(string $type): void
    {
        $query = match ($type) {
            'all' => "DELETE FROM " . USERPREFIX . "_billing_invoice",
            'ok' => "DELETE FROM " . USERPREFIX . "_billing_invoice WHERE invoice_date_pay != 0",
            'no' => "DELETE FROM " . USERPREFIX . "_billing_invoice WHERE invoice_date_pay = 0",
            default => null
        };

        if ($query) {
            $this->Dashboard->LQuery->db->super_query($query);
        }
    }

    /**
     * Cleanup refunds
     */
    private function cleanupRefunds(string $type): void
    {
        $query = match ($type) {
            'all' => "DELETE FROM " . USERPREFIX . "_billing_refund",
            'ok' => "DELETE FROM " . USERPREFIX . "_billing_refund WHERE refund_date_return != 0",
            'no' => "DELETE FROM " . USERPREFIX . "_billing_refund WHERE refund_date_return = 0",
            default => null
        };

        if ($query) {
            $this->Dashboard->LQuery->db->super_query($query);
        }
    }

    /**
     * Cleanup balance
     */
    private function cleanupBalance(bool $clean): void
    {
        if ($clean) {
            $this->Dashboard->LQuery->db->query(
                "UPDATE " . USERPREFIX . "_users SET {$this->Dashboard->config['fname']} = 0"
            );
        }
    }

    /**
     * Render cleanup form
     */
    private function renderCleanupForm(): string
    {
        $plugins = $this->Dashboard->Plugins();
        $plugins['pay']['title'] = $this->Dashboard->lang['statistics_pay'];
        $plugins['users']['title'] = $this->Dashboard->lang['statistics_admin'];

        $pluginCheckboxes = $this->renderPluginCheckboxes($plugins);

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_3'],
            $this->Dashboard->lang['statistics_clean_3d'],
            $pluginCheckboxes
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_4'],
            $this->Dashboard->lang['statistics_clean_4d'],
            $this->Dashboard->GetSelect($this->Dashboard->lang['statistics_clean_invoice'], "clear_invoice")
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_5'],
            $this->Dashboard->lang['statistics_clean_5d'],
            $this->Dashboard->GetSelect($this->Dashboard->lang['statistics_clean_refund'], "clear_refund")
        );

        $this->Dashboard->ThemeAddStr(
            $this->Dashboard->lang['statistics_clean_6'],
            $this->Dashboard->lang['statistics_clean_6d'],
            $this->Dashboard->GetSelect($this->Dashboard->lang['statistics_clean_balance'], "clear_balance")
        );

        return $this->Dashboard->ThemeParserStr();
    }

    /**
     * Render plugin checkboxes
     */
    private function renderPluginCheckboxes(array $plugins): string
    {
        $html = '<div class="checkbox">';
        $html .= '<label><input type="checkbox" value="" onclick="BillingJS.checkAll(this)"> ';
        $html .= $this->Dashboard->lang['statistics_clean_2'] . '</label>';
        $html .= '</div>';

        $this->Dashboard->LQuery->db->query(
            "SELECT history_plugin FROM " . USERPREFIX . "_billing_history GROUP BY history_plugin"
        );

        while ($row = $this->Dashboard->LQuery->db->get_row()) {
            $title = $plugins[$row['history_plugin']]['title'] ?? $row['history_plugin'];
            $html .= '<div class="checkbox">';
            $html .= '<label>';
            $html .= '<input type="checkbox" name="clean_plugins[]" value="' . htmlspecialchars($row['history_plugin']) . '"> ';
            $html .= htmlspecialchars($title);
            $html .= '</label>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Render payments statistics (up)
     */
    private function renderPaymentsStatsUp(string $sql, string $sqlNull): string
    {
        $this->chartCounter++;

        $payments = $this->Dashboard->Payments();
        $payments[''] = ['title' => $this->Dashboard->lang['pay_not_payment']];

        $billingData = $this->fetchBillingData($sql, $sqlNull);

        if (empty($billingData)) {
            return $this->Dashboard->lang['statistics_null'];
        }

        [$names, $payData, $waitData] = $this->prepareChartData($billingData, $payments);

        return $this->renderBarChart($names, $payData, $waitData);
    }

    /**
     * Fetch billing data
     */
    private function fetchBillingData(string $sql, string $sqlNull): array
    {
        $data = [];

        $this->Dashboard->LQuery->db->query($sql);
        while ($row = $this->Dashboard->LQuery->db->get_row()) {
            $data[$row['invoice_paysys']]['ok_allids'] = (int)($row['rows'] ?? 0);
            $data[$row['invoice_paysys']]['ok_get'] = (float)($row['get'] ?? 0);
        }

        $this->Dashboard->LQuery->db->query($sqlNull);
        while ($row = $this->Dashboard->LQuery->db->get_row()) {
            $data[$row['invoice_paysys']]['wait_allids'] = (int)($row['rows'] ?? 0);
            $data[$row['invoice_paysys']]['wait_get'] = (float)($row['get'] ?? 0);
        }

        return $data;
    }

    /**
     * Prepare chart data
     */
    private function prepareChartData(array $data, array $payments): array
    {
        $names = [];
        $payData = [];
        $waitData = [];

        foreach ($data as $billName => $info) {
            $okIds = (int)($info['ok_allids'] ?? 0);
            $waitIds = (int)($info['wait_allids'] ?? 0);
            $total = $okIds + $waitIds;

            $title = $payments[$billName]['title'] ?? $billName;
            $names[] = "{$title} <br>({$okIds} {$this->Dashboard->lang['statistics_billings_invoices_0']} {$total} {$this->Dashboard->lang['statistics_billings_invoices_1']})";

            $payData[] = Balance::Init()->Convert($info['ok_get'] ?? 0);
            $waitData[] = Balance::Init()->Convert($info['wait_get'] ?? 0);
        }

        return [$names, $payData, $waitData];
    }

    /**
     * Render bar chart
     */
    private function renderBarChart(array $names, array $payData, array $waitData): string
    {
        $namesJson = json_encode($names);
        $payDataJson = json_encode($payData);
        $waitDataJson = json_encode($waitData);
        $counter = $this->chartCounter;
        $currency = Balance::Init()->Declension(10);

        return <<<HTML
<script>
$(function () {
    $('#container_{$counter}').highcharts({
        chart: { type: 'bar' },
        title: { text: '' },
        xAxis: {
            categories: {$namesJson},
            title: { text: null }
        },
        yAxis: {
            min: 0,
            title: {
                text: '{$this->Dashboard->lang['history_summa']} ({$currency})',
                align: 'high'
            },
            labels: { overflow: 'justify' }
        },
        plotOptions: {
            bar: {
                dataLabels: { enabled: true }
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: -40,
            y: 80,
            floating: true,
            borderWidth: 1,
            backgroundColor: '#FFFFFF',
            shadow: true
        },
        credits: { enabled: false },
        series: [{
            name: '{$this->Dashboard->lang['invoice_payok']}',
            data: {$payDataJson}
        }, {
            name: '{$this->Dashboard->lang['refund_wait']}',
            data: {$waitDataJson}
        }]
    });
});
</script>
<div id="container_{$counter}" style="min-width:310px; width:100%; height:400px; margin:0 auto"></div>
HTML;
    }

    /**
     * Render payments expenses
     */
    private function renderPaymentsExp(string $sql): string
    {
        $this->chartCounter++;

        [$dates, $values] = $this->fetchTimeSeriesData($sql);

        if (empty($dates)) {
            return $this->Dashboard->lang['statistics_null'];
        }

        return $this->renderAreaChart($dates, $values, $this->Dashboard->lang['statistics_graph_get']);
    }

    /**
     * Fetch time series data
     */
    private function fetchTimeSeriesData(string $sql): array
    {
        $dates = [];
        $values = [];

        $this->Dashboard->LQuery->db->query($sql);

        while ($row = $this->Dashboard->LQuery->db->get_row()) {
            $dates[] = $this->formatDate($row);
            $values[] = (float)($row['sum'] ?? 0);
        }

        return [$dates, $values];
    }

    /**
     * Format date based on sector
     */
    private function formatDate(array $row): string
    {
        return match ($this->sectorTime) {
            self::SECTOR_DAY => $row['D'] . ' ' . ($this->Dashboard->lang['months'][$row['M']] ?? ''),
            self::SECTOR_MONTH => $this->Dashboard->lang['months_full'][$row['M']] ?? '',
            default => (string)($row['Y'] ?? ''),
        };
    }

    /**
     * Render area chart
     */
    private function renderAreaChart(array $dates, array $values, string $name): string
    {
        $datesJson = json_encode($dates);
        $valuesJson = json_encode($values);
        $counter = $this->chartCounter;
        $currency = Balance::Init()->Declension(10);

        return <<<HTML
<script>
$(function () {
    $('#container_{$counter}').highcharts({
        chart: { type: 'area' },
        title: { text: '' },
        xAxis: {
            categories: {$datesJson},
            tickmarkPlacement: 'on',
            title: { enabled: false }
        },
        yAxis: {
            title: {
                text: '{$this->Dashboard->lang['history_summa']} ({$currency})'
            }
        },
        tooltip: {
            split: true,
            valueSuffix: ' ({$currency})'
        },
        plotOptions: {
            area: {
                stacking: 'normal',
                lineColor: '#666666',
                lineWidth: 1,
                marker: {
                    lineWidth: 1,
                    lineColor: '#666666'
                }
            }
        },
        series: [{
            name: '{$name}',
            data: {$valuesJson}
        }]
    });
});
</script>
<div id="container_{$counter}" style="height:400px; margin:10px"></div>
HTML;
    }

    /**
     * Render main chart
     */
    private function renderMainChart(): string
    {
        $this->chartCounter++;

        $dates = [];
        $plus = [];
        $minus = [];

        $this->Dashboard->LQuery->db->query(
            sprintf($this->queries['main'], $this->startTime, $this->endTime, $this->sectorTime)
        );

        while ($row = $this->Dashboard->LQuery->db->get_row()) {
            $dates[] = $this->formatDate($row);
            $plus[] = (float)($row['plus'] ?? 0);
            $minus[] = (float)($row['minus'] ?? 0);
        }

        if (empty($dates)) {
            return $this->Dashboard->lang['statistics_null'];
        }

        $datesJson = json_encode($dates);
        $plusJson = json_encode($plus);
        $minusJson = json_encode($minus);
        $counter = $this->chartCounter;
        $currency = Balance::Init()->Declension(10);

        return <<<HTML
<script>
$(function () {
    $('#container_{$counter}').highcharts({
        chart: { type: 'area' },
        title: { text: '' },
        xAxis: {
            categories: {$datesJson},
            tickmarkPlacement: 'on',
            title: { enabled: false }
        },
        yAxis: {
            title: {
                text: '{$this->Dashboard->lang['history_summa']} ({$currency})'
            }
        },
        tooltip: {
            split: true,
            valueSuffix: ' ({$currency})'
        },
        plotOptions: {
            area: {
                stacking: 'normal',
                lineColor: '#666666',
                lineWidth: 1,
                marker: {
                    lineWidth: 1,
                    lineColor: '#666666'
                }
            }
        },
        series: [{
            name: '{$this->Dashboard->lang['statistics_graph_plus']}',
            data: {$plusJson}
        }, {
            name: '{$this->Dashboard->lang['statistics_graph_minus']}',
            data: {$minusJson}
        }]
    });
});
</script>
<div id="container_{$counter}" style="min-width:310px; height:400px; margin:10px auto"></div>
HTML;
    }

    /**
     * Render plugins populars
     */
    private function renderPluginsPopulars(string $sql, float $onePercent, string $title, string $subtitle): string
    {
        $this->chartCounter++;

        $plugins = $this->Dashboard->Plugins();
        $plugins['pay']['title'] = $this->Dashboard->lang['statistics_pay'];
        $plugins['users']['title'] = $this->Dashboard->lang['statistics_admin'];

        $data = [];

        $this->Dashboard->LQuery->db->query($sql);

        while ($row = $this->Dashboard->LQuery->db->get_row()) {
            $name = $plugins[$row['history_plugin']]['title'] ?? $row['history_plugin'];
            $pay = (float)($row['pay'] ?? 0);
            $rows = (int)($row['rows'] ?? 0);

            $data[] = [
                'name' => "{$name}<br>{$pay} " . Balance::Init()->Declension($pay) . "<br>({$rows}" . $this->Dashboard->lang['statistics_d_per'] . ")",
                'y' => round($pay / $onePercent, 2)
            ];
        }

        $dataJson = json_encode($data);
        $counter = $this->chartCounter;

        return <<<HTML
<script>
$(function () {
    $('#container_{$counter}').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: { text: '{$title}' },
        subtitle: { text: '{$subtitle}' },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                    style: { color: 'black' },
                    connectorColor: 'silver'
                }
            }
        },
        series: [{
            name: '{$this->Dashboard->lang['statistics_d_end']}',
            data: {$dataJson}
        }]
    });
});
</script>
<div id="container_{$counter}" style="width:100%; margin:10px auto"></div>
HTML;
    }

    /**
     * Render plugins costs
     */
    private function renderPluginsCosts(string $sql): string
    {
        $this->chartCounter++;

        $dates = [];
        $plus = [];
        $minus = [];

        $this->Dashboard->LQuery->db->query($sql);

        while ($row = $this->Dashboard->LQuery->db->get_row()) {
            $dates[] = $this->formatDate($row);
            $plus[] = (float)($row['plus'] ?? 0);
            $minus[] = (float)($row['minus'] ?? 0);
        }

        $datesJson = json_encode($dates);
        $plusJson = json_encode($plus);
        $minusJson = json_encode($minus);
        $counter = $this->chartCounter;
        $currency = Balance::Init()->Declension(10);

        $style = $this->chartCounter === 1 ? 'min-width:310px' : '';

        return <<<HTML
<script>
$(function () {
    $('#container_{$counter}').highcharts({
        chart: { type: 'column' },
        title: { text: '' },
        xAxis: {
            categories: {$datesJson}
        },
        yAxis: {
            min: 0,
            title: {
                text: '{$this->Dashboard->lang['history_summa']}'
            }
        },
        credits: { enabled: false },
        tooltip: {
            split: true,
            valueSuffix: ' ({$currency})'
        },
        series: [{
            name: '{$this->Dashboard->lang['statistics_plus']}',
            data: {$plusJson}
        }, {
            name: '{$this->Dashboard->lang['statistics_minus']}',
            data: {$minusJson}
        }]
    });
});
</script>
<br>
<div id="container_{$counter}" style="{$style}; height:400px; margin:10px"></div>
HTML;
    }

    /**
     * Render user group
     */
    private function renderUserGroup(array $userInfo): string
    {
        global $user_group;

        $answer = '';

        if (($userInfo['banned'] ?? '') === 'yes') {
            $answer = $this->Dashboard->lang['statistics_users_2'];
        }

        if (!empty($user_group[$userInfo['user_group']]['time_limit'])) {
            if (!empty($userInfo['time_limit'])) {
                $date = langdate("j F Y H:i", $userInfo['time_limit']);
                $answer .= "&nbsp;<a style=\"cursor:info\" data-toggle=\"dropdown\" data-original-title=\"" . $this->Dashboard->lang['statistics_users_21'] . " {$date}\" class=\"status-info tip\"><i class=\"fa fa-info-sign\"></i></a>";
            } else {
                $answer .= $this->Dashboard->lang['statistics_users_22'];
            }
        }

        return ($user_group[$userInfo['user_group']]['group_name'] ?? '') . $answer;
    }
}