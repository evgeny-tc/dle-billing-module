<?php

namespace CoinsPay\Dle;

/**
 * Texts of the module in Russian or English: lang/ru.php and lang/en.php. Plain arrays, so the
 * unit tests read them without DLE.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Text
{
    private array $strings;

    private array $fallback;

    /**
     * @param string $lang A language of DLE (Russian, English, Ukrainian…) or a code (ru, en):
     *                     Russian gets Russian, anything else English.
     */
    public function __construct(string $lang)
    {
        $this->fallback = self::load('en');
        $this->strings = self::code($lang) === 'en' ? $this->fallback : self::load(self::code($lang));
    }

    /** ru or en from a language of DLE or a locale. */
    public static function code(string $lang): string
    {
        return str_starts_with(strtolower(trim($lang)), 'ru') ? 'ru' : 'en';
    }

    /** Text by key with sprintf arguments: t('log_paid', $number, $amount). */
    public function t(string $key, mixed ...$args): string
    {
        $text = $this->strings[$key] ?? $this->fallback[$key] ?? $key;

        return $args ? vsprintf($text, $args) : $text;
    }

    private static function load(string $lang): array
    {
        $file = dirname(__DIR__) . '/lang/' . preg_replace('/[^a-z]/', '', $lang) . '.php';
        if (!is_file($file)) {
            return [];
        }
        $strings = include $file;

        return is_array($strings) ? $strings : [];
    }
}
