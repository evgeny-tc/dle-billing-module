<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module/
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2025
 */

namespace Billing\Services\Admin;

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
     * @throws \Exception
     */
    public function transactionInfoPage(array $get) : string
    {
        $this->Dashboard->CheckHash();

        $service = new \Billing\Services\Admin\Transactions();
        $service->Dashboard = $this->Dashboard;

        return $this->Dashboard->ajaxResponse(
            [
                'data' => $service->sliderInfoAjax( (int)$_POST['params']['id'] )
            ]
        );
    }

    /**
     * Информация о транзакции
     * @param array $get
     * @return string
     * @throws \Exception
     */
    public function invoiceInfoPage(array $get) : string
    {
        $this->Dashboard->CheckHash();

        $service = new \Billing\Services\Admin\Invoice();
        $service->Dashboard = $this->Dashboard;

        return $this->Dashboard->ajaxResponse(
            [
                'data' => $service->sliderInfoAjax( (int)$_POST['params']['id'] )
            ]
        );
    }

    /**
     * Поиск пользователей
     * @return string
     */
    public function searchUserPage() : string
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
            $this->Dashboard->LQuery->db->query( "select name FROM  " . USERPREFIX . "_users ORDER BY {$this->Dashboard->LQuery->balanceField} DESC LIMIT 10" );
        }

        $result = [];

        while ( $row = $this->Dashboard->LQuery->db->get_row() )
        {
            $result[] = $row['name'];
        }

        return $this->Dashboard->ajaxResponse($result);
    }
}