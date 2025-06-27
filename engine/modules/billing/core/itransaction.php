<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2025
 */

namespace Billing;

Class iTransaction
{
    /**
     * Карточка транзакции
     * @param int $id
     * @return string
     */
    public static function sliderInfo( int $id ) : string
    {
        $transaction = \Billing\DB\Transaction::getById($id);

        return print_r($transaction,1);
    }
}