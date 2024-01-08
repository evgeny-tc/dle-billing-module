<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

abstract class Hooks
{
    protected array $configPlugin;
    protected Api $API;

    abstract function init(array $pluginConfig, Api $API) : void;
    abstract function pay(string $user, ?float $plus, ?float $minus, float $balance, ?string $desc, ?string $plugin = '', ?int $plugin_id = 0 ) : void;
}