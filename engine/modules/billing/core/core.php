<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

trait Core
{
    /**
     * Loaded plugins lang's
     * @var array
     */
    private static array $Lang = [];

    /**
     * Check installer
     * @param callable $callback
     * @return void
     */
    public static function isInstall(callable $callback) : void
    {
        if( ! file_exists( MODULE_DATA . '/config.php' ) )
        {
            $callback();

            exit;
        }
    }

    /**
     * Loader handler class
     * @param string $plugin
     * @param string $handler
     * @return object|null
     */
    public static function getHandler(string $plugin, string $handler) : ?object
    {
        $plugin = preg_replace("/[^a-z\s]/", "", trim( $plugin ) );
        $handler = preg_replace("/[^a-z\s]/", "", trim( $handler ) );

        if( file_exists( MODULE_PATH . '/plugins/' . $plugin . '/handler.' . $handler . '.php' ) )
        {
            $Handler = include MODULE_PATH . '/plugins/' . $plugin . '/handler.' . $handler . '.php';

            if( $Handler instanceof Handler )
            {
                return $Handler;
            }
        }

        return null;
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

    /**
     * Get plugin lang
     * @param string $plugin
     * @return array
     */
    public static function getLang(string $plugin) : array
    {
        if( isset(static::$Lang[$plugin]) )
        {
            return static::$Lang[$plugin];
        }

        if( file_exists(MODULE_PATH . '/plugins/' . $plugin . '/lang.php') )
        {
            return static::$Lang[$plugin] = include MODULE_PATH . '/plugins/' . $plugin . '/lang.php';
        }

        return [];
    }

    /**
     * Get plugin config file
     * @param string $plugin
     * @return array
     */
    public static function getConfig(string $plugin) : array
    {
        if( $plugin !== '' )
        {
            $plugin = 'plugin.' . $plugin;
        }
        else
        {
            $plugin = 'config';
        }

        return file_exists( MODULE_DATA . '/' . $plugin . '.php' ) ? require MODULE_DATA . '/' . $plugin . '.php' : [];
    }

    /**
     * Загрузить экземпляр класса платежной системы
     * @param string $payment
     * @return IPayment|null
     */
    public static function getPayment(string $payment) : ?IPayment
    {
        $payment = preg_replace("/[^a-z\s]/", "", trim( $payment ) );

        if( ! $payment )
        {
            return null;
        }

        if( file_exists( MODULE_PATH . '/payments/' . $payment . "/adm.settings.php" ) )
        {
            require_once MODULE_PATH . '/payments/' . $payment . "/adm.settings.php";

            if( isset($Paysys) and $Paysys instanceof IPayment)
            {
                return $Paysys;
            }
        }

        return null;
    }

    /**
     * Ссылка на пост
     * @param array $row
     * @return string
     */
    public static function getPostFullUrl(array $row) : string
    {
        global $config;

        if( $config['allow_alt_url'] )
        {
            if( $config['seo_type'] == 1 OR $config['seo_type'] == 2  )
            {
                if( $row['category'] and $config['seo_type'] == 2 )
                {
                    $cats_url = get_url( $row['category'] );

                    if($cats_url)
                    {
                        return $config['http_home_url'] . $cats_url . "/" . $row['id'] . "-" . $row['alt_name'] . ".html";
                    }

                    return $config['http_home_url'] . $row['id'] . "-" . $row['alt_name'] . ".html";
                }

                return $config['http_home_url'] . $row['id'] . "-" . $row['alt_name'] . ".html";
            }

            return $config['http_home_url'] . date( 'Y/m/d/', $row['date'] ) . $row['alt_name'] . ".html";
        }

        return $config['http_home_url'] . "index.php?newsid=" . $row['id'];
    }

    /**
     * Загрузить или создать файл настроек
     * @param string $file
     * @param bool $creat
     * @param array $setStarting
     * @return false|mixed
     */
    public function LoadConfig( string $file, bool $creat = false, array $setStarting = [] ): mixed
    {
        if( ! file_exists( MODULE_DATA . '/plugin.' . $file . '.php' ) )
        {
            if( $creat )
            {
                $this->SaveConfig( "plugin." . $file, $setStarting );

                return require MODULE_DATA . '/plugin.' . $file . '.php';
            }

            return false;
        }
        else
        {
            return require MODULE_DATA . '/plugin.' . $file . '.php';
        }
    }

    /**
     * Проверить hash строку
     * @param string $hash
     * @return void
     * @throws Exception
     */
    public function CheckHash(string $hash = '')
    {
        $hash = $hash ?: $_REQUEST['user_hash'];

        if( ! $hash or $hash != $this->hash )
        {
            throw new Exception($this->lang['hash_error']);
        }
    }

    /**
     * Аватар пользователя
     * @param string|null $avatar
     * @return string
     */
    public function Foto( ?string $avatar = '' ) : string
    {
        if ( count(explode("@", $avatar)) == 2 )
        {
            return 'https://www.gravatar.com/avatar/' . md5(trim($avatar)) . '?s=150';
        }
        else if( $avatar and ( file_exists( ROOT_DIR . "/uploads/fotos/" . $avatar )) )
        {
            return '/uploads/fotos/' . $avatar;
        }
        elseif( $avatar )
        {
            return $avatar;
        }

        return "/templates/{$this->dle['skin']}/dleimages/noavatar.png";
    }

    /**
     * Время и дата
     * @param int $time
     * @return string
     */
    public function ThemeChangeTime( int $time ) : string
    {
        date_default_timezone_set( $this->dle['date_adjust'] );

        $ndate = date('j.m.Y', $time);
        $ndate_time = date('H:i', $time);

        if( $ndate == date('j.m.Y') )
        {
            return $this->lang['main_now'] . $ndate_time;
        }
        elseif($ndate == date('j.m.Y', strtotime('-1 day')))
        {
            return $this->lang['main_rnow'] . $ndate_time;
        }
        else
        {
            return langdate( "j F Y  G:i", $time );
        }
    }

    /**
     * Plugins
     * @return array
     */
    public function Plugins() : array
    {
        if( $this->Plugins ) return $this->Plugins;

        $Plugins = [];
        $List = opendir( MODULE_PATH . '/plugins/' );

        while ( $name = readdir($List) )
        {
            if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) or ! is_dir(MODULE_PATH . '/plugins/' . $name) ) continue;

            $Plugins[mb_strtolower($name)] = parse_ini_file( MODULE_PATH . '/plugins/' . $name . '/info.ini' );
            $Plugins[mb_strtolower($name)]['config'] = file_exists( MODULE_DATA . '/plugin.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/plugin.' . mb_strtolower($name) . '.php' : array();
        }

        uasort($Plugins, function ($a, $b) {
            $b["config"]['status'] = $b["config"]['status'] ?? 0;
            $a["config"]['status'] = $a["config"]['status'] ?? 0;
            return strcmp($b["config"]['status'], $a["config"]['status']);
        });

        return $this->Plugins = $Plugins;
    }

    /**
     * Payments list
     * @return array
     */
    public function Payments() : array
    {
        if( $this->Payments ) return $this->Payments;

        $Payments = [];
        $List = opendir( MODULE_PATH . '/payments/' );

        while ( $name = readdir($List) )
        {
            if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) or ! is_dir(MODULE_PATH . '/payments/' . $name) ) continue;

            $Payments[mb_strtolower($name)] = parse_ini_file( MODULE_PATH . '/payments/' . $name . '/info.ini' );
            $Payments[mb_strtolower($name)]['config'] = file_exists( MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' : array();

            if( ! $Payments[mb_strtolower($name)]['title'] )
            {
                $Payments[mb_strtolower($name)]['title'] = $name;
            }
        }

        uasort($Payments, function ($a, $b) {
            $b["config"]['status'] = $b["config"]['status'] ?? 0;
            $a["config"]['status'] = $a["config"]['status'] ?? 0;
            return strcmp($b["config"]['status'], $a["config"]['status']);
        });

        return $this->Payments = $Payments;
    }

    /**
     * Usergroups in select
     * @param $id
     * @param $none
     * @return string
     */
    public function GetGroups( mixed $id = false, mixed $none = false ) : string
    {
        global $user_group;

        $return = "";

        foreach ( $user_group as $group )
        {
            if( ( is_array( $none ) and in_array( $group['id'], $none ) )
                or ( !is_array( $none ) and $group['id'] == $none ) ) continue;

            $return .= '<option value="' . $group['id'] . '" ';

            if( is_array( $id ) )
            {
                foreach ( $id as $element )
                {
                    if( $element == $group['id'] ) $return .= 'selected';
                }
            }
            elseif( $id and $id == $group['id'] ) $return .= 'selected';

            $return .= ">" . $group['group_name'] . "</option>\n";
        }

        return $return;
    }

    /**
     * Генерация строки
     * @param int $length
     * @return string
     */
    public function genCode( int $length = 8 ) : string
    {
        $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
        $numChars = strlen($chars);
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= substr($chars, rand(1, $numChars) - 1, 1);
        }

        return $string;
    }

    /**
     * Сохранить кеш в файл
     * @param $file
     * @param $data
     * @return void
     */
    public function CreatCache( $file, $data ) : void
    {
        file_put_contents (ENGINE_DIR . "/cache/" . $file . ".tmp", $data, LOCK_EX);

        @chmod( ENGINE_DIR . "/cache/" . $file . ".tmp", 0666 );
    }

    /**
     * Проверить кеш и загрузить
     * @param string $file
     * @return string
     */
    public function GetCache( string $file ) : string
    {
        $buffer = @file_get_contents( ENGINE_DIR . "/cache/" . $file . ".tmp" );

        if ( $buffer !== false and $this->dle['clear_cache'] )
        {
            $file_date = @filemtime( ENGINE_DIR . "/cache/" . $file . ".tmp" );
            $file_date = time() - $file_date;

            if ( $file_date > ( $this->dle['clear_cache'] * 60 ) )
            {
                $buffer = false;
                @unlink( ENGINE_DIR . "/cache/" . $file . ".tmp" );
            }

            return $buffer;
        }

        return $buffer;
    }
}