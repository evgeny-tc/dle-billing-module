<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

Class USER
{
	var $Payments = array();

	# Страница пополнения баланса
	#
	function main( $GET )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] ) return $this->DevTools->lang['pay_need_login'];

		$PaymentsArray = $this->Payments();

		# Создание квитанции
		#
		if( isset($_POST['submit']) )
		{
			$this->DevTools->LQuery->DbWhere( array(
				"invoice_user_name = '{s}' " => $this->DevTools->member_id['name'],
				"invoice_date_pay = '0' " => 1
			));

			if( $this->DevTools->LQuery->DbGetInvoiceNum() >= $this->DevTools->config['invoice_max_num'] )
			{
				return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], sprintf( $this->DevTools->lang['invoice_max_num'], $this->DevTools->config['invoice_max_num'] ) );
			}

			$_Sum = $this->DevTools->LQuery->db->safesql( $_POST['billingPaySum'] );

			$Error = "";

			if( ! isset( $_POST['billingHash'] ) or $_POST['billingHash'] != $this->DevTools->hash() )
			{
				$Error = $this->DevTools->lang['pay_hash_error'];
			}
			else if( ! $this->DevTools->API->Convert( $_Sum ) )
			{
				$Error = $this->DevTools->lang['pay_incorect_sum'];
			}
			else if( ! $_Sum )
			{
				$Error = $this->DevTools->lang['pay_summa_error'];
			}

			if( $Error )
			{
				return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $Error );
			}

			$_ConvertSum = $this->DevTools->API->Convert( $_POST['billingPaySum'] );

			$_InvoiceID = $this->DevTools->LQuery->DbCreatInvoice(
				"",
				$this->DevTools->member_id['name'],
				$_ConvertSum,
				""
			);

			$dataMail = array(
				'{id}' => $_InvoiceID,
				'{login}' => $this->DevTools->member_id['name'],
				'{sum_get}' => $_ConvertSum . ' ' . $this->DevTools->API->Declension( $_ConvertSum ),
				'{link}' => $this->DevTools->dle['http_home_url'] . $this->DevTools->config['page'] . '.html/pay/waiting/id/' . $_InvoiceID,
			);

			if( $this->DevTools->config['mail_paynew_pm'] )
			{
				$this->DevTools->API->Alert( "new", $dataMail, $this->DevTools->member_id['user_id'] );
			}
			if( $this->DevTools->config['mail_paynew_email'] )
			{
				$this->DevTools->API->Alert( "new", $dataMail, 0, $this->DevTools->member_id['email'] );
			}

			header( 'Location: /' . $this->DevTools->config['page'].'.html/pay/waiting/id/' . $_InvoiceID );

			return;
		}

		# Форма создания платежа
		#
		$Tpl = $this->DevTools->ThemeLoad( "pay/start" );

		$PaysysList = '';

		$TplSelect = $this->DevTools->ThemePregMatch( $Tpl, '~\[payment\](.*?)\[/payment\]~is' );

		$GetSum = $GET['sum'] ? $this->DevTools->API->Convert( $GET['sum'] ) : $this->DevTools->config['sum'];

		$this->DevTools->ThemeSetElement( "{module.get.currency}", $this->DevTools->API->Declension( $GetSum ) );
		$this->DevTools->ThemeSetElement( "{module.currency}", $this->DevTools->config['currency'] );
		$this->DevTools->ThemeSetElement( "{module.format}", $this->DevTools->config['format'] == 'int' ? 0 : 2 );
		$this->DevTools->ThemeSetElement( "{get.sum}", $GetSum );
		$this->DevTools->ThemeSetElement( "{hash}", $this->DevTools->Hash() );

		return $this->DevTools->Show( $Tpl );
	}

	# Страницы результата оплаты
	#
	function ok()
	{
		return $this->DevTools->Show( $this->DevTools->ThemeLoad( "pay/success" ) );
	}

	function bad()
	{
		return $this->DevTools->Show( $this->DevTools->ThemeLoad( "pay/fail" ) );
	}

	# Квитанция, переход к оплате
	#
	function waiting( $GET )
	{
		# Проверка авторизации
		#
		if( ! $this->DevTools->member_id['name'] )
		{
			return $this->DevTools->lang['pay_need_login'];
		}

		$Content = '';
		$PaymentsArray = $this->Payments();
		$Invoice = $this->DevTools->LQuery->DbGetInvoiceByID( $GET['id'] );

		if( ! isset( $Invoice['invoice_paysys'] ) or $Invoice['invoice_user_name'] != $this->DevTools->member_id['name'] )
		{
			$Content = $this->DevTools->lang['pay_invoice_error'];
		}
		else
		{
			$this->DevTools->ThemeSetElement( "{invoive.payment.tag}", $Invoice['invoice_paysys'] );
			$this->DevTools->ThemeSetElement( "{invoive.payment.title}", $PaymentsArray[$Invoice['invoice_paysys']]['config']['title'] );
			$this->DevTools->ThemeSetElement( "{invoive.pay}", $Invoice['invoice_pay'] );
			$this->DevTools->ThemeSetElement( "{invoive.pay.currency}",  $PaymentsArray[$Invoice['invoice_paysys']]['config']['currency'] );
			$this->DevTools->ThemeSetElement( "{invoive.get}", $Invoice['invoice_get'] );
			$this->DevTools->ThemeSetElement( "{invoive.get.currency}", $this->DevTools->API->Declension( $Invoice['invoice_pay'] ) );

			# Квитанция оплачена
			#
			if( $Invoice['invoice_date_pay'] )
			{
				$Content = $this->DevTools->ThemeLoad( "pay/ok" );
			}
			else
			{
				header("Cache-Control: no-cache");

				$this->DevTools->ThemeSetElement( "{title}", str_replace("{id}", $GET['id'], $this->DevTools->lang['pay_invoice']) );

				if( isset( $_POST['submit'] ) )
				{
					$this->DevTools->LQuery->parsVar( $_POST['billingPayment'], '~[^a-z|0-9|\-|.]*~is' );

					$_Payment = $PaymentsArray[$_POST['billingPayment']]['config'];

					$Invoice['invoice_paysys'] = $_POST['billingPayment'];
					$Invoice['invoice_pay'] = $this->DevTools->API->Convert($Invoice['invoice_get'] * $_Payment['convert'], $_Payment['format']);

					$Error = "";

					if( ! isset( $_POST['billingHash'] ) or $_POST['billingHash'] != $this->DevTools->hash() )
					{
						$Error = $this->DevTools->lang['pay_hash_error'];
					}
					else if( ! $_Payment['status'] )
					{
						$Error = $this->DevTools->lang['pay_paysys_error'];
					}
					else if( $Invoice['invoice_get'] < $_Payment['minimum'] )
					{
						$Error = sprintf(
							$this->DevTools->lang['pay_minimum_error'],
							$_Payment['title'],
							$_Payment['minimum'],
							$this->DevTools->API->Declension( $_Payment['minimum'] )
						);
					}
					else if( $Invoice['invoice_get'] > $_Payment['max'] )
					{
						$Error = sprintf(
							$this->DevTools->lang['pay_max_error'],
							$_Payment['title'],
							$_Payment['max'],
							$this->DevTools->API->Declension( $_Payment['max'] )
						);
					}

					if( $Error )
					{
						return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], $Error );
					}

					$this->DevTools->ThemeSetElement( "{invoive.payment.tag}", $Invoice['invoice_paysys'] );
					$this->DevTools->ThemeSetElement( "{invoive.payment.title}", $PaymentsArray[$Invoice['invoice_paysys']]['config']['title'] );
					$this->DevTools->ThemeSetElement( "{invoive.pay}", $Invoice['invoice_pay'] );
					$this->DevTools->ThemeSetElement( "{invoive.pay.currency}",  $PaymentsArray[$Invoice['invoice_paysys']]['config']['currency'] );

					if( file_exists( DLEPlugins::Check( MODULE_PATH . '/payments/' . $Invoice['invoice_paysys'] . "/adm.settings.php" ) ) )
					{
						require_once DLEPlugins::Check( MODULE_PATH . '/payments/' . $Invoice['invoice_paysys'] . '/adm.settings.php' );

						if( $this->DevTools->config['redirect'] )
						{
							$RedirectForm = '	<script type="text/javascript">
													window.onload = function()
													{
														document.getElementById("paysys_form").submit();
													}
												</script>';
						}
						else
						{
							$RedirectForm = '';
						}

						$this->DevTools->ThemeSetElement( "{button}", $RedirectForm .
							$Paysys->Form(
								$GET['id'],
								$PaymentsArray[$Invoice['invoice_paysys']]['config'],
								$Invoice,
								$this->DevTools->API->Declension( $Invoice['invoice_get'] ),
								sprintf( $this->DevTools->lang['pay_desc'], $this->DevTools->member_id['name'], $Invoice['invoice_get'], $this->DevTools->API->Declension( $Invoice['invoice_get'] ) )
							)
						);

					}
					else
					{
						$this->DevTools->ThemeSetElement( "{button}", $this->DevTools->lang['pay_file_error'] );
					}

					$this->DevTools->ThemeSetElementBlock( "step1", "" );
					$this->DevTools->ThemeSetElement( "[step2]", "" );
					$this->DevTools->ThemeSetElement( "[/step2]", "" );

					$Content = $this->DevTools->ThemeLoad( "pay/waiting" );
				}
				else
				{
					$Tpl = $this->DevTools->ThemeLoad( "pay/waiting" );

					$PaysysList = '';

					$TplSelect = $this->DevTools->ThemePregMatch( $Tpl, '~\[payment\](.*?)\[/payment\]~is' );

					if( count( $PaymentsArray ) )
					{
						foreach( $PaymentsArray as $Name=>$Info )
						{
							$TimeLine = $TplSelect;

							$TimeLine = str_replace("{payment.name}", $Name, $TimeLine);
							$TimeLine = str_replace("{payment.title}", $Info['config']['title'], $TimeLine);
							$TimeLine = str_replace("{payment.topay}", $this->DevTools->API->Convert($Invoice['invoice_get'] * $Info['config']['convert'], $Info['config']['format']), $TimeLine);
							$TimeLine = str_replace("{payment.currency}", $Info['config']['currency'], $TimeLine);

							$PaysysList .= $TimeLine;
						}
					}
					else
					{
						$PaysysList = $this->DevTools->lang['pay_main_error'];
					}

					$this->DevTools->ThemeSetElementBlock( "payment", $PaysysList );
					$this->DevTools->ThemeSetElementBlock( "step2", "" );
					$this->DevTools->ThemeSetElement( "[step1]", "" );
					$this->DevTools->ThemeSetElement( "[/step1]", "" );

					$this->DevTools->ThemeSetElement( "{button}", "<input type=\"submit\" name=\"submit\" class=\"btn\" value=\"" . $this->DevTools->lang['pay_invoice_now'] . "\">" );

					$Content = "<form action=\"\" method=\"post\"><input type=\"hidden\" name=\"billingHash\" value=\"" . $this->DevTools->Hash() . "\" />" . $this->DevTools->ThemeLoad( "pay/waiting" ) . "</form>";
				}
			}
		}

		return $this->DevTools->Show( $Content );
	}

	# Обработчик платежей
	#
	function handler( $GET )
	{
		header($_SERVER['SERVER_PROTOCOL'].' HTTP 200 OK', true, 200);
		header( "Content-type: text/html; charset=" . $this->DevTools->dle['charset'] );

		@http_response_code(200);
		
		$SecretKey = $this->DevTools->LQuery->parsVar( $GET['key'], '~[^a-z|0-9|\-|.]*~is' );
		$GetPaysys = $this->DevTools->LQuery->parsVar( $GET['payment'], '~[^a-z|0-9|\-|.]*~is' );

		$PaymentsArray = $this->Payments();

		# .. логирование
		#
		$this->logging( 0, $GetPaysys );

		# .. полученные данные
		#
		$DATA = $this->ClearData( $_REQUEST );

		$this->logging( 1, str_replace("\n", "<br>", print_r( $DATA, true )) );

		# Проверка ключа
		#
		if( ! isset( $SecretKey ) or $SecretKey != $this->DevTools->config['secret'] )
		{
			$this->logging( 3 );

			die( $this->DevTools->lang['pay_getErr_key'] );
		}

		# Проверка системы оплаты
		#
		if( ! isset( $GetPaysys ) or ! $PaymentsArray[$GetPaysys]['config']['status'] )
		{
			$this->logging( 4 );

			die( $this->DevTools->lang['pay_getErr_paysys'] );
		}

		$this->logging( 5 );

		# Подключение класса системы оплаты
		#
		if( file_exists( DLEPlugins::Check( MODULE_PATH . '/payments/' . $GetPaysys . "/adm.settings.php" ) ) )
		{
			require_once DLEPlugins::Check( MODULE_PATH . '/payments/' . $GetPaysys . '/adm.settings.php' );

			$this->logging( 6 );

			# .. номер квитанции
			#
			$CheckID = $Paysys->check_id( $DATA );

			if( in_array('check_payer_requisites', get_class_methods($Paysys) ) )
			{
				$CheckPayerRequisites = $Paysys->check_payer_requisites( $DATA );
			}

			if( ! intval( $CheckID ) )
			{
				$this->logging( 7 );

				die( $this->billingMessage($Paysys, $this->DevTools->lang['handler_error_id']) );
			}

			$this->logging( 8, $CheckID );

			# .. данные квитанции
			#
			$Invoice = $this->DevTools->LQuery->DbGetInvoiceByID( $CheckID );

			if( ! $Invoice )
			{
				$this->logging( 15 );

				die( $this->billingMessage($Paysys, $this->DevTools->lang['pay_invoice_error']) );
			}

			if( $Invoice['invoice_date_pay'] )
			{
				$this->logging( 16 );

				die( $this->billingMessage($Paysys, $this->DevTools->lang['pay_invoice_pay']) );
			}

			$Invoice['invoice_paysys'] = $GetPaysys;
			$Invoice['invoice_pay'] =  $this->DevTools->API->Convert($Invoice['invoice_get'] * $PaymentsArray[$GetPaysys]['config']['convert'], $PaymentsArray[$GetPaysys]['config']['format']);

			# .. проверка параметров запроса пс
			#
			$CheckInvoice = $Paysys->check_out( $DATA, $PaymentsArray[$GetPaysys]['config'], $Invoice );

			if( $CheckInvoice === 200 )
			{
				$this->logging( 9, $CheckInvoice );

				if( $this->RegisterPay( $Invoice, $CheckPayerRequisites ) )
				{
					$this->logging( 10, $Invoice['invoice_get'] . ' ' . $this->DevTools->API->Declension( $Invoice['invoice_get'] ) );
					$this->logging( 14 );

					echo $Paysys->check_ok( $DATA );
				}
				else
				{
					$this->logging( 11 );

					echo $this->DevTools->lang['pay_getErr_invoice'];
				}
			}
			else
			{
				$this->logging( "9.1", $CheckInvoice );

				echo $CheckInvoice;
			}
		}
		else
		{
			$this->logging( 12 );

			echo $this->DevTools->lang['pay_file_error'];
		}

		exit();
	}

	# Вывод сообщения для ПС
	#
	private function billingMessage( $payment, $text )
	{
		if( in_array('null_info', get_class_methods($payment) ) )
		{
			return $payment->null_info( $text );
		}
		else
		{
			return $text;
		}
	}

	# Логирование
	#
	private function logging( $step = 0, $info = '' )
	{
		if( ! $this->DevTools->config['test'] ) return false;

		if( filesize('pay.logger.php') > 1024 and ! $step )
		{
			unlink('pay.logger.php');
		}

		if( ! file_exists( 'pay.logger.php' ) )
		{
			$handler = fopen( 'pay.logger.php', "a" );

			fwrite( $handler, "<?php if( !defined( 'BILLING_MODULE' ) ) die( 'Hacking attempt!' ); ?>\n");
		}
		else
		{
			$handler = fopen( 'pay.logger.php', "a" );
		}

		fwrite( $handler,
			$step . '|' .
			langdate( "j.m.Y H:i", $this->_TIME) . '|' .
			$info . "\n"
		);

		fclose( $handler );

		return true;
	}

	private function ClearData( $DATA )
	{
		foreach( $DATA as $key=>$val )
		{
			if( in_array( $key, array( 'do', 'page', 'seourl', 'route', 'key' ) ) ) unset( $DATA[$key] );
		}

		return $DATA;
	}

	# Изменить статус квитанции, зачислить платеж
	#
	private function RegisterPay( $Invoice, $CheckPayerRequisites )
	{
		$PaymentsArray = $this->Payments();

		if( ! isset( $Invoice ) or $Invoice['invoice_date_pay'] ) return;

		$this->DevTools->LQuery->DbInvoiceUpdate( $Invoice['invoice_id'], false, $Invoice['invoice_paysys'], $Invoice['invoice_pay'], $CheckPayerRequisites );

		# .. отправить уведомление
		#
		$dataMail = array
		(
			'{id}' => $Invoice['invoice_id'],
			'{sum}' => $Invoice['invoice_pay'] . ' ' . $PaymentsArray[$Invoice['invoice_paysys']]['config']['currency'],
			'{login}' => $Invoice['invoice_user_name'],
			'{sum_get}' => $Invoice['invoice_get'] . ' ' . $this->DevTools->API->Declension( $Invoice['invoice_get'] ),
			'{payments}' => $PaymentsArray[$Invoice['invoice_paysys']]['title']
		);

		$SearchUser = $this->DevTools->LQuery->DbSearchUserByName( $Invoice['invoice_user_name'] );

		if( $this->DevTools->config['mail_payok_pm'] )
		{
			$pmres = $this->DevTools->API->Alert( "payok", $dataMail, $SearchUser['user_id'] );

			if( $pmres )
			{
				 $this->logging( 15, $pmres );
			}
		}

		if( $this->DevTools->config['mail_payok_email'] )
		{
			$this->DevTools->API->Alert( "payok", $dataMail, 0, $SearchUser['email'] );
		}

		$this->DevTools->API->PlusMoney(
			$SearchUser['name'],
			$Invoice['invoice_get'],
			sprintf( $this->DevTools->lang['pay_msgOk'], $PaymentsArray[$Invoice['invoice_paysys']]['title'], $Invoice['invoice_pay'], $PaymentsArray[$Invoice['invoice_paysys']]['config']['currency'] ),
			'pay',
			$Invoice['invoice_id']
		);

		return true;
	}

	# Массив пс
	#
	private function Payments()
	{
		if( $this->Payments ) return $this->Payments;

		$List = opendir( MODULE_PATH . '/payments/' );

		while ( $name = readdir($List) )
		{
			if ( in_array($name, array(".", "..", "/", "index.php", ".htaccess")) ) continue;

			$this->Payments[$name] = parse_ini_file( MODULE_PATH . '/payments/' . $name . '/info.ini' );
			$this->Payments[$name]['config'] = file_exists( MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' ) ? include MODULE_DATA . '/payment.' . mb_strtolower($name) . '.php' : array();

			if( ! $this->Payments[$name]['config']['status'] )
			{
				unset( $this->Payments[$name] );
			}
		}

		return $this->Payments;
	}
}
?>