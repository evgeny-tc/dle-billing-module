<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

/**
 * Обработка платежей плагинами
 */
abstract class Hooks
{
    /**
     * Настройки плагина
     * @var array
     */
    protected array $configPlugin;

    /**
     * Подключить api class
     */
    function __construct()
    {
        if( ! class_exists('\Billing\Api\Balance') )
        {
            require_once ENGINE_DIR . '/modules/billing/api/balance.php';
        }
    }

    /**
     * Загрузить настройки плагина
     * @param array $pluginConfig
     * @return void
     */
    abstract function init(array $pluginConfig) : void;

    /**
     * Платеж
     * @param string $user
     * @param float|null $plus
     * @param float|null $minus
     * @param float $balance
     * @param string|null $desc
     * @param string|null $plugin
     * @param int|null $plugin_id
     * @return void
     */
    abstract function pay(string $user, ?float $plus, ?float $minus, float $balance, ?string $desc, ?string $plugin = '', ?int $plugin_id = 0 ) : void;
}