<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

/**
 * Пользовательский интерфейс
 */
Class DevTools
{
    use Core, Utheme;

    private static self $instance;

    private function __construct(){}
    private function __clone()    {}
    private function __wakeup()   {}

    public static function Start()
    {
        if ( empty(self::$instance) )
        {
            self::$instance = new self();
        }
        return self::$instance->Loader();
    }

    /**
     * DLE config
     */
    public array $dle = [];

    /**
     * Authorized user
     */
    public array $member_id = [];

    /**
     * Local time
     */
    public int $_TIME;

    /**
     * Config this module
     * @var array
     */
    public array $config = [];

    /**
     * Lang array
     * @var array
     */
    public array $lang = [];

    /**
     * Loaded plugin class
     * @var string
     */
    public string $get_plugin = '';

    /**
     * Loaded plugin method
     * @var string
     */
    public string $get_method = '';

    /**
     * Connect api module
     * @var object
     */
    public object $API;

    /**
     * Helper sql
     * @var object
     */
    public object $LQuery;

    /**
     * User balance
     * @var float|int
     */
    public int|float $BalanceUser = 0;

    /**
     * Hash string to form
     * @var string
     */
    public string $hash;

    /**
     * Module plugins and payments
     * @var array
     */
    public array $Plugins = [];
    public array $Payments = [];

    /**
     * Main loader
     * @throws \Exception
     */
    private function Loader(): void
    {
        global $config, $member_id, $_TIME, $db, $dle_login_hash;

        $this->lang 	= include MODULE_PATH . '/lang/cabinet.php';
        $this->config 	= static::getConfig('');

        # ..модуль отключен
        #
        if( ! $this->config['status'] )
        {
            if( $_GET['c'] == "pay" and $_GET['m'] == "get" ) exit("Off");

            if( $member_id['user_group'] != 1 )
            {
                echo $this->lang['cabinet_off'];
                return;
            }
            else
            {
                echo $this->lang['off'];
            }
        }

        $this->LQuery 	= new Database( $db, $this->config['fname'], $_TIME );
        $this->API 		= new API( $db, $member_id, $this->config, $_TIME );

        $this->dle 		= $config;
        $this->member_id = $member_id;

        $this->_TIME = $_TIME;
        $this->hash = $dle_login_hash;

        $this->BalanceUser = $this->API->Convert( $this->member_id[$this->config['fname']] );

        # Параметры загрузки
        #
        $arrParams = [];

        $_GET['route'] = $_GET['route'] ?? '';

        $parseRoute = array_map(function($value) {
            return ( preg_match( "/[\||\'|\<|\>|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\+]/", $value ) || empty($value) ) ? '': $value;
        }, explode('/', $_GET['route']));

        $defaultRoute = explode('/', $this->config['start']);

        $this->get_plugin 		= $parseRoute[0] ?: $defaultRoute[0];
        $this->get_method   	= $parseRoute[1] ?: $defaultRoute[1];

        $RealURL = $this->URL( $this->get_plugin );

        $parseRoute = count( $parseRoute ) > 2 ? $parseRoute : $defaultRoute;

        if( count( $parseRoute ) > 2 )
        {
            for( $n = 2; $n < count( $parseRoute ); $n++ )
            {
                $arrParams[$parseRoute[$n]] = $parseRoute[$n+1];
                $n++;
            }
        }

        # Подключение страницы
        #
        if( file_exists( MODULE_PATH . '/controllers/user.' . $RealURL . '.php' ) )
        {
            require_once MODULE_PATH . '/controllers/user.' . $RealURL . '.php';
        }
        # Подключение плагина
        #
        elseif( file_exists( MODULE_PATH . '/plugins/' . $RealURL . '/user.main.php' ) )
        {
            require_once MODULE_PATH . '/plugins/' . $RealURL . '/user.main.php';
        }
        else
        {
            throw new \Exception(sprintf($this->lang['cabinet_controller_error'], $this->get_plugin));
        }

        $Cabinet = new USER;

        if( in_array($this->get_method, get_class_methods($Cabinet) ) )
        {
            if( property_exists($Cabinet, 'DevTools') )
            {
                $Cabinet->DevTools = $this;
            }

            echo $Cabinet->{$this->get_method}( $arrParams );
        }
        else
        {
            throw new \Exception(sprintf($this->lang['cabinet_metod_error'], $this->get_plugin, $this->get_method));
        }
    }

    /**
     * Show page
     * @param string $Content
     * @param bool $show_panel
     * @return string
     */
    public function Show(string $Content, bool $show_panel = true ) : string
    {
        $Cabinet = @file_get_contents( ENGINE_DIR . "/cache/system/billing.php" );

        if( ! $this->member_id['name'] )
        {
            $show_panel = false;
        }

        if( isset($_GET['modal']) )
        {
            $Cabinet = $this->ThemeLoad( 'modal' );
        }
        else if( ! $Cabinet )
        {
            $Cabinet = $this->ThemeLoad( 'cabinet' );

            $TplPlugin = $this->ThemePregMatch( $Cabinet, '~\[plugin\](.*?)\[/plugin\]~is' );

            $PluginsList = '';

            if( count( $this->Plugins() ) )
            {
                foreach( $this->Plugins() as $name => $pl_config )
                {
                    if( ! $pl_config['config']['name'] or ! $pl_config['config']['status'] )
                    {
                        continue;
                    }

                    $TimeLine = $TplPlugin;

                    $name = $this->reURL( $name );

                    $TimeLine = str_replace("{plugin.tag}", mb_strtolower( $name ), $TimeLine);
                    $TimeLine = str_replace("{plugin.name}", $pl_config['config']['name'], $TimeLine);
                    $TimeLine = str_replace("{plugin.active}", "billing-item[active]" . mb_strtolower( $name ) . "[/active]", $TimeLine);

                    $PluginsList .= $TimeLine;
                }
            }

            $Cabinet = preg_replace("'\\[plugin\\].*?\\[/plugin\\]'si", $PluginsList, $Cabinet);

            $save_file = fopen( ENGINE_DIR . "/cache/system/billing.php", "w" );

            fwrite( $save_file, $Cabinet );
            fclose( $save_file );
        }

        $Cabinet = str_replace( "{content}", $Content, $Cabinet);
        $Cabinet = str_replace( "{hash}", $this->hash, $Cabinet);

        $Cabinet = str_replace( "{module.cabinet}", $this->config['page'] . '.html', $Cabinet);
        $Cabinet = str_replace( "{module.skin}", $this->dle['skin'], $Cabinet);

        $Cabinet = str_replace( "{user.name}", $this->member_id['name'], $Cabinet);
        $Cabinet = str_replace( "{user.balance}", $this->API->Convert($this->BalanceUser, number_format_f: true), $Cabinet);
        $Cabinet = str_replace( "{user.balance.currency}", $this->API->Declension( $this->BalanceUser ), $Cabinet);
        $Cabinet = str_replace( "{user.foto}", $this->Foto( $this->member_id['foto'] ), $Cabinet);

        $Cabinet = str_replace( "[active]{$this->get_plugin}[/active]", "-active", $Cabinet);

        if( $show_panel )
        {
            $Cabinet = str_replace('[panel]', '', $Cabinet);
            $Cabinet = str_replace('[/panel]', '', $Cabinet);
        }
        else
            $Cabinet = preg_replace("'\\[panel\\].*?\\[/panel\\]'si", $PluginsList, $Cabinet);

        $Cabinet = preg_replace("'\\[active\\].*?\\[/active\\]'si", '', $Cabinet);

        foreach( $this->elements as $key => $value )
        {
            $Cabinet = str_replace( $key, $value, $Cabinet);
        }

        foreach( $this->element_block as $key => $value )
        {
            $Cabinet = preg_replace("'\\[".$key."\\].*?\\[/".$key."\\]'si", $value, $Cabinet);
        }

        if( isset($_GET['modal']) )
        {
            echo $Cabinet; exit;
        }

        return $Cabinet;
    }

    /**
     * Pre-check pay from balance
     * @param float $sum
     * @param array $_Payment
     * @param bool $from_balance
     * @return void
     * @throws \Exception
     */
    public function FormPayCheck( float $sum, array $_Payment, bool $from_balance = false ): void
    {
        $this->CheckHash($_POST['billingHash']);

        if( $from_balance and ! count( $_Payment ) and $this->member_id['user_id'] )
        {
            $_Payment = [
                'status' => 1,
                'title' => $this->lang['pay_balance'],
                'currency' => $this->API->Declension( $sum ),
                'min' => 0.01,
                'max' => $this->BalanceUser,
                'convert' => 1
            ];

            if( $sum > $this->BalanceUser )
            {
                throw new \Exception( $this->lang['pay_sum_error'] );
            }
        }

        if( ! $_Payment['status'] )
        {
            throw new \Exception( $this->lang['pay_paysys_error'] );
        }
        else if( $sum < $_Payment['minimum'] )
        {
            throw new \Exception( sprintf(
                $this->lang['pay_minimum_error'],
                $_Payment['title'],
                $_Payment['minimum'],
                $this->API->Declension( $_Payment['minimum'] )
            ) );
        }
        else if( $sum > $_Payment['max'] )
        {
            throw new \Exception( sprintf(
                $this->lang['pay_max_error'],
                $_Payment['title'],
                $_Payment['max'],
                $this->API->Declension( $_Payment['max'] )
            ) );
        }
    }

    /**
     * Payment list for pay
     * @param float $sum
     * @param bool $from_balance
     * @param array $more_info
     * @return string
     */
    public function FormSelectPay( float $sum, bool $from_balance = false, array $more_info = [] ): string
    {
        $PaymentsArray = $this->Payments();

        $Tpl = $this->ThemeLoad( "pay/waiting" );

        $PaysysList = '';

        $TplSelect = $this->ThemePregMatch( $Tpl, '~\[payment\](.*?)\[/payment\]~is' );

        # Оплата с баланса
        #
        if( $from_balance and $this->member_id['user_id'] )
        {
            $this->ThemeSetElement( "[payment_balance]", '' );
            $this->ThemeSetElement( "[/payment_balance]", '' );
        }
        else
        {
            $this->ThemeSetElementBlock( "payment_balance", '' );
        }

        if( count( $PaymentsArray ) )
        {
            foreach( $PaymentsArray as $Name => $Info )
            {
                if( ! $Info['config']['status'] or $sum < $Info['config']['minimum'] or $sum > $Info['config']['max'] )
                {
                    continue;
                }

                $TimeLine = $TplSelect;

                $TimeLine = str_replace("{module.skin}", $this->dle['skin'], $TimeLine);
                $TimeLine = str_replace("{payment.name}", $Name, $TimeLine);
                $TimeLine = str_replace("{payment.title}", $Info['config']['title'], $TimeLine);
                $TimeLine = str_replace("{payment.topay}", $this->API->Convert($sum * floatval($Info['config']['convert']), $Info['config']['format']), $TimeLine);
                $TimeLine = str_replace("{payment.currency}", $Info['config']['currency'], $TimeLine);

                $PaysysList .= $TimeLine;
            }
        }
        else
        {
            $PaysysList = $this->lang['pay_main_error'];
        }

        $this->ThemeSetElementBlock( "payment", $PaysysList );

        if( count($more_info) )
        {
            $TplSelect = $this->ThemePregMatch( $Tpl, '~\[more\](.*?)\[/more\]~is' );

            $arrMore = [];

            foreach( $more_info as $title => $value )
            {
                $TimeLine = $TplSelect;

                $TimeLine = str_replace('{title}', $title, $TimeLine);
                $TimeLine = str_replace('{value}', $value, $TimeLine);

                $arrMore[] = $TimeLine;
            }

            $this->ThemeSetElementBlock( "more", implode($arrMore) );
        }
        else
            $this->ThemeSetElementBlock( "more", '' );

        return $this->ThemeLoad( "pay/waiting" );
    }

    /**
     * Parse user xfields
     * @param string $xfields
     * @return array
     */
    public function ParsUserXFields( string $xfields = '' ) : array
    {
        $return = [];

        foreach( explode("||", $xfields) as $xfield )
        {
            $value = explode("|", $xfield);

            $return[$value[0]] = $value[1];
        }

        return $return;
    }

    /**
     * URL: alt -> real
     * @param string $plugin
     * @return string
     */
    public function URL( string $plugin ): string
    {
        foreach (explode(',', $this->config['urls']) as $url_param)
        {
            $url = explode('-', $url_param);

            if( $url[1] == $plugin )
            {
                return $url[0];
            }

            if( $url[0] == $plugin )
            {
                header('Location: /' . $this->config['page'] . '.html/' . $url[1] . '/' );
            }
        }

        return $plugin;
    }

    /**
     * URL: real -> alt
     * @param string $plugin
     * @return string
     */
    public function reURL( string $plugin ): string
    {
        foreach (explode(',', $this->config['urls']) as $url_param)
        {
            $url = explode('-', $url_param);

            if( $url[0] == $plugin )
            {
                return $url[1];
            }
        }

        return $plugin;
    }

    /**
     * Check user/guest with db
     * @param string $loginRow
     * @return bool
     */
    public function checkUser(string $loginRow): bool
    {
        $checkUser = !empty($this->member_id['name']) ? $this->member_id['name'] : $_SERVER['REMOTE_ADDR'];

        if( empty($checkUser) or $checkUser != $loginRow )
        {
            return false;
        }

        return true;
    }

    /**
     * Invoice handler string to array
     * @param string $invoice_handler
     * @return array
     *
     */
    public static function exInvoiceHandler(string $invoice_handler) : array
    {
        $parsHandler = explode(':', $invoice_handler);

        if( count($parsHandler) !== 2 )
            return [];

        $parsHandler[0] = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $parsHandler[0] ) );
        $parsHandler[1] = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $parsHandler[1] ) );

        return $parsHandler;
    }
}