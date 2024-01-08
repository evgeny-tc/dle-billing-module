<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

interface IPayment
{
    /**
     * Массив настроек для редактирования в админ.панели
     * @param array $config
     * @return array
     */
    public function Settings( array $config ) : array;

    /**
     * Форма с данными для отправки на сайт платежной системы
     * @param int $id
     * @param array $config_payment
     * @param array $invoice
     * @param string $currency
     * @param string $desc
     * @return string
     */
    public function Form( int $id, array $config_payment, array $invoice, string $currency, string $desc ) : string;

    /**
     * ID квитанции из данных, полученных от сервера платежной системы
     * @param array $result
     * @return int
     */
    public function check_id( array $result ) : int;

    /**
     * Статус оплаты на запрос от платежной системы
     * @param array $result
     * @return string
     */
    public function check_ok( array $result ) : string;

    /**
     * Проверяет принятые от платежной системы данные
     * В случае успешной проверки - возвращает (bool) true, иначе - (string)сообщение об ошибке
     * @param array $result
     * @param array $config_payment
     * @param array $invoice
     * @return string|bool
     */
    public function check_out( array $result, array $config_payment, array $invoice ) : string|bool;
}