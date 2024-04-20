<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Api;

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

    public static function Init(?array $params = []) : self
	{
        if ( empty(self::$instance) )
		{
            global $db, $member_id, $_TIME, $config;

            self::$instance = new self();

            if( ! $params )
            {
                $params = file_exists( ENGINE_DIR . '/data/billing/config.php' ) ? require ENGINE_DIR . '/data/billing/config.php' : throw new \Exception('Unable to load config file');
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
}
