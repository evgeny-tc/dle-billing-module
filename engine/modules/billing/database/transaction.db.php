<?php

namespace Billing\DB;

use \Billing\BaseDB;

/**
 * БД Транзакции
 * @table_name dle_billing_history
 */
Class Transaction extends BaseDB
{
    const TABLE_NAME = '_billing_history';

    /**
     *
     * @param int $id
     * @return int[]
     */
    public static function getById( int $id ) : array
    {
        parent::init();

        if( $result = parent::$db->super_query( "SELECT * FROM " . USERPREFIX . self::TABLE_NAME . " WHERE history_id = {$id}" ) )
        {
            return $result;
        }

        return [];
    }
}