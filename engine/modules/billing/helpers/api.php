<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

/**
 * API 1.0
 * TODO: update
 */
Class BillingAPI
{
    /**
     * Config this module
     * @var array
     */
    public $config = false;

    /**
     * Connect db
     * @var bool
     */
    public $db = false;

    /**
     * Authorized user
     * @var [type]
     */
    public array $member_id = [];

    /**
     * Local time
     * @var [type]
     */
    public int $_TIME;

    /**
     * depth
     * @var int
     */
    private int $hook_step = 0;

    /**
     * Config send alerts
     * @var bool
     */
    public $alert_pm = true;
    public $alert_main = true;

    function __construct( $db, $member_id, $billing_config, $_TIME )
    {
        $this->db = $db;
        $this->member_id = $member_id;
        $this->_TIME = $_TIME;
        $this->config = $billing_config;
    }

    /**
     * Add money
     * @param $user
     * @param $money
     * @param $desc
     * @param $plugin
     * @param $plugin_id
     * @return bool
     */
    public function PlusMoney( $user, $money, $desc, $plugin = 'api', $plugin_id = 0 )
    {
        $this->hook_step += 1;

        $user = $this->db->safesql( $user );
        $money = $this->Convert( $money );

        if( $this->member_id['name'] == $user )
        {
            $balance = $this->member_id[$this->config['fname']] + $money;
        }
        else
        {
            $SearchUser = $this->db->super_query( "SELECT " . $this->config['fname'] . "
													FROM " . USERPREFIX . "_users
													WHERE name='" . $user . "'" );

            $balance = $SearchUser[$this->config['fname']] + $money;
        }

        $this->db->query( "UPDATE " . USERPREFIX . "_users
							SET {$this->config['fname']} = {$this->config['fname']} + '$money'
							WHERE name='$user'");

        $this->SetHistory( $user, $money, 0, $balance, $desc, $plugin, $plugin_id );

        return true;
    }

    /**
     * Minus money
     * @param $user
     * @param $money
     * @param $desc
     * @param $plugin
     * @param $plugin_id
     * @param $test_balance
     * @return bool
     */
    public function MinusMoney( $user, $money, $desc, $plugin = 'api', $plugin_id = 0, $test_balance = true )
    {
        $this->hook_step += 1;

        $user = $this->db->safesql( $user );
        $money = $this->Convert( $money );

        if( $this->member_id['name'] == $user )
        {
            $balance = $this->member_id[$this->config['fname']] - $money;
        }
        else
        {
            $SearchUser = $this->db->super_query( "SELECT " . $this->config['fname'] . "
													FROM " . USERPREFIX . "_users
													WHERE name='" . $user . "'" );

            $balance = $SearchUser[$this->config['fname']] - $money;
        }

        if( $balance < 0 and $test_balance ) return false;

        $this->db->query( "UPDATE " . USERPREFIX . "_users
							SET {$this->config['fname']} = {$this->config['fname']} - '$money'
							WHERE name='$user'");

        $this->SetHistory( $user, 0, $money, $balance, $desc, $plugin, $plugin_id );

        return true;
    }

    /**
     * Send alerts
     * @param $theme
     * @param $data
     * @param $user_id
     * @param $user_email
     * @param $from
     * @return string|void
     */
    public function Alert( $theme, $data, $user_id = 0, $user_email = '', $from = '' )
    {
        global $config;

        $user_id = intval( $user_id );

        $from = $from ? $this->db->safesql( $from ) : $this->config['admin'];

        $Text = @file_get_contents( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/mail/' . $theme . '.tpl');

        if( ! $Text )
        {
            return 'Error load: ' . '/templates/' . $config['skin'] . '/billing/mail/' . $theme . '.tpl';
        }

        preg_match('~\[title\](.*?)\[/title\]~is', $Text, $Title);

        $Text = preg_replace("'\\[title\\].*?\\[/title\\]'si", '', $Text);

        foreach( $data as $key=>$value )
        {
            $Text = str_replace( $key, $this->db->safesql( $value ), $Text);
        }

        # .. отправить pm на сайте
        #
        if( $user_id )
        {
            $this->db->query( "INSERT INTO " . PREFIX . "_pm
											(subj, text, user, user_from, date, pm_read, folder) VALUES
											('$Title[1]', '$Text', '$user_id', '$from', '$this->_TIME', '0', 'inbox')" );

            $this->db->query( "UPDATE " . USERPREFIX . "_users
								SET pm_unread = pm_unread + 1, pm_all = pm_all+1
								WHERE user_id = '$user_id'" );
        }

        # .. отправить email
        #
        if( $user_email )
        {
            include_once DLEPlugins::Check( ENGINE_DIR . '/classes/mail.class.php' );

            $mail = new dle_mail( $config, true );

            $mail->send( $user_email, $Title[1], $Text );

            unset( $mail );
        }

        return;
    }

    /**
     * Paging
     * @param $all_count
     * @param $this_page
     * @param $link
     * @param $tpl_link
     * @param $tpl_this_num
     * @param $per_page
     * @return array|string|string[]
     */
    public function Pagination( $all_count, $this_page, $link, $tpl_link, $tpl_this_num, $per_page = '' )
    {
        $all_count = intval( $all_count ) ? intval( $all_count ) : 1;
        $this_page = intval( $this_page ) ? intval( $this_page ) : 1;
        $per_page = intval( $per_page ) ? intval( $per_page ) : $this->config['paging'];

        $enpages_count = @ceil( $all_count / $per_page );
        $enpages_start_from = 0;
        $enpages = '';

        if( $enpages_count == 1 )
        {
            return $this->PaginationForm( 1, $tpl_link, "#" );
        }

        $min = false;

        if( $this_page > 1 )
        {
            $enpages = $this->PaginationForm( ($this_page-1), $tpl_link, $link, "&laquo;" );
        }

        for( $j = 1; $j <= $enpages_count; $j ++ )
        {
            if( $j < ( $this_page - 4 ) )
            {
                if( ! $min )
                {
                    $j++;
                    $min = true;

                    $enpages .= $this->PaginationForm( 1, $tpl_link, $link, "1.." );
                }

                continue;
            }

            if( $j > ( $this_page + 5 ) )
            {
                $enpages .= $this->PaginationForm( $enpages_count, $tpl_link, $link, "..{$enpages_count}" );

                break;
            }

            if( $this_page != $j )
            {
                $enpages .= $this->PaginationForm( $j, $tpl_link, $link );
            }
            else
            {
                $enpages .= $this->PaginationForm( $j, $tpl_this_num, $link );
            }

            $enpages_start_from += $per_page;
        }

        if( $this_page < $enpages_count )
        {
            $enpages .= $this->PaginationForm( ($this_page+1), $tpl_link, $link, "&raquo;" );
        }

        return $enpages;
    }

    private function PaginationForm( $page, $form_link, $link, $title = '' )
    {
        $link = str_replace( "{p}", $page, $link);

        $answer = str_replace( "{page_num}", ( $title ? $title : $page ), $form_link);
        $answer = str_replace( "{page_num_link}", $link, $answer);

        return $answer;
    }

    /**
     * Convert the number to the format
     * @param $money
     * @param $format
     * @return int|string
     */
    public function Convert( $money, $format = '' )
    {
        if( ! $format ) $format = $this->config['format'];
        if( ! floatval($money) ) $money = 0;

        if( $format == 'int' ) return intval( $money );

        return number_format($money, 2, '.', '');
    }

    /**
     * Name currency
     * @param $number
     * @param $titles
     * @return string
     */
    public function Declension( $number, $titles = '' )
    {
        $number = intval( $number );

        if( ! $titles ) $titles = $this->config['currency'];

        $titles = explode(",", $titles );

        if( count( $titles ) != 3 ) return $titles[0];

        $cases = array (2, 0, 1, 1, 1, 2);

        return $titles[ ($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)] ];
    }

    /**
     * Connect plugins hook
     * @param $user
     * @param $plus
     * @param $minus
     * @param $balance
     * @param $desc
     * @param string $plugin
     * @param string $plugin_id
     * @return void
     */
    private function Hooks($user, $plus, $minus, $balance, $desc, $plugin = '', $plugin_id = '' )
    {
        if( $this->hook_step >= 2 ) return;

        $List = opendir( MODULE_PATH . '/plugins/' );

        while ( $name = readdir($List) )
        {
            if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

            if( file_exists( MODULE_PATH . '/plugins/' . $name . '/hook.class.php' )
                and file_exists( MODULE_DATA . '/plugin.' . $name . '.php' ))
            {
                $Hook = include( MODULE_PATH . '/plugins/' . $name . '/hook.class.php' );

                if( (new ReflectionClass($Hook))->isAnonymous() )
                {
                    $Hook->plugin = include MODULE_DATA . '/plugin.' . $name . '.php';
                    $Hook->api = $this;

                    if( in_array('pay', get_class_methods($Hook) ) )
                    {
                        $Hook->pay( $user, $plus, $minus, $balance, $desc, $plugin, $plugin_id );
                    }
                }
            }
        }

        return;
    }

    /**
     * Write to story pay
     * @param $user
     * @param $plus
     * @param $minus
     * @param $balance
     * @param $desc
     * @param $plugin
     * @param $plugin_id
     * @return bool
     */
    private function SetHistory( $user, $plus, $minus, $balance, $desc, $plugin = '', $plugin_id = '' )
    {
        $desc = $this->db->safesql( $desc );

        $currency = $plus ? $this->Declension( $plus ) : $this->Declension( $minus );
        $balance = $this->Convert( $balance );

        $dataMail = array(
            '{date}' => langdate( "j F Y  G:i", $this->_TIME ),
            '{login}' => $user,
            '{sum}'=> ( $plus ? "+$plus " . $this->Declension( $plus ) : "-$minus " . $this->Declension( $plus ) ),
            '{comment}' => $desc,
            '{balance}' => $balance . ' ' . $this->Declension( $balance ),
        );

        # Уведомление об изменении баланса на сайте
        # .. в лп
        #
        if( $this->config['mail_balance_pm'] and $this->alert_pm )
        {
            $arrUser = $this->db->super_query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE name='" . $user . "'" );

            if( $arrUser['user_id'] )
            {
                $this->Alert( "balance", $dataMail, $arrUser['user_id'] );
            }
        }

        # .. на email
        #
        if( $this->config['mail_balance_email'] and $this->alert_main )
        {
            if( ! $arrUser['email'] )
            {
                $arrUser = $this->db->super_query( "SELECT email FROM " . USERPREFIX . "_users WHERE name='" . $user . "'" );
            }

            if( $arrUser['email'] )
            {
                $this->Alert( "balance", $dataMail, 0, $arrUser['email'] );
            }
        }

        $this->db->query( "INSERT INTO " . PREFIX . "_billing_history
							(history_plugin, history_plugin_id, history_user_name, history_plus, history_minus, history_balance, history_currency, history_text, history_date) values
							('$plugin', '$plugin_id', '$user', '$plus', '$minus', '$balance', '$currency', '$desc', '".$this->_TIME."')" );

        $this->Hooks( $user, $plus, $minus, $balance, $desc, $plugin, $plugin_id );

        return true;
    }
}
