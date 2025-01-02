<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Api;

use \Billing\BalanceException;

/**
 * API Баланс пользователя
 * @api
 */
Class Balance
{
	private static self $instance;

	private function __construct(){}
    private function __clone()    {}
    private function __wakeup()   {}

    private static array $global = [];

    /**
     * Перед созданием квитанции проверить на дубли
     * @var bool
     */
    private bool $CHECK_INVOICE_DOUBLE = false;

    /**
     * Максимальная длина цепочки событий
     */
    const MAX_HOOK_EVENTS = 10;

    /**
     * Данные для события
     * @var array
     */
    private array $hook_data = [];

    /**
     * Текущее звено события
     * @var int
     */
    private int $hook_num = 0;

    /**
     * @param array|null $params
     * @return static
     * @throws \BalanceException
     */
    public static function Init(?array $params = []) : self
	{
        if ( empty(self::$instance) )
		{
            global $db, $member_id, $_TIME, $config;

            self::$instance = new self();

            if( ! $params )
            {
                $params = file_exists( ENGINE_DIR . '/data/billing/config.php' ) ? require ENGINE_DIR . '/data/billing/config.php' : throw new \BalanceException('Unable to load config file');
            }

            self::$global = [
                'DB' => $db,
                'USER' => $member_id,
                'TIME' => $_TIME,
                'DLE' => $config,
                'BILLING' => $params
            ];

            self::$instance->hook_num = 0;
            self::$instance->hook_data = [];
        }

        return self::$instance;
    }

    /**
     * @param bool $check
     * @return $this
     */
    public function checkDouble(bool $check = true) : self
    {
        $this->CHECK_INVOICE_DOUBLE = $check;

        return $this;
    }

    /**
     * Создать счет на оплату
     * @param int $userId - id
     * @param string $userLogin - name
     * @param string $userAnonymous - or user ip
     * @param string $payment
     * @param float $sum_get
     * @param float $sum_pay
     * @param mixed $payer_info
     * @param string $handler
     * @return int
     * @throws BalanceException
     */
    public function createInvoice(int $userId = 0, string $userLogin = '', string $userAnonymous = '', string $payment = '', float $sum_get = 0, float $sum_pay = 0, mixed $payer_info = '', string $handler = '') : int
    {
        $payment = self::$global['DB']->safesql( $payment );
        $handler = self::$global['DB']->safesql( $handler );

        if( $userAnonymous )
        {
            $getUser['name'] = $userAnonymous;
        }
        else
        {
            $getUser = $this->getUser($userId, $userLogin);
        }

        if( is_array( $payer_info ) )
        {
            foreach( $payer_info as $key => $info )
            {
                if( is_array($info) )
                {
                    foreach($info as $info_key => $info_val)
                    {
                        $payer_info[$key][$info_key] = preg_replace('/[^ a-z&#;@а-яA-ZА-Я\d.]/ui', '', $info_val );
                    }
                }
                else
                {
                    $payer_info[$key] = preg_replace('/[^ a-z&#;@а-яA-ZА-Я\d.]/ui', '', $info);
                }
            }

            $payer_info = serialize( $payer_info );
        }
        else
        {
            $payer_info = self::$global['DB']->safesql( $payer_info );
        }

        # Неавторизованный пользователь
        #
        $invoice_user_anonymous = $userAnonymous ? 1 : 0;

        # Проверка на дубль
        #
        if( $this->CHECK_INVOICE_DOUBLE )
        {
            $search_double = self::$global['DB']->super_query( "SELECT invoice_id FROM " . USERPREFIX . "_billing_invoice 
                                                                    where invoice_paysys = '{$payment}'
                                                                        and invoice_user_name = '{$getUser['name']}'
                                                                        and invoice_user_anonymous = '{$invoice_user_anonymous}'
                                                                        and invoice_get = '{$sum_get}'
                                                                        and invoice_pay = '{$sum_pay}'
                                                                        and invoice_payer_info = '{$payer_info}'
                                                                        and invoice_handler = '{$handler}'
                                                                        and invoice_date_pay = 0 " );

            if( intval( $search_double['invoice_id'] ) )
            {
                return $search_double['invoice_id'];
            }
        }

        self::$global['DB']->query( "INSERT INTO " . USERPREFIX . "_billing_invoice
							(invoice_paysys, invoice_user_name, invoice_user_anonymous, invoice_get, invoice_pay, invoice_date_creat, invoice_payer_info, invoice_handler) values
							('{$payment}',  '{$getUser['name']}', '{$invoice_user_anonymous}', '{$sum_get}', '{$sum_pay}', '" . self::$global['TIME'] . "', '{$payer_info}', '{$handler}')" );

        $this->checkDouble( false );

        return self::$global['DB']->insert_id();
    }

    /**
     * DB start transaction
     * @return $this
     */
    public function Transaction() : self
    {
        self::$global['DB']->query('START TRANSACTION');

        return $this;
    }

    /**
     * DB commit transaction
     * @return void
     */
    public function Commit() : void
    {
        self::$global['DB']->query('COMMIT');
    }

    /**
     * DB cancel transaction
     * @return void
     */
    public function Rollback() : void
    {
        self::$global['DB']->query('ROLLBACK');
    }

    /**
     * Списать
     * @param int $userId
     * @param string $userLogin
     * @param float $sum
     * @return $this
     * @throws BalanceException
     */
    public function From(int $userId = 0, string $userLogin = '', float $sum = 0) : self
    {
        $getUser = $this->getUser($userId, $userLogin);

        self::$global['DB']->query( "UPDATE " . USERPREFIX . "_users SET " . self::getBalanceField() . " = " . self::getBalanceField() . " - {$sum} WHERE user_id='{$getUser['user_id']}'");

        return $this;
    }

    /**
     * Начислить
     * @param int $userId
     * @param string $userLogin
     * @param float $sum
     * @return $this
     * @throws BalanceException
     */
    public function To(int $userId = 0, string $userLogin = '', float $sum = 0) : self
    {
        $getUser = $this->getUser($userId, $userLogin);

        self::$global['DB']->query( "UPDATE " . USERPREFIX . "_users SET " . self::getBalanceField() . " = " . self::getBalanceField() . " + {$sum} WHERE user_id='{$getUser['user_id']}'");

        return $this;
    }

    /**
     * Запись в журнал
     * @param int $userId
     * @param string $userLogin
     * @param float $plus
     * @param float $minus
     * @param string $comment
     * @param int $plugin_id
     * @param string $plugin_name
     * @param bool $pm
     * @param bool $email
     * @return $this
     * @throws BalanceException
     * @throws \Exception
     */
    public function Comment(int $userId = 0, string $userLogin = '', float $plus = 0, float $minus = 0, string $comment = '', int $plugin_id = 0, string $plugin_name = 'api', bool $pm = false, bool $email = false) : self
    {
        $getUser = $this->getUser($userId, $userLogin);
        $currency = $this->Declension( $plus ?: $minus );

        $plugin_name = self::$global['DB']->safesql($plugin_name);

        self::$global['DB']->query( "INSERT INTO " . PREFIX . "_billing_history
							(history_plugin, history_plugin_id, history_user_name, history_plus, history_minus, history_balance, history_currency, history_text, history_date) values
							('{$plugin_name}', '{$plugin_id}', '{$getUser['name']}', '{$plus}', '{$minus}', '{$getUser[self::getBalanceField()]}', '{$currency}', '{$comment}', '" . self::$global['TIME'] . "')" );

        $userReportBalance = $getUser [self::getBalanceField()] + $plus - $minus;

        # Событие в плагины
        #
        $this->hook_data = [
            'userId' => $getUser['user_id'],
            'userLogin' => $getUser['name'],
            'plus' => $plus,
            'minus' => $minus,
            'balance' => $userReportBalance,
            'comment' => $comment,
            'plugin_id' => $plugin_id,
            'plugin_name' => $plugin_name
        ];

        # Уведомления
        #
        $buildAlert = (new Alert(userId: $userId, name: $userLogin))->loadTemplate('balance')->buildTemplate(
            [
                '{date}' => langdate( "j F Y  G:i", self::$global['TIME'] ),
                '{login}' => $getUser['name'],
                '{sum}'=> ( $plus ? "+{$plus} {$currency}" : "-{$plus} {$currency}" ),
                '{comment}' => strip_tags($comment),
                '{balance}' => \Billing\Api\Balance::Init()->Convert(value: $userReportBalance, separator_space: true, declension: true)
            ]
        );

        if( $pm )
        {
            $buildAlert->pm();
        }

        if( $email )
        {
            $buildAlert->email();
        }

        return $this;
    }

    /**
     * Отправить событие в плагины
     * @param mixed ...$hook_new_data
     * @return Balance
     */
    function sendEvent(...$hook_new_data) : self
    {
        if( $this->hook_num <= self::MAX_HOOK_EVENTS )
        {
            $this->hook_num += 1;

            if( $hook_new_data )
            {
                $this->hook_data = array_merge($this->hook_data , $hook_new_data);
            }

            if( ! class_exists('\Billing\Hooks') )
            {
                require_once ENGINE_DIR . '/modules/billing/core/hooks.php';
            }

            $List = opendir( ENGINE_DIR . '/modules/billing/plugins/' );

            while ( $name = readdir($List) )
            {
                if ( in_array($name, [".", "..", "/", "index.php", ".htaccess"]) ) continue;

                if( file_exists( ENGINE_DIR . '/modules/billing/plugins/' . $name . '/hook.class.php' )
                    and file_exists( ENGINE_DIR . '/data/billing/plugin.' . $name . '.php' ))
                {
                    $Hook = include( ENGINE_DIR . '/modules/billing/plugins/' . $name . '/hook.class.php' );

                    if( $Hook instanceof \Billing\Hooks)
                    {
                        if( in_array('init', get_class_methods($Hook) ) )
                        {
                            $Hook->init(
                                include MODULE_DATA . '/plugin.' . $name . '.php'
                            );
                        }

                        $Hook->pay(
                            $this->hook_data['userLogin'],
                            $this->hook_data['plus'],
                            $this->hook_data['minus'],
                            $this->hook_data['balance'],
                            $this->hook_data['comment'],
                            $this->hook_data['plugin_name'],
                            $this->hook_data['plugin_id']
                        );
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Проверить достаточно ли средств
     * @param int $userId
     * @param string $userLogin
     * @param float $sum
     * @return $this
     * @throws BalanceException
     */
    public function Check(int $userId = 0, string $userLogin = '', float $sum = 0) : self
    {
        if( $this->getUser($userId, $userLogin)[self::getBalanceField()] < $sum )
        {
            throw new BalanceException('balance.check');
        }

        return $this;
    }

    protected static array $buffer = [];

    /**
     * Найти пользователя
     * @param int $userId
     * @param string $userLogin
     * @return array
     * @throws BalanceException
     */
    protected function getUser(int $userId = 0, string $userLogin = '') : array
    {
        if( self::$buffer[md5($userId.$userLogin)] )
        {
            return self::$buffer[md5($userId.$userLogin)];
        }

        if( $userId )
        {
            self::$global['DB']->query( "SELECT user_id, name, email, " . self::getBalanceField() . " FROM " . USERPREFIX . "_users WHERE user_id = '{$userId}'" );
        }

        if( $userLogin )
        {
            self::$global['DB']->query( "SELECT user_id, name, email, " . self::getBalanceField() . " FROM " . USERPREFIX . "_users WHERE name = '" . self::$global['DB']->safesql( $userLogin ) . "'" );
        }

        if( ! $user = self::$global['DB']->get_row())
        {
            throw new BalanceException('user.not_found:' . $userId . $userLogin);
        }

        return self::$buffer[md5($userId.$userLogin)] = $user;
    }

    /**
     * @param float|null $value
     * @param bool|null $separator_space
     * @param string|null $format
     * @param bool|null $declension
     * @return float|string
     */
    public function Convert(mixed $value = 0, ?bool $separator_space = false, ?string $format = '', ?bool $declension = false) : float|string
    {
        $value = floatval($value);

        $format = $format ?: self::$global['BILLING']['format'];

        $decimal = $format == 'int' ? 0 : 2;

        $decimal_separator = $format == 'int' ? '' : '.';
        $separator = $separator_space ? ' ' : '';

        return number_format(
            $value,
            $decimal,
            $decimal_separator,
            $separator
        )
            .
            ( $declension ? ' ' . $this->Declension($value) : '' );
    }

    /**
     * @param float $value
     * @param array|null $titles
     * @return string
     */
    public function Declension(mixed $value, ?array $titles = []) : string
    {
        $value = abs(floatval($value));

        $titles = $titles ?: explode(',', self::$global['BILLING']['currency']);

        if( count( $titles ) != 3 )
        {
            return $titles[0];
        }

        $cases = [2, 0, 1, 1, 1, 2];

        return $titles[ ($value % 100 > 4 && $value % 100 < 20) ? 2 : $cases[min($value % 10, 5)] ] ?? '';
    }

    /**
     * @return string
     */
    protected static function getBalanceField() : string
    {
        return self::$global['BILLING']['fname'];
    }
}
