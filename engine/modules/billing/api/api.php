<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Module;

Class Api
{
	private static self $instance;

	private function __construct(){}
    private function __clone()    {}
    private function __wakeup()   {}

    private static array $global = [];

    public static function Start(?array $params = []) : self
	{
        if ( empty(self::$instance) )
		{
            global $db, $member_id, $_TIME, $config;

            self::$instance = new self();

            self::$global = [
                'DB' => $db,
                'USER' => $member_id,
                'TIME' => $_TIME,
                'DLE' => $config
            ];
        }

        return self::$instance;
    }

    public static function Convert()
    {

    }

//	public function setSettingsAlert(array $new_data)
//	{
//		foreach( $this->settingAlert as $setting => $value )
//		{
//			if( isset( $new_data[$setting] ) )
//			{
//				$this->settingAlert[$setting] = $new_data[$setting];
//			}
//		}
//
//		return $this;
//	}
//
//	public function Plus( string $userlogin, float $money, string $desc, string $plugin = 'api', int $plugin_id = 0 ) : bool
//	{
//		$userlogin = $this->db->safesql( $userlogin );
//
//		$money = $this->Convert( $money );
//
//		if( $this->member_id['name'] == $userlogin )
//		{
//			$balance = $this->Convert($this->member_id[$this->config['fname']]) + $money;
//		}
//		else
//		{
//			$SearchUser = $this->db->super_query( "SELECT " . $this->config['fname'] . " FROM " . USERPREFIX . "_users WHERE name='" . $userlogin . "'" );
//
//			$balance = $this->Convert($SearchUser[$this->config['fname']]) + $money;
//		}
//
//		$this->db->query("START TRANSACTION;");
//
//		$this->db->query( "UPDATE " . USERPREFIX . "_users
//							SET {$this->config['fname']} = {$this->config['fname']} + $money
//							WHERE name='$userlogin'");
//
//		if( $this->SetHistory( $userlogin, $money, 0, $balance, $desc, $plugin, $plugin_id ) )
//		{
//			$this->db->query("COMMIT");
//
//			return true;
//		}
//
//		$this->db->query("ROLLBACK");
//
//		return false;
//	}
//
//	public function Minus( string $userlogin, float $money, string $desc, bool $check_balance = true, string $plugin = 'api', int $plugin_id = 0 ) : bool
//	{
//		$userlogin = $this->db->safesql( $userlogin );
//		$money = $this->Convert( $money );
//
//		if( $this->member_id['name'] == $userlogin )
//		{
//			$balance = $this->Convert($this->member_id[$this->config['fname']]) - $money;
//		}
//		else
//		{
//			$SearchUser = $this->db->super_query( "SELECT " . $this->config['fname'] . " FROM " . USERPREFIX . "_users WHERE name='" . $userlogin . "'" );
//
//			$balance = $this->Convert($SearchUser[$this->config['fname']]) - $money;
//		}
//
//		if( $check_balance and $balance < 0 )
//		{die('asd');
//			return false;
//		}
//
//		$this->db->query("START TRANSACTION;");
//
//		$this->db->query( "UPDATE " . USERPREFIX . "_users
//							SET {$this->config['fname']} = {$this->config['fname']} - $money
//							WHERE name='$userlogin'");
//
//		if( $this->SetHistory( $userlogin, 0, $money, $balance, $desc, $plugin, $plugin_id ) )
//		{
//			$this->db->query("COMMIT");
//
//			return true;
//		}
//
//		$this->db->query("ROLLBACK");
//
//		return false;
//	}
//
//	public function SendPM( string $tpl_filename, array $data, int $user_id, string $from = '' )
//	{
//		$dataMessage = $this->ParseMessage( $tpl_filename, $data, $user_id);
//
//		$from = $from ? $this->db->safesql( $from ) : $this->config['admin'];
//
//		if( count($dataMessage) !== 2 )
//		{
//			return false;
//		}
//
//		$this->db->query( "INSERT INTO " . PREFIX . "_pm
//										(subj, text, user, user_from, date, pm_read, folder) VALUES
//										('{$dataMessage[0]}', '{$dataMessage[1]}', '{$user_id}', '{$from}', '{$this->_TIME}', '0', 'inbox')" );
//
//		$this->db->query( "UPDATE " . USERPREFIX . "_users
//							SET pm_unread = pm_unread + 1, pm_all = pm_all+1
//							WHERE user_id = '{$user_id}'" );
//
//		return true;
//	}
//
//	public function SendMail( string $tpl_filename, array $data, string $user_email, string $from = '' ) : bool
//	{
//		global $config;
//
//		$dataMessage = $this->ParseMessage( $tpl_filename, $data, $user_email);
//
//		$from = $from ? $this->db->safesql( $from ) : $this->config['admin'];
//
//		if( count($dataMessage) !== 2 )
//		{
//			return false;
//		}
//
//		include_once ENGINE_DIR . '/classes/mail.class.php';
//
//		$mail = new \dle_mail( $config, true );
//
//		$mail->send( $user_email, $dataMessage[0], $dataMessage[1] );
//
//		unset( $mail );
//
//		return true;
//	}
//
//	private function ParseMessage( string $tpl_filename, array $data, string|int $user )
//	{
//		global $config;
//
//		$tpl_filename = preg_replace("/[^a-zA-Z0-9\s]/", "", $tpl_filename );
//		$user = preg_replace("/[^a-zA-Z0-9\s]/", "", $user );
//
//		if( ! $user )
//		{
//			return [];
//		}
//
//		$Message = @file_get_contents( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/mail/' . $tpl_filename . '.tpl');
//
//		if( ! $Message )
//		{
//			return [];
//		}
//
//		preg_match('~\[title\](.*?)\[/title\]~is', $Message, $Title);
//
//		$Message = preg_replace("'\\[title\\].*?\\[/title\\]'si", '', $Message);
//
//		foreach( $data as $key=>$value )
//		{
//			$Message = str_replace( $key, $this->db->safesql( $value ), $Message);
//		}
//
//		return [
//			$Title[1],
//			$Message
//		];
//	}
//
//	public function SetHistory( string $userlogin, float $plus = 0, float $minus = 0, float $balance = 0, string $desc = '', string $plugin = '', int $plugin_id = 0 ) : bool
//	{
//		$userlogin = $this->db->safesql( $userlogin );
//		$desc = $this->db->safesql( $desc );
//		$plugin = $this->db->safesql( $plugin );
//
//		$plus = $this->Convert($plus);
//		$minus = $this->Convert($minus);
//		$balance = $this->Convert($balance);
//
//		$currency = $plus ? $this->Declension( $plus ) : $this->Declension( $minus );
//		$balance = $this->Convert( $balance );
//
//		$this->db->query( "INSERT INTO " . PREFIX . "_billing_history
//							(history_plugin, history_plugin_id, history_user_name, history_plus, history_minus, history_balance, history_currency, history_text, history_date) values
//							('$plugin', '$plugin_id', '$userlogin', '$plus', '$minus', '$balance', '$currency', '$desc', '".$this->_TIME."')" );
//
//		$sendData = [
//			'{date}' => langdate( "j F Y  G:i", $this->_TIME ),
//			'{login}' => $userlogin,
//			'{sum}'=> ( floatval($plus) ? "+$plus " . $this->Declension( $plus ) : "-$minus " . $this->Declension( $plus ) ),
//			'{comment}' => $desc,
//			'{balance}' => $balance . ' ' . $this->Declension( $balance ),
//		];
//
//		if( $this->settingAlert['email'] )
//		{
//			$searchUser = $this->db->super_query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE name='" . $userlogin . "'" );
//
//			$this->SendMail( 'balance', $sendData, $searchUser['email'] );
//		}
//
//		if( $this->settingAlert['pm'] )
//		{
//			if( ! $searchUser['user_id'] )
//				$searchUser = $this->db->super_query( "SELECT user_id FROM " . USERPREFIX . "_users WHERE name='" . $userlogin . "'" );
//
//			$this->SendPM( 'balance', $sendData, $searchUser['user_id'] );
//		}
//
//		//$this->Hooks( $user, $plus, $minus, $balance, $desc, $plugin, $plugin_id );
//
//		return true;
//	}
//
//    public function Convert( float|null $sum = 0, bool $dec = false, string $format = '' ) : string
//    {
//        $format = $format ?: $this->config['format'];
//        $sum = $format == 'int' ? intval( $sum ) : number_format(floatval($sum), 2, '.', '');
//
//        return $sum . ( $dec ? '&nbsp;' . $this->Declension($sum) : '' );
//    }
//
//    public function Declension( int $number, string $titles = '' )
//    {
//        if( $number < 0 ) $number = -$number;
//
//        $titles = $titles ?: $this->config['currency'];
//
//        $titles = explode(",", $titles );
//
//        if( count( $titles ) != 3 ) return $titles[0];
//
//        $cases = array (2, 0, 1, 1, 1, 2);
//
//        return $titles[ ($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)] ];
//    }

}
