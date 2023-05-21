<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

trait Core
{
    public function CheckHash(string $hash = '')
    {
        $hash = $hash ?: $_REQUEST['user_hash'];

        if( ! $hash or $hash != $this->hash )
        {
            throw new Exception($this->lang['hash_error']);
        }
    }


    public function Logger(string $file, ...$msg) : void
    {
        if( ! file_exists( MODULE_PATH . "/log/{$file}.txt"  ) )
        {
            $handler = fopen( MODULE_PATH . "/log/{$file}.txt", "a" );
        }
        else
        {
            $handler = fopen( MODULE_PATH . "/log/{$file}.txt", "a" );
        }

        $msg = str_replace(array('\r\n', '\r', '\n', '|'), '/',  strip_tags(print_r($msg, 1)));

        fwrite( $handler,
            $step . "\n" .
            langdate( "j.m.Y H:i", time()) . '|' .
            $msg . "\n --- END --- \n"
        );

        fclose( $handler );

        return;
    }

    # Фото пользователя
    #
    private function Foto( $foto )
    {
        if ( count(explode("@", $foto)) == 2 )
        {
            return 'http://www.gravatar.com/avatar/' . md5(trim($foto)) . '?s=150';
        }
        else if( $foto and ( file_exists( ROOT_DIR . "/uploads/fotos/" . $foto )) )
        {
            return '/uploads/fotos/' . $foto;
        }
        elseif( $foto )
        {
            return $foto;
        }

        return "/templates/{$this->dle['skin']}/dleimages/noavatar.png";
    }

    # Время и дата
    #
    function ThemeChangeTime( int $time )
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

    # Массив плагинов
    #
    function Plugins()
    {
        if( $this->Plugins ) return $this->Plugins;

        $List = opendir( MODULE_PATH . '/plugins/' );

        while ( $name = readdir($List) )
        {
            if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

            $this->Plugins[mb_strtolower($name)] = parse_ini_file( MODULE_PATH . '/plugins/' . $name . '/info.ini' );
            $this->Plugins[mb_strtolower($name)]['config'] = file_exists( MODULE_DATA . '/plugin.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/plugin.' . mb_strtolower($name) . '.php' : array();
        }

        return $this->Plugins;
    }

    # Массив платежных систем
    #
    function Payments()
    {
        if( $this->Payments ) return $this->Payments;

        $List = opendir( MODULE_PATH . '/payments/' );

        while ( $name = readdir($List) )
        {
            if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

            $this->Payments[mb_strtolower($name)] = parse_ini_file( MODULE_PATH . '/payments/' . $name . '/info.ini' );
            $this->Payments[mb_strtolower($name)]['config'] = file_exists( MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' : array();

            if( ! $this->Payments[mb_strtolower($name)]['title'] )
            {
                $this->Payments[mb_strtolower($name)]['title'] = $name;
            }
        }

        return $this->Payments;
    }

    function GetGroups( $id = false, $none = false )
    {
        global $user_group;

        $returnstring = "";

        foreach ( $user_group as $group )
        {
            if( ( is_array( $none ) and in_array( $group['id'], $none ) )
                or ( !is_array( $none ) and $group['id'] == $none ) ) continue;

            $returnstring .= '<option value="' . $group['id'] . '" ';

            if( is_array( $id ) )
            {
                foreach ( $id as $element )
                {
                    if( $element == $group['id'] ) $returnstring .= 'SELECTED';
                }
            }
            elseif( $id and $id == $group['id'] ) $returnstring .= 'SELECTED';

            $returnstring .= ">" . $group['group_name'] . "</option>\n";
        }

        return $returnstring;
    }

    # Загрузить или создать файл настроек
    #
    function LoadConfig( $file, $creat = false, $setStarting = array() )
    {
        if( ! file_exists( MODULE_DATA . '/plugin.' . $file . '.php' ) )
        {
            if( $creat )
            {
                $this->SaveConfig( "plugin." . $file, array( $setStarting ) );

                return require MODULE_DATA . '/plugin.' . $file . '.php';
            }

            return false;
        }
        else
        {
            return require MODULE_DATA . '/plugin.' . $file . '.php';
        }
    }

    # Генерация строки
    #
    function genCode( $length = 8 )
    {
        $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
        $numChars = strlen($chars);
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= substr($chars, rand(1, $numChars) - 1, 1);
        }

        return $string;
    }

    function CreatCache( $file, $data )
    {
        file_put_contents (ENGINE_DIR . "/cache/" . $file . ".tmp", $data, LOCK_EX);

        @chmod( ENGINE_DIR . "/cache/" . $file . ".tmp", 0666 );

        return;
    }

    function GetCache( $file )
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