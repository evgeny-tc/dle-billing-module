<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2025
 */

namespace Billing\Admin\Controller;

use \Billing\Dashboard;

Class Ajax
{
    /**
     * @var Dashboard
     */
    public Dashboard $Dashboard;

    /**
     * Информация о транзакции
     * @param array $get
     * @return string
     */
    public function transactionInfo(array $get) : string
    {
        return $this->Dashboard->ajaxResponse(
            [
                'data' => \Billing\iTransaction::sliderInfo( (int)$get['id'] )
            ]
        );
    }

    /**
     * Поиск пользователей
     * @return string
     */
    public function searchUser() : string
    {
        if( preg_match( "/[\||\<|\>]/", $_GET['term'] ) )
        {
            $term = "";
        }
        else
        {
            $term = $this->Dashboard->LQuery->db->safesql(  dle_strtolower( htmlspecialchars( strip_tags( stripslashes( trim( rawurldecode($_GET['term']) ) ) ), ENT_COMPAT, $this->Dashboard->dle['charset'] ), $this->Dashboard->dle['charset'] ) );
        }

        $term = trim($term);

        if( $term )
        {
            $this->Dashboard->LQuery->db->query( "select name FROM  " . USERPREFIX . "_users WHERE name LIKE '{$term}%' LIMIT 10" );
        }
        else
        {
            $this->Dashboard->LQuery->db->query( "select name FROM  " . USERPREFIX . "_users ORDER BY {$this->Dashboard->config['fname']} DESC LIMIT 10" );
        }

        $result = [];

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            $result[] = $row['name'];
        }

        return $this->Dashboard->ajaxResponse($result);
    }
}