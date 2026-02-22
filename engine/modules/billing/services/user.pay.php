<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Services\User;

use Billing\BalanceException;
use \Billing\DevTools;
use \Billing\IPayment;

Class Pay
{
    /**
     * @var DevTools
     */
    public DevTools $DevTools;

    /**
     * @var array
     */
    private array $PaymentsArray = [];

    /**
     * Страница пополнения баланса
     * @param array $GET
     * @return string
     * @throws \Exception
     */
    public function main( array $GET = [] ) : string
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

            $this->DevTools->LQuery->where(
                [
                    "invoice_user_name = '{s}' " => $this->DevTools->member_id['name'],
                    "invoice_date_pay = '0' " => 1
                ]
            );

            if( $this->DevTools->config['invoice_max_num'] and $this->DevTools->LQuery->getInvoicesCount() >= $this->DevTools->config['invoice_max_num'] )
            {
                return $this->DevTools->ThemeMsg( $this->DevTools->lang['pay_error_title'], sprintf( $this->DevTools->lang['invoice_max_num'], $this->DevTools->config['invoice_max_num'] ) );
            }

            if( ! $_ConvertSum = \Billing\Api\Balance::Init()->Convert( $_POST['billingPaySum'] ) )
            {
                throw new \Exception($this->DevTools->lang['pay_summa_error']);
            }

            $invoiceId = \Billing\Api\Balance::Init()->checkDouble()->createInvoice(
                userId: $this->DevTools->member_id['user_id'],
                sum_get: $_ConvertSum,
                sum_pay: $_ConvertSum
            );

            $dataMail = [
                '{id}' => $invoiceId,
                '{login}' => $this->DevTools->member_id['name'],
                '{sum_get}' => $_ConvertSum . ' ' . \Billing\Api\Balance::Init()->Declension( $_ConvertSum ),
                '{link}' => $this->DevTools->dle['http_home_url'] . $this->DevTools->config['page'] . '.html/pay/waiting/id/' . $invoiceId
            ];

            if( $this->DevTools->config['mail_paynew_pm'] )
            {
                (new \Billing\Api\Email(userId: $this->DevTools->member_id['user_id']))
                    ->loadTemplate('new')
                    ->buildTemplate($dataMail)
                    ->send();
            }
            if( $this->DevTools->config['mail_paynew_email'] )
            {
                (new \Billing\Api\Message(userId: $this->DevTools->member_id['user_id']))
                    ->loadTemplate('new')
                    ->buildTemplate($dataMail)
                    ->send();
            }

            header( 'Location: /' . $this->DevTools->config['page'].'.html/pay/waiting/id/' . $invoiceId );

            return '';
        }

        # Форма создания платежа
        #
        $Tpl = $this->DevTools->ThemeLoad( 'pay/start' );

        $GetSum = $GET['sum'] ? \Billing\Api\Balance::Init()->Convert( $GET['sum'] ) : $this->DevTools->config['sum'];

        $this->DevTools->ThemeSetElement( "{module.get.currency}", \Billing\Api\Balance::Init()->Declension( floatval($GetSum) ) );
        $this->DevTools->ThemeSetElement( "{module.currency}", $this->DevTools->config['currency'] );
        $this->DevTools->ThemeSetElement( "{module.format}", $this->DevTools->config['format'] == 'int' ? 0 : 2 );
        $this->DevTools->ThemeSetElement( "{get.sum}", $GetSum );

        return $this->DevTools->Show( $Tpl );
    }

    /**
     * Страницы результата оплаты
     * @param array $get
     * @return mixed
     * @throws \Exception
     */
    public function ok(array $get = []) : string
    {
        return $this->DevTools->Show( $this->DevTools->ThemeLoad( "pay/success" ) );
    }

    public function bad(array $get = []) : string
    {
        return $this->DevTools->Show( $this->DevTools->ThemeLoad( "pay/fail" ) );
    }

    /**
     * Квитанция, переход к оплате
     * @param array $GET
     * @return string
     * @throws \Exception
     */
    public function waiting( array $GET = [] ) : string
    {
        $GET['id'] = intval($GET['id']);

        $InfoPay = [];

        $this->PaymentsArray = $this->DevTools->Payments();

        $Invoice = $this->DevTools->LQuery->getInvoiceById( $GET['id'] );

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
                'currency' => \Billing\Api\Balance::Init()->Declension($Invoice['invoice_pay']),
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
        $this->DevTools->ThemeSetElement( "{invoice.get.currency}", \Billing\Api\Balance::Init()->Declension( $Invoice['invoice_pay'] ) );

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

                if( $Handler = DevTools::getHandler($pluginHandler, $fileHandler) )
                {
                    $Handler->prepay($Invoice, $InfoPay, $more_data);
                }
            }

            # Процесс проверки купона
            #
            if( $couponKey = trim( $_POST['coupon'] ) )
            {
                $this->DevTools->CheckHash( $_POST['billingHash'] );

                $this->DevTools->ThemeSetElement( "[coupon_result]", "" );
                $this->DevTools->ThemeSetElement( "[/coupon_result]", "" );

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
                $this->DevTools->ThemeSetElementBlock( "coupon_result", '' );
                $this->DevTools->ThemeSetElement( "{coupon_result}", "" );
            }

            if( $_coupon )
            {
                $this->DevTools->ThemeSetElement( "[old]", "" );
                $this->DevTools->ThemeSetElement( "[/old]", "" );

                $this->DevTools->ThemeSetElement( "{coupon}", $couponData['coupon_key'] );

                $this->DevTools->ThemeSetElement( "{old.invoice.get}", $Invoice['invoice_get'] );
                $this->DevTools->ThemeSetElement( "{old.invoice.get.currency}", \Billing\Api\Balance::Init()->Declension( $Invoice['invoice_get'] ) );

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

                $this->DevTools->ThemeSetElement( "{invoice.get}", \Billing\Api\Balance::Init()->Convert(money:$Invoice['invoice_get'], number_format_f: true) );
                $this->DevTools->ThemeSetElement( "{invoice.get.currency}", \Billing\Api\Balance::Init()->Declension( $Invoice['invoice_get'] ) );
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

                $this->DevTools->LQuery->sanitize( $_POST['billingPayment'], '~[^a-z|0-9|\-|.]*~is' );

                $_Payment = $this->PaymentsArray[$_POST['billingPayment']]['config'];

                $Invoice['invoice_paysys'] = $_POST['billingPayment'];

                if($_Payment['convert'])
                {
                    $Invoice['invoice_pay'] = \Billing\Api\Balance::Init()->Convert($Invoice['invoice_get'] * $_Payment['convert'], $_Payment['format']);
                }

                # Есть обработчик
                #
                if( isset($Handler) )
                {
                    $Handler->prepay_check($Invoice, $InfoPay);
                }

                # Оплата с баланса
                #
                if( $from_balance and $_POST['billingPayment'] == 'balance' )
                {
                    if( floatval($Invoice['invoice_get']) <= floatval($this->DevTools->BalanceUser) )
                    {
                        $logData = (isset($Handler) ) ? $Handler->desc($InfoPay) : ['null', 0];

                        if( $_coupon and ! $this->DevTools->LQuery->useCoupon($couponData, $Invoice) )
                        {
                            throw new \Exception($this->DevTools->lang['coupon_use_error']);
                        }

                        try
                        {
                            \Billing\Api\Balance::Init()->Transaction()->Comment(
                                userLogin: $this->DevTools->member_id['name'],
                                minus: $Invoice['invoice_get'],
                                comment: $logData[0],
                                plugin_id: $logData[1],
                                plugin_name: $pluginHandler ?? 'null',
                                pm: (bool)$this->config['mail_payok_pm'],
                                email: (bool)$this->config['mail_payok_email']
                            )->From(
                                userLogin: $this->DevTools->member_id['name'],
                                sum: $Invoice['invoice_get']
                            )->sendEvent()->Commit();

                            if( $this->DevTools->invoiceRegisterPay( $Invoice, $this->DevTools->member_id['name'] ) )
                            {
                                if( $_GET['modal'] )
                                {
                                    $this->DevTools->ThemeSetElement( '[modal]', '' );
                                    $this->DevTools->ThemeSetElement( "[/modal]", '' );
                                }
                                else
                                {
                                    $this->DevTools->ThemeSetElementBlock( 'modal', '' );
                                }

                                return $this->DevTools->Show(
                                    $this->DevTools->ThemeLoad( 'pay/success' )
                                );
                            }
                        }
                        catch (\BalanceException $e)
                        {
                            throw new \Exception(
                                $e->getMessage()
                            );
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
                            \Billing\Api\Balance::Init()->Declension( $_Payment['minimum'] )
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
                            \Billing\Api\Balance::Init()->Declension( $_Payment['max'] )
                        )
                    );
                }

                if( $Payment = DevTools::getPayment($Invoice['invoice_paysys']) )
                {
                    $payForm = '';

                    if( $this->DevTools->config['redirect'] )
                    {
                        $payForm = '<script type="text/javascript">window.onload = function() { document.getElementById("paysys_form").submit(); }</script>';
                    }

                    if( $_coupon and $this->DevTools->LQuery->useCoupon($couponData, $Invoice) )
                    {
                        $this->DevTools->LQuery->updateInvoice(
                            invoice_id: $GET['id'],
                            wait: true,
                            invoice_pay: $Invoice['invoice_pay']
                        );
                    }

                    if( property_exists($Payment, 'DevTools') )
                    {
                        $Payment->DevTools = $this->DevTools;
                    }

                    $payForm .= $Payment->Form(
                            $GET['id'],
                            $this->PaymentsArray[$Invoice['invoice_paysys']]['config'],
                            $Invoice,
                            \Billing\Api\Balance::Init()->Declension( $Invoice['invoice_get'] ),
                            sprintf( $this->DevTools->lang['pay_desc'], $this->DevTools->member_id['name'], $Invoice['invoice_get'], \Billing\Api\Balance::Init()->Declension( $Invoice['invoice_get'] ) )
                        );

                    if( $_GET['modal'] )
                    {
                        echo $this->DevTools->Show( str_replace('<form', '<form target="_blank"', $payForm) );
                        exit;
                    }

                    return $payForm;
                }

                throw new \Exception($this->DevTools->lang['pay_file_error']);
            }
        }

        return $this->DevTools->Show( $Content );
    }

    /**
     * Обработчик платежей
     * @param array $GET
     * @return void
     * @throws BalanceException
     */
    public function handler( array $GET = [] ) : void
    {
        header($_SERVER['SERVER_PROTOCOL'].' HTTP 200 OK', true, 200);
        header( "Content-type: text/html; charset=" . $this->DevTools->dle['charset'] );

        @http_response_code(200);

        $secretKey = $this->DevTools->LQuery->sanitize( $GET['key'], '~[^a-z|0-9|\-|.]*~is' );
        $getPayment = $this->DevTools->LQuery->sanitize( $GET['payment'], '~[^a-z|0-9|\-|.]*~is' );

        $this->PaymentsArray = $this->DevTools->Payments();

        # .. логирование
        #
        $this->logging( 0, $getPayment );

        # .. полученные данные
        #
        $DATA = $this->ClearData( $_REQUEST );

        $this->logging( 1, str_replace("\n", "<br>", print_r( $DATA, true )) );

        # Проверка ключа
        #
        if( ! isset( $secretKey ) or $secretKey != $this->DevTools->config['secret'] )
        {
            $this->logging( 3 );

            die( $this->DevTools->lang['pay_getErr_key'] );
        }

        # Проверка системы оплаты
        #
        if( ! isset( $getPayment ) or ! $this->PaymentsArray[$getPayment]['config']['status'] )
        {
            $this->logging( 4 );

            die( $this->DevTools->lang['pay_getErr_paysys'] );
        }

        $this->logging( 5 );

        # Подключение класса системы оплаты
        #
        if( $Payment = DevTools::getPayment($getPayment) )
        {
            $this->logging( 6 );

            # ..номер квитанции
            #
            $getInvoiceID = $Payment->check_id( $DATA );

            $payerRequisites = '';

            if( in_array('check_payer_requisites', get_class_methods($Payment) ) )
            {
                $payerRequisites = $Payment->check_payer_requisites( $DATA );
            }

            if( ! intval( $getInvoiceID ) )
            {
                $this->logging( 7 );

                die( $this->billingMessage($Payment, $this->DevTools->lang['handler_error_id']) );
            }

            $this->logging( 8, $getInvoiceID );

            # .. данные квитанции
            #
            $Invoice = $this->DevTools->LQuery->getInvoiceById( $getInvoiceID );

            if( ! $Invoice )
            {
                $this->logging( 15 );

                die( $this->billingMessage($Payment, $this->DevTools->lang['pay_invoice_error']) );
            }

            if( $Invoice['invoice_date_pay'] )
            {
                $this->logging( 16 );

                die( $this->billingMessage($Payment, $this->DevTools->lang['pay_invoice_pay']) );
            }

            $Invoice['invoice_paysys'] = $getPayment;

            # если цена не по купону -> конвертируем
            #
            $InfoPay = unserialize($Invoice['invoice_payer_info']);

            if( ! $InfoPay['coupon']['coupon_id'] )
            {
                $Invoice['invoice_pay'] = \Billing\Api\Balance::Init()->Convert($Invoice['invoice_pay'] * $this->PaymentsArray[$getPayment]['config']['convert'], $this->PaymentsArray[$getPayment]['config']['format']);
            }

            # .. проверка параметров запроса пс
            #
            $paymentVerification = $Payment->check_out( $DATA, $this->PaymentsArray[$getPayment]['config'], $Invoice );

            if( $paymentVerification === true )
            {
                $this->logging( 9, '200' );

                if( $this->DevTools->invoiceRegisterPay( $Invoice, $payerRequisites ) )
                {
                    $this->logging( 10, $Invoice['invoice_get'] . ' ' . \Billing\Api\Balance::Init()->Declension( $Invoice['invoice_get'] ) );
                    $this->logging( 14 );

                    echo $Payment->check_ok( $DATA );
                }
                else
                {
                    $this->logging( 11 );

                    echo $this->DevTools->lang['pay_getErr_invoice'];
                }
            }
            else
            {
                $this->logging( "9.1", $paymentVerification );

                echo $paymentVerification;
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
     * @param IPayment $Payment
     * @param string|null $text
     * @return mixed
     */
    private function billingMessage( IPayment $Payment, ?string $text = '' ) : string
    {
        if( in_array('null_info', get_class_methods($Payment) ) )
        {
            return $Payment->null_info( $text );
        }

        return $text;
    }

    /**
     * Логирование
     * TODO: replace new method
     * @param int $step
     * @param string $info
     * @return void
     */
    private function logging( int $step = 0, string $info = '' ) : void
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
     * @param array $data
     * @return mixed
     */
    private function ClearData( array $data ) : array
    {
        foreach( $data as $key => $val )
        {
            if( in_array( $key, ['do', 'page', 'seourl', 'route', 'key'] ) ) unset( $data[$key] );
        }

        return $data;
    }
}