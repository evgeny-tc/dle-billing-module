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
        }

        return self::$instance;
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
     * @param string $comment
     * @param int $plugin_id
     * @param string $plugin_name
     * @return $this
     * @throws BalanceException
     */
    public function Comment(int $userId = 0, string $userLogin = '', float $plus = 0, float $minus = 0, string $comment = '', int $plugin_id = 0, string $plugin_name = 'api') : self
    {
        $getUser = $this->getUser($userId, $userLogin);

        $plugin_name = self::$global['DB']->safesql($plugin_name);

        self::$global['DB']->query( "INSERT INTO " . PREFIX . "_billing_history
							(history_plugin, history_plugin_id, history_user_name, history_plus, history_minus, history_balance, history_currency, history_text, history_date) values
							('{$plugin_name}', '{$plugin_id}', '{$getUser['name']}', '{$plus}', '{$minus}', '{$getUser[self::getBalanceField()]}', '{$this->Declension( $plus ?: $minus )}', '{$comment}', '" . self::$global['TIME'] . "')" );

        return $this;
    }

    /**
     * Отправить событие в плагины
     * @return $this
     */
    public function Events() : self
    {
        //todo: hooks

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
            self::$global['DB']->query( "SELECT user_id, name, email, " . self::getBalanceField() . " FROM " . USERPREFIX . "_users WHERE user_id = '" . self::$global['DB']->safesql( $userLogin ) . "'" );
        }

        if( ! $user = self::$global['DB']->get_row())
        {
            throw new BalanceException('user.not_found');
        }

        return self::$buffer[md5($userId.$userLogin)] = $user;
    }

    /**
     * @param float|null $value
     * @param string|null $format
     * @param bool|null $separator_space
     * @return float|string
     */
    public function Convert(?float $value = 0, ?bool $separator_space = false, ?string $format = '') : float|string
    {
        $format = $format ?: self::$global['BILLING']['format'];

        $decimal = $format == 'int' ? 0 : 2;

        $decimal_separator = $format == 'int' ? '' : '.';
        $separator = $separator_space ? ' ' : '';

        return number_format(
            $value,
            $decimal,
            $decimal_separator,
            $separator
        );
    }

    /**
     * @param float $value
     * @param array|null $titles
     * @return string
     */
    public function Declension(float $value, ?array $titles = []) : string
    {
        $titles = $titles ?: explode(',', self::$global['BILLING']['currency']);

        if( count( $titles ) != 3 )
        {
            return $titles[0];
        }

        $cases = array (2, 0, 1, 1, 1, 2);

        return $titles[ ($value % 100 > 4 && $value % 100 < 20) ? 2 : $cases[min($value % 10, 5)] ];
    }

    /**
     * @return string
     */
    protected static function getBalanceField() : string
    {
        return self::$global['BILLING']['fname'];
    }
}
