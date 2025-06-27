<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

//Class Query
//{
//    private static self $instance;
//
//    private function __construct(){}
//    private function __clone()    {}
//    private function __wakeup()   {}
//
//    /**
//     * dle
//     * @var array
//     */
//    private static array $global = [];
//
//    /**
//     * @return static
//     */
//    public static function Init() : self
//    {
//        if ( empty(self::$instance) )
//        {
//            global $db, $member_id, $_TIME, $config;
//
//            self::$instance = new self();
//
//            $params = file_exists( ENGINE_DIR . '/data/billing/config.php' ) ? require ENGINE_DIR . '/data/billing/config.php' : throw new \BalanceException('Unable to load config file');
//
//            self::$global = [
//                'DB' => $db,
//                'USER' => $member_id,
//                'TIME' => $_TIME,
//                'DLE' => $config,
//                'BILLING' => $params
//            ];
//        }
//
//        return self::$instance;
//    }
//
//    /**
//     * Обновить квитанцию
//     * @param int $id
//     * @param ...$params
//     * @return void
//     */
//    public function updateInvoice( int $id, ...$params ) : void
//    {
//        $fields = [];
//
//        foreach ($params as $key => $value)
//        {
//            $key = self::$global['DB']->safesql($key);
//            $value = self::$global['DB']->safesql($value);
//
//            $fields[] = "{$key} = '" . $value . "'";
//        }
//
//        self::$global['DB']->query( "UPDATE " . USERPREFIX . "_billing_invoice SET " . implode(', ', $fields) . " WHERE invoice_id = {$id}" );
//    }
//}