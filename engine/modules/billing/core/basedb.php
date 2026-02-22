<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2025
 */

namespace Billing;

/**
 * БД
 */
Class BaseDB
{
    protected static object $db;
    protected static int $time;

    public static string $conditions = '';

    private const PATTERN_DEFAULT = '';
    private const PATTERN_ALPHANUMERIC = '/[^a-zA-Z0-9\s]/';
    private const PATTERN_ALPHANUMERIC_EXTENDED = '/[^-_a-zA-Z0-9\s]/';
    private const PATTERN_NUMERIC = '/[^.0-9\s]/';
    private const PATTERN_HANDLER = '/[^.a-z:\s]/';
    private const PATTERN_PAYMENT = '/[^a-zA-Z0-9\s]/';

    protected static function init() : void
    {
        global $db, $_TIME;

        self::$db = $db;
        self::$time = $_TIME;
    }

    /**
     * @return object
     */
    public static function getDb() : object
    {
        return self::$db;
    }

    /**
     * @param array $conditions
     * @return void
     */
    public static function where(array $conditions = []): void
    {
        self::$conditions = '';

        foreach ($conditions as $field => $value)
        {
            if ($value === '' || $value === null) {
                continue;
            }

            $value = self::sanitize($value);
            $condition = str_replace('{s}', $value, $field);

            if (empty(self::$conditions))
            {
                self::$conditions = "WHERE {$condition}";
            }
            else
            {
                self::$conditions .= " AND {$condition}";
            }
        }
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function deleteById(int $id) : bool
    {
        self::init();

        return (bool) self::$db->query(
            "DELETE FROM " . USERPREFIX . static::TABLE_NAME . " 
             WHERE invoice_id = {$id}"
        );
    }

    /**
     * Sanitize string value
     * @param $value
     * @param string $pattern
     * @return string
     */
    public static function sanitize(&$value, string $pattern = self::PATTERN_DEFAULT): string
    {
        if (is_array($value))
        {
            foreach ($value as &$item)
            {
                $item = self::sanitize($item, $pattern);
            }

            return $value;
        }

        $value = trim((string) $value);

        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        $value = preg_replace('#\s+#', ' ', $value);

        return self::$db->safesql($value);
    }

    /**
     * @param $value
     * @return string
     */
    public static function sanitizeNumeric($value): string
    {
        return self::sanitize($value, self::PATTERN_NUMERIC);
    }
}