<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

abstract class Handler
{
    /**
     * Процесс оплаты заказа, выполняется перед списанием средств, но после всех проверок
     * @param array $Invoice
     * @param API $API
     * @return bool
     */
    abstract function pay(array $Invoice, API $API) : bool;

    /**
     * Возвращает массив с описанием платежа для истории движения средств
     * @param array $info
     * @return array
     */
    abstract function desc(array $info = []) : array;

    /**
     * Проверяет дополнительные поля (может изменять их) перед платежом
     * @param array $invoice
     * @param array|bool $info
     * @return mixed
     */
    abstract function prepay_check( array $invoice, array|bool &$info ) : void;

    /**
     * Дополнительные поля с информацией об оплате
     * @param array $invoice
     * @param array|bool $info
     * @param array $more_data
     * @return void
     */
    abstract function prepay( array $invoice, array|bool $info, array &$more_data ) : void;
}