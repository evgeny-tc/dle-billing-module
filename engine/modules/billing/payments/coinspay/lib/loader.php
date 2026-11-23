<?php
/**
 * CoinsPay for DLE-Billing: the autoloader of the library (CoinsPay\Dle\*, one class per file).
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */

if (!defined('COINSPAY_DLE_LIB')) {
    define('COINSPAY_DLE_LIB', __DIR__);

    spl_autoload_register(static function (string $class): void {
        $prefix = 'CoinsPay\\Dle\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $name = substr($class, strlen($prefix));
        if (preg_match('/^[A-Za-z]+$/D', $name) && is_file(COINSPAY_DLE_LIB . '/' . $name . '.php')) {
            require COINSPAY_DLE_LIB . '/' . $name . '.php';
        }
    });
}
