<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

namespace Billing;

Class USER
{
    public DevTools $DevTools;

    private array $PaymentsArray = [];

    /**
     * Страница пополнения баланса
     * @param array $GET
     * @return void
     * @throws Exception
     */
    public function main( array $GET = [] )
    {
        # Проверка авторизации
        #
        if( ! $this->DevTools->member_id['name'] )
        {
            throw new \Exception($this->DevTools->lang['pay_need_login']);
        }

        $this->PaymentsArray = $this->DevTools->Payments();

        # Создание квитанции
        #
        if( isset($_POST['submit']) )
        {
            $this->DevTools->CheckHash( $_POST['billingHash'] );

            $this->DevTools->LQuery->DbWhere( array(
                "invoice_user_name = '{s}' " => $this->DevTools->member_id['name'],
                "invoice_date_pay = '0' " => 1
            ));

            if( $this->DevTools->config['invoice_max_num'] and $this->DevTools->LQuery->DbGetInvoiceNum() >= $this->DevTools->config['invoice_max_num'] )
            {
                return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], sprintf( $this->DevTools->lang['invoice_max_num'], $this->DevTools->config['invoice_max_num'] ) );
            }

            if( ! $_ConvertSum = $this->DevTools->API->Convert( $_POST['billingPaySum'] ) )
            {
                throw new \Exception($this->DevTools->lang['pay_summa_error']);
            }

            $_InvoiceID = $this->DevTools->LQuery->DbCreatInvoice(
                payment_name: '',
                username: $this->DevTools->member_id['name'],
                sum_get: $_ConvertSum,
                sum_pay: $_ConvertSum
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
        $Tpl = $this->DevTools->ThemeLoad( 'pay/start' );

        $GetSum = $GET['sum'] ? $this->DevTools->API->Convert( $GET['sum'] ) : $this->DevTools->config['sum'];

        $this->DevTools->ThemeSetElement( "{module.get.currency}", $this->DevTools->API->Declension( $GetSum ) );
        $this->DevTools->ThemeSetElement( "{module.currency}", $this->DevTools->config['currency'] );
        $this->DevTools->ThemeSetElement( "{module.format}", $this->DevTools->config['format'] == 'int' ? 0 : 2 );
        $this->DevTools->ThemeSetElement( "{get.sum}", $GetSum );

        return $this->DevTools->Show( $Tpl );
    }

    /**
     * Страницы результата оплаты
     * @param array $GET
     * @return mixed
     */
    public function ok(array $GET = [])
    {
        return $this->DevTools->Show( $this->DevTools->ThemeLoad( "pay/success" ) );
    }

    public function bad(array $GET = [])
    {
        return $this->DevTools->Show( $this->DevTools->ThemeLoad( "pay/fail" ) );
    }

    /**
     * Квитанция, переход к оплате
     * @param array $GET
     * @return mixed|string
     * @throws Exception
     */
    public function waiting( array $GET )
    {
        $GET['id'] = intval($GET['id']);

        $Content = '';

        $InfoPay = [];

        $this->PaymentsArray = $this->DevTools->Payments();

        $Invoice = $this->DevTools->LQuery->DbGetInvoiceByID( $GET['id'] );

        if( ! $Invoice or $this->DevTools->checkUser( $Invoice['invoice_user_name'] ) === false )
        {
            throw new \Exception($this->DevTools->lang['pay_invoice_error']);
        }

        # Оплата с баланса
        #
        $this->PaymentsArray['balance'] = [
            'title' => $this->DevTools->lang['title_short'],
            'config' => [
                'title' => $this->DevTools->lang['title_short'],
                'currency' => $this->DevTools->API->Declension($Invoice['invoice_pay']),
                'convert' => 1
            ]
        ];

        # Купоны
        #
        if( $this->DevTools->config['coupons'] and $Invoice['invoice_handler'] )
        {
            $this->DevTools->ThemeSetElement( "[coupon]", '' );
            $this->DevTools->ThemeSetElement( "[/coupon]", '' );
        }
        else
        {
            $this->DevTools->ThemeSetElementBlock( "coupon", '' );
        }

        $this->DevTools->ThemeSetElement( "{invoice.id}", $Invoice['invoice_id'] );

        $this->DevTools->ThemeSetElement( "{invoice.date.create}", langdate( "j.m.Y H:i", $Invoice['invoice_date_creat']) );
        $this->DevTools->ThemeSetElement( "{invoice.date.pay}", langdate( "j.m.Y H:i", $Invoice['invoice_date_pay']) );

        $this->DevTools->ThemeSetElement( "{invoice.payment.tag}", $Invoice['invoice_paysys'] );
        $this->DevTools->ThemeSetElement( "{invoice.payment.title}", $this->PaymentsArray[$Invoice['invoice_paysys']]['config']['title'] );

        $this->DevTools->ThemeSetElement( "{invoice.pay}", $Invoice['invoice_pay'] );
        $this->DevTools->ThemeSetElement( "{invoice.pay.currency}",  $this->PaymentsArray[$Invoice['invoice_paysys']]['config']['currency']  );

        $this->DevTools->ThemeSetElement( "{invoice.get}", $Invoice['invoice_get'] );
        $this->DevTools->ThemeSetElement( "{invoice.get.currency}", $this->DevTools->API->Declension( $Invoice['invoice_pay'] ) );

        if( $Invoice['invoice_payer_info'] )
        {
            $this->DevTools->ThemeSetElement( "{invoice.desc}", $this->DevTools->lang['invoice_good_desc2'] );
        }
        else
        {
            $this->DevTools->ThemeSetElement( "{invoice.desc}", $this->DevTools->lang['invoice_good_desc'] );
        }

        # Квитанция оплачена
        #
        if( $Invoice['invoice_date_pay'] )
        {
            $Content = $this->DevTools->ThemeLoad( "pay/ok" );
        }
        else
        {
            header("Cache-Control: no-cache");

            $this->DevTools->ThemeSetElement( "{id}", $GET['id'] );
            $this->DevTools->ThemeSetElement( "{title}", str_replace("{id}", $GET['id'], $this->DevTools->lang['pay_invoice']) );

            $from_balance = false;
            $_coupon = false;

            $couponData = [];
            $more_data = [];

            # есть обработчик
            #
            if( $Invoice['invoice_handler'] )
            {
                $InfoPay = unserialize($Invoice['invoice_payer_info']);

                if( isset($InfoPay['billing']['from_balance']) )
                {
                    $from_balance = true;
                }

                list($pluginHandler, $fileHandler) = DevTools::exInvoiceHandler($Invoice['invoice_handler']);

                if( file_exists( MODULE_PATH . '/plugins/' . $pluginHandler . '/handler.' . $fileHandler . '.php' ) )
                {
                    $Handler = include MODULE_PATH . '/plugins/' . $pluginHandler . '/handler.' . $fileHandler . '.php';

                    if( in_array('prepay', get_class_methods($Handler) ) )
                    {
                        $Handler->prepay($Invoice, $InfoPay, $more_data);
                    }
                }
            }

            # Процесс проверки купона
            #
            if( $couponKey = trim( $_POST['coupon'] ) )
            {
                $this->DevTools->CheckHash( $_POST['billingHash'] );

                if( $couponData = $this->DevTools->LQuery->getCoupon($couponKey) )
                {
                    $_coupon = true;

                    $this->DevTools->ThemeSetElement( "{coupon_result}", $this->DevTools->lang['coupon_use_ok'] );
                }
                else
                {
                    $this->DevTools->ThemeSetElement( "{coupon_result}", $this->DevTools->lang['coupon_use_notfound'] );
                }
            }
            else
            {
                $this->DevTools->ThemeSetElement( "{coupon_result}", "" );
            }

            if( $_coupon )
            {
                $this->DevTools->ThemeSetElement( "[old]", "" );
                $this->DevTools->ThemeSetElement( "[/old]", "" );

                $this->DevTools->ThemeSetElement( "{coupon}", $couponData['coupon_key'] );

                $this->DevTools->ThemeSetElement( "{old.invoice.get}", $Invoice['invoice_get'] );
                $this->DevTools->ThemeSetElement( "{old.invoice.get.currency}", $this->DevTools->API->Declension( $Invoice['invoice_get'] ) );

                if( $couponData['coupon_type'] == '1' )
                {
                    $Invoice['invoice_get'] -= $couponData['coupon_value'];
                }
                else
                {
                    $Invoice['invoice_get'] -= $Invoice['invoice_get'] / 100 * intval($couponData['coupon_value']);
                }

                if( $Invoice['invoice_get'] <= 0 )
                    $Invoice['invoice_get'] = 1;

                $this->DevTools->ThemeSetElement( "{invoice.get}", $Invoice['invoice_get'] );
                $this->DevTools->ThemeSetElement( "{invoice.get.currency}", $this->DevTools->API->Declension( $Invoice['invoice_get'] ) );
            }
            else
            {
                $this->DevTools->ThemeSetElement( "{coupon}", '' );
                $this->DevTools->ThemeSetElementBlock( "old", '' );
            }

            $Content = $this->DevTools->FormSelectPay( $Invoice['invoice_get'], $from_balance, $more_data );

            # Процесс оплаты
            #
            if( isset( $_POST['submit'] ) )
            {
                $this->DevTools->CheckHash( $_POST['billingHash'] );

                $this->DevTools->LQuery->parsVar( $_POST['billingPayment'], '~[^a-z|0-9|\-|.]*~is' );

                $_Payment = $this->PaymentsArray[$_POST['billingPayment']]['config'];

                $Invoice['invoice_paysys'] = $_POST['billingPayment'];

                if($_Payment['convert'])
                    $Invoice['invoice_pay'] = $this->DevTools->API->Convert($Invoice['invoice_get'] * $_Payment['convert'], $_Payment['format']);

                # есть обработчик
                #
                if( isset($Handler) and in_array('prepay_check', get_class_methods($Handler) ) )
                {
                    $Handler->prepay_check($Invoice, $InfoPay);
                }

                if( $from_balance and $_POST['billingPayment'] == 'balance' )
                {
                    if( floatval($Invoice['invoice_get']) <= floatval($this->DevTools->BalanceUser) )
                    {
                        $logData = (isset($Handler) and in_array('desc', get_class_methods($Handler))) ? $Handler->desc($InfoPay) : ['null', 0];

                        if( $_coupon and ! $this->DevTools->LQuery->useCoupon($couponData, $Invoice) )
                        {
                            throw new \Exception($this->DevTools->lang['coupon_use_error']);
                        }

                        $resultPay = $this->DevTools->API->MinusMoney(
                            $this->DevTools->member_id['name'],
                            $Invoice['invoice_get'],
                            $logData[0],
                            $pluginHandler ?? 'null',
                            $logData[1]
                        );

                        if( $resultPay and $this->RegisterPay( $Invoice, $this->DevTools->member_id['name'] ) )
                        {
                            return $this->DevTools->Show( $this->DevTools->ThemeLoad( 'pay/success' ) );
                        }

                        return $this->DevTools->Show( $this->DevTools->ThemeLoad( 'pay/fail' ) );
                    }

                    throw new \Exception( $this->DevTools->lang['pay_sum_error'] );
                }
                else if( ! $_Payment['status'] )
                {
                    throw new \Exception( $this->DevTools->lang['pay_paysys_error'] );
                }
                else if( $Invoice['invoice_get'] < $_Payment['minimum'] )
                {
                    throw new \Exception(
                        sprintf(
                            $this->DevTools->lang['pay_minimum_error'],
                            $_Payment['title'],
                            $_Payment['minimum'],
                            $this->DevTools->API->Declension( $_Payment['minimum'] )
                        )
                    );
                }
                else if( $Invoice['invoice_get'] > $_Payment['max'] )
                {
                    throw new \Exception(
                        sprintf(
                            $this->DevTools->lang['pay_max_error'],
                            $_Payment['title'],
                            $_Payment['max'],
                            $this->DevTools->API->Declension( $_Payment['max'] )
                        )
                    );
                }

                if( file_exists( MODULE_PATH . '/payments/' . $Invoice['invoice_paysys'] . "/adm.settings.php" )  )
                {
                    require_once MODULE_PATH . '/payments/' . $Invoice['invoice_paysys'] . "/adm.settings.php";

                    $RedirectForm = $this->DevTools->config['redirect']  ? '<script type="text/javascript">
								window.onload = function() { document.getElementById("paysys_form").submit(); }
						</script>' : '';

                    if( $_coupon and $this->DevTools->LQuery->useCoupon($couponData, $Invoice) )
                    {
                        $this->DevTools->LQuery->DbInvoiceUpdate(
                            $GET['id'],
                            true,
                            '',
                            $Invoice['invoice_pay']
                        );
                    }

                    $payForm = $RedirectForm . $Paysys->Form(
                            $GET['id'],
                            $this->PaymentsArray[$Invoice['invoice_paysys']]['config'],
                            $Invoice,
                            $this->DevTools->API->Declension( $Invoice['invoice_get'] ),
                            sprintf( $this->DevTools->lang['pay_desc'], $this->DevTools->member_id['name'], $Invoice['invoice_get'], $this->DevTools->API->Declension( $Invoice['invoice_get'] ) )
                        );

                    if( $_GET['modal'] )
                    {
                        echo $this->DevTools->Show( str_replace('<form', '<form target="_blank"', $payForm) );;
                        exit;
                    }

                    return $payForm;
                }
                else
                {
                    throw new \Exception($this->DevTools->lang['pay_file_error']);
                }
            }
        }

        return $this->DevTools->Show( $Content );
    }

    /**
     * Обработчик платежей
     * @param array $GET
     * @return void
     */
    public function handler( array $GET = [] )
    {
        header($_SERVER['SERVER_PROTOCOL'].' HTTP 200 OK', true, 200);
        header( "Content-type: text/html; charset=" . $this->DevTools->dle['charset'] );

        @http_response_code(200);

        $SecretKey = $this->DevTools->LQuery->parsVar( $GET['key'], '~[^a-z|0-9|\-|.]*~is' );
        $GetPaysys = $this->DevTools->LQuery->parsVar( $GET['payment'], '~[^a-z|0-9|\-|.]*~is' );

        $this->PaymentsArray = $this->DevTools->Payments();

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
        if( ! isset( $GetPaysys ) or ! $this->PaymentsArray[$GetPaysys]['config']['status'] )
        {
            $this->logging( 4 );

            die( $this->DevTools->lang['pay_getErr_paysys'] );
        }

        $this->logging( 5 );

        # Подключение класса системы оплаты
        #
        if( file_exists( MODULE_PATH . '/payments/' . $GetPaysys . "/adm.settings.php" ) )
        {
            require_once MODULE_PATH . '/payments/' . $GetPaysys . "/adm.settings.php";

            $this->logging( 6 );

            # ..номер квитанции
            #
            $CheckID = $Paysys->check_id( $DATA );

            $CheckPayerRequisites = '';

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

            # если цена не по купону -> конвертируем
            #
            $InfoPay = unserialize($Invoice['invoice_payer_info']);

            if( ! $InfoPay['coupon']['coupon_id'] )
            {
                $Invoice['invoice_pay'] = $this->DevTools->API->Convert($Invoice['invoice_pay'] * $this->PaymentsArray[$GetPaysys]['config']['convert'], $this->PaymentsArray[$GetPaysys]['config']['format']);
            }

            # .. проверка параметров запроса пс
            #
            $CheckInvoice = $Paysys->check_out( $DATA, $this->PaymentsArray[$GetPaysys]['config'], $Invoice );

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

    /**
     * Вывод сообщения для ПС
     * @param $payment
     * @param $text
     * @return mixed
     */
    private function billingMessage( $payment, $text )
    {
        if( in_array('null_info', get_class_methods($payment) ) )
        {
            return $payment->null_info( $text );
        }

        return $text;
    }

    /**
     * Логирование
     * TODO: replace new method
     * @param $step
     * @param $info
     * @return bool
     */
    private function logging( int $step = 0, string $info = '' )
    {
        if( ! $this->DevTools->config['test'] ) return;

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
    }

    /**
     * Remove params
     * @param $DATA
     * @return mixed
     */
    private function ClearData( $DATA )
    {
        foreach( $DATA as $key=>$val )
        {
            if( in_array( $key, array( 'do', 'page', 'seourl', 'route', 'key' ) ) ) unset( $DATA[$key] );
        }

        return $DATA;
    }

    /**
     * Изменить статус квитанции, зачислить платеж
     * @param array $Invoice
     * @param $CheckPayerRequisites
     * @return bool|void
     */
    private function RegisterPay( array $Invoice, string|null $CheckPayerRequisites = '' )
    {
        $this->PaymentsArray = $this->DevTools->Payments();

        if( ! isset( $Invoice ) or $Invoice['invoice_date_pay'] )
        {
            return true;
        }

        $this->DevTools->LQuery->DbInvoiceUpdate(
            $Invoice['invoice_id'],
            false,
            $Invoice['invoice_paysys'],
            $Invoice['invoice_pay'],
            $CheckPayerRequisites
        );

        # есть обработчик
        #
        if( $Invoice['invoice_handler'] )
        {
            list($pluginHandler, $fileHandler) = DevTools::exInvoiceHandler($Invoice['invoice_handler']);

            $this->logging( 1, $Invoice['invoice_handler'] );

            if( file_exists( MODULE_PATH . '/plugins/' . $pluginHandler . '/handler.' . $fileHandler . '.php' ) )
            {
                $Handler = include MODULE_PATH . '/plugins/' . $pluginHandler . '/handler.' . $fileHandler . '.php';

                if( in_array('pay', get_class_methods($Handler) ) )
                {
                    $Handler->pay($Invoice, $this->DevTools->API);
                }
            }

            return true;
        }

        # .. отправить уведомление
        #
        $dataMail = array
        (
            '{id}' => $Invoice['invoice_id'],
            '{sum}' => $Invoice['invoice_pay'] . ' ' . $this->PaymentsArray[$Invoice['invoice_paysys']]['config']['currency'],
            '{login}' => $Invoice['invoice_user_name'],
            '{sum_get}' => $Invoice['invoice_get'] . ' ' . $this->DevTools->API->Declension( $Invoice['invoice_get'] ),
            '{payments}' => $this->PaymentsArray[$Invoice['invoice_paysys']]['title']
        );

        $SearchUser = $this->DevTools->LQuery->DbSearchUserByName( $Invoice['invoice_user_name'] );

        if( $this->DevTools->config['mail_payok_pm'] )
        {
            if( $this->DevTools->API->Alert( "payok", $dataMail, $SearchUser['user_id'] ) )
            {
                $this->logging( 15, "yes" );
            }
        }

        if( $this->DevTools->config['mail_payok_email'] )
        {
            $this->DevTools->API->Alert( "payok", $dataMail, 0, $SearchUser['email'] );
        }

        $this->DevTools->API->PlusMoney(
            $SearchUser['name'],
            $Invoice['invoice_get'],
            sprintf( $this->DevTools->lang['pay_msgOk'], $this->PaymentsArray[$Invoice['invoice_paysys']]['title'], $Invoice['invoice_pay'], $this->PaymentsArray[$Invoice['invoice_paysys']]['config']['currency'] ),
            'pay',
            $Invoice['invoice_id']
        );

        return true;
    }
}