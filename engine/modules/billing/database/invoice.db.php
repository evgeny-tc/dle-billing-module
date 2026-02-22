<?php

namespace Billing\DB;

use \Billing\BaseDB;

/**
 * БД Счета на оплату
 * @table_name dle_billing_history
 */
Class Invoice extends BaseDB
{
    const TABLE_NAME = '_billing_invoice';

    /**
     *
     * @param int $id
     * @return int[]
     */
    public static function getById( int $id ) : array
    {
        parent::init();

        if( $result = parent::$db->super_query( "SELECT *, `user_data`.*
                FROM " . USERPREFIX . self::TABLE_NAME . " `invoice`
                LEFT JOIN " . USERPREFIX . "_users `user_data`
                        ON user_data.name = invoice.invoice_user_name
                    WHERE invoice_id = {$id}" ) )
        {
            return $result;
        }

        return [];
    }
}
