<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

Class Database
{
	/**
	 * Builder WHERE..
	 * @var string
	 */
	public string $where = '';

	/**
	 * Connect db
	 * @var
	 */
	public $db;

	/**
	 * String balance field in db
	 * @var
	 */
	public $BalanceField;

	/**
	 * Local time
	 * @var
	 */
	public $_TIME;

	function __construct( $db, $field, $time )
	{
		$this->db = $db;
		$this->BalanceField = $field;
		$this->_TIME = $time;
	}

    /**
     * Check coupon
     * @param string $coupon
     * @return void
     */
    public function getCoupon(string $coupon)
    {
        return $this->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_coupons
											WHERE coupon_key = '" . $this->db->safesql( $coupon ) . "'
											        and coupon_use = ''
											        and ( coupon_time_end = 0 or coupon_time_end > " . time() . " ) " );
    }

    /**
     * Use coupon
     * @param string $coupon
     * @return mixed
     */
    public function useCoupon(array $coupon, array $invoice = [])
    {
        if( isset( $invoice['invoice_id'] ) )
        {
            $set_data = [];

            if( $invoice['invoice_payer_info'] )
            {
                $set_data = unserialize($invoice['invoice_payer_info']);
            }

            $set_data['coupon'] = $coupon;


            $this->db->query( "UPDATE " . USERPREFIX . "_billing_invoice
									SET invoice_payer_info = '" . serialize( $set_data ) . "'
									WHERE invoice_id = '" . intval( $invoice['invoice_id'] ) . "'" );
        }

        return $this->db->query( "UPDATE " . USERPREFIX . "_billing_coupons
									SET coupon_use = '" . $invoice['invoice_user_name'] . "'
									WHERE coupon_id = '" . intval( $coupon['coupon_id'] ) . "'" );
    }

    /**
	 * Users list
	 * @param $limit
	 * @return array
	 */
	public function DbSearchUsers( $limit = 100 )
	{
		$limit = intval( $limit );

		$answer = array();

		$this->db->query( "SELECT * FROM " . USERPREFIX . "_users " . $this->where . " order by " . $this->BalanceField . " desc limit " . $limit );

		while ( $row = $this->db->get_row() ) $answer[] = $row;

		return $answer;
	}

	/**
	 * Search users
	 * @param $search_str
	 * @return mixed
	 */
	public function DbSearchUserByName( string $search_str )
	{
		return $this->db->super_query( "SELECT * FROM " . USERPREFIX . "_users
											WHERE name = '" . $this->db->safesql( $search_str ) . "' or
											      email = '" . $this->db->safesql( $search_str ) . "'" );
	}

	/**
	 * Get refund row by id
	 * @param $refund_id
	 * @return mixed
	 */
	public function DbGetRefundById( $refund_id )
	{
		return $this->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_refund
											WHERE refund_id='" . intval( $refund_id ) . "'" );
	}

	/**
	 * Update refund status
	 * @param $refund_id
	 * @param $new_status
	 * @return void
	 */
	public function DbRefundStatus( $refund_id, $new_status = 0 )
	{
		$new_status = $new_status ? intval( $new_status ) : 0;

		$this->db->query( "UPDATE " . USERPREFIX . "_billing_refund
									SET refund_date_return='" . $new_status . "'
									WHERE refund_id='" . intval( $refund_id ) . "'" );

		return;
	}

	/**
	 * Delete refund row
	 * @param $refund_id
	 * @return void
	 */
	public function DbRefundRemore( $refund_id )
	{
		$this->db->query( "DELETE FROM " . USERPREFIX . "_billing_refund
									WHERE refund_id='" . intval( $refund_id ) . "'" );

		return;
	}

    /**
     * Delete refund row
     * @param $refund_id
     * @return void
     */
    public function DbRefundCancel( $refund_id )
    {
        $this->db->query( "UPDATE " . USERPREFIX . "_billing_refund
									SET refund_date_return='0', refund_date_cancel='" . $this->_TIME . "'
									WHERE refund_id='" . intval( $refund_id ) . "'" );

        return;
    }

	/**
	 * Get count refund rows
	 * @return mixed
	 */
	public function DbGetRefundNum()
	{
		$result_count = $this->db->super_query( "SELECT COUNT(*) as count
													FROM " . USERPREFIX . "_billing_refund " . $this->where );

        return $result_count['count'];
	}

	/**
	 * Get refund rows
	 * @param $intFrom
	 * @param $intPer
	 * @return array
	 */
	public function DbGetRefund( $intFrom = 1, $intPer = 30 )
	{
		$this->parsPage( $intFrom, $intPer );

		$answer = array();

		$this->db->query( "SELECT * FROM " . USERPREFIX . "_billing_refund " . $this->where . "
								ORDER BY refund_id desc LIMIT {$intFrom},{$intPer}" );

		while ( $row = $this->db->get_row() ) $answer[] = $row;

		return $answer;
	}

    /**
     * Удалить квитанции по параметрам
     * @return void
     */
    public function DbInvoicesRemove()
    {
        $this->db->query( "DELETE FROM " . USERPREFIX . "_billing_invoice " . $this->where );

        return;
    }

	/**
	 * Get count invoices
	 * @return mixed
	 */
	public function DbGetInvoiceNum()
	{
		$result_count = $this->db->super_query( "SELECT COUNT(*) as count
													FROM " . USERPREFIX . "_billing_invoice " . $this->where );

        return $result_count['count'];
	}

	/**
	 * Get sum invoice null
	 * @return int
	 */
	public function DbNewInvoiceSumm()
	{
		$sqlInvoice = $this->db->super_query( "SELECT SUM(invoice_get) as `sum`
													FROM " . USERPREFIX . "_billing_invoice
													WHERE invoice_get > '0' and invoice_date_pay > " . mktime(0,0,0) );

		return $sqlInvoice['sum'] ? $sqlInvoice['sum'] : 0;
	}

	/**
	 * Get invoice rows
	 * @param $intFrom
	 * @param $intPer
	 * @return array
	 */
	public function DbGetInvoice( $intFrom = 1, $intPer = 30 )
	{
		$this->parsPage( $intFrom, $intPer );

		$answer = array();

		$this->db->query( "SELECT * FROM " . USERPREFIX . "_billing_invoice " . $this->where . "
								ORDER BY invoice_id desc LIMIT {$intFrom},{$intPer}" );

		while ( $row = $this->db->get_row() ) $answer[] = $row;

		return $answer;
	}

	/**
	 * Get count transactions
	 * @return mixed
	 */
	public function DbGetHistoryNum()
	{
		$result_count = $this->db->super_query( "SELECT COUNT(*) as count
													FROM " . USERPREFIX . "_billing_history " . $this->where );

        return $result_count['count'];
	}

	/**
	 * Get transaction rows
	 * @param $intFrom
	 * @param $intPer
	 * @return array
	 */
	public function DbGetHistory( $intFrom = 1, $intPer = 30 )
	{
		$this->parsPage( $intFrom, $intPer );

		$answer = array();

		$this->db->query( "SELECT * FROM " . USERPREFIX . "_billing_history " . $this->where . "
							ORDER BY history_id desc LIMIT {$intFrom},{$intPer}" );

		while ( $row = $this->db->get_row() ) $answer[] = $row;

		return $answer;
	}

	/**
	 * Delete transaction by row
	 * @param $history_id
	 * @return void
	 */
	public function DbHistoryRemoveByID(  int $history_id )
	{
		$this->db->query( "DELETE FROM " . USERPREFIX . "_billing_history WHERE history_id = '" . intval( $history_id ) . "'" );
	}

    /**
     * Create invoice
     * @param string $payment_name
     * @param string $username
     * @param float $sum_get
     * @param float $sum_pay
     * @param string|null $payer_info
     * @param string $handler
     * @return mixed
     */
	public function DbCreatInvoice( string $payment_name, string $username, float $sum_get, float $sum_pay = 0, mixed $payer_info = '', string $handler = '' ): int
    {
		$this->parsVar( $username );
		$this->parsVar( $handler, "/[^.a-z:\s]/" );

		if( is_array( $payer_info ) )
		{
			foreach( $payer_info as $key => $info )
			{
				if( is_array($info) )
				{
					foreach($info as $info_key => $info_val)
					{
						$payer_info[$key][$info_key] = preg_replace('/[^ a-z&#;@а-яA-ZА-Я\d.]/ui', '', $info_val );
					}
				}
				else
				{
					$payer_info[$key] = preg_replace('/[^ a-z&#;@а-яA-ZА-Я\d.]/ui', '', $info);
				}
			}

			$payer_info = serialize( $payer_info );
		}
		else
			$payer_info = $this->db->safesql( $payer_info );

		$this->parsVar( $payment_name, "/[^a-zA-Z0-9\s]/" );
		$this->parsVar( $sum_get, "/[^.0-9\s]/" );
		$this->parsVar( $sum_pay, "/[^.0-9\s]/" );

		$this->db->query( "INSERT INTO " . USERPREFIX . "_billing_invoice
							(invoice_paysys, invoice_user_name, invoice_get, invoice_pay, invoice_date_creat, invoice_payer_info, invoice_handler) values
							('{$payment_name}',  '{$username }', '{$sum_get}', '{$sum_pay}', '{$this->_TIME}', '{$payer_info}', '{$handler}')" );

		return $this->db->insert_id();
	}

	/**
	 * Get invoice row
	 * @param $id
	 * @return false
	 */
	public function DbGetInvoiceByID( $id )
	{
		$id = intval( $id );

		if( ! $id ) return false;

		return $this->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_invoice WHERE invoice_id='" . $id . "'" );
	}

    /**
     * Update invoice by id
     * @param int $invoice_id
     * @param bool $wait
     * @param string $invoice_paysys
     * @param float $invoice_pay
     * @param string $check_payer_requisites
     * @return void
     */
	public function DbInvoiceUpdate( int $invoice_id, bool $wait = false, string $invoice_paysys = '', float $invoice_pay, string $check_payer_requisites = '' ) : void
	{
		$time = ! $wait ? $this->_TIME : 0;

		$this->db->query( "UPDATE " . USERPREFIX . "_billing_invoice
									SET invoice_date_pay = '" . $time . "',
										invoice_paysys = '" . $invoice_paysys . "',
										invoice_pay = '" . $invoice_pay . "',
										invoice_payer_requisites = '" . $check_payer_requisites . "'
									WHERE invoice_id = '" . intval( $invoice_id ) . "'" );
	}

	/**
	 * Delete invoice by id
	 * @param $invoice_id
	 * @return void
	 */
	public function DbInvoiceRemove( $invoice_id ) : void
	{
		$this->db->query( "DELETE FROM " . USERPREFIX . "_billing_invoice WHERE invoice_id='" . intval( $invoice_id ) . "'" );
	}

	/**
	 * Create refund row
	 * @param $strUser
	 * @param $floatSum
	 * @param $floatComm
	 * @param $strReq
	 * @return mixed
	 */
	public function DbCreatRefund( $strUser, $floatSum, $floatComm, $strReq ) : int
	{
		$this->parsVar( $strUser );
		$this->parsVar( $strReq );
		$this->parsVar( $floatSum, "/[^.0-9\s]/" );
		$this->parsVar( $floatComm, "/[^.0-9\s]/" );

		$this->db->query( "INSERT INTO " . USERPREFIX . "_billing_refund
							(refund_date, refund_user, refund_summa, refund_commission, refund_requisites) values
							('" . $this->_TIME . "',
							 '" . $strUser . "',
							 '" . $floatSum . "',
							 '" . $floatComm . "',
							 '" . $strReq . "')" );

		return $this->db->insert_id();
	}

	/**
	 * Set params filter
	 * @param $where_array
	 * @return void
	 */
	public function DbWhere( array $where_array = [] ) : void
	{
		$this->where = '';

		foreach( $where_array as $key => $value )
		{
			$this->parsVar( $value );

			if( empty( $value ) ) continue;

			$this->where .= ! $this->where ? "where " . str_replace("{s}", $value, $key) : " and " . str_replace("{s}", $value, $key);
		}
	}

	# Примеры фильтров:
	#		/[^-_рРА-Яа-яa-zA-Z0-9\s]/
	#		/[^a-zA-Z0-9\s]/
	#		/[^.0-9\s]/
	#
	/**
	 * Filter
	 * @param $str
	 * @param $filter
	 * @return void
	 */
	public function parsVar( &$str, string $filter = '' )
	{
		if( is_array( $str ) )
		{
			foreach( $str as $item )
			{
				$this->parsVar( $item, $filter );
			}

			return;
		}

		$str = trim( $str );

		if( function_exists( "get_magic_quotes_gpc" ) && get_magic_quotes_gpc() ) $str = stripslashes( $str );

		$str = htmlspecialchars( trim( $str ), ENT_COMPAT );

		if( $filter )
		{
			$str = preg_replace( $filter, "", $str);
		}

		$str = preg_replace('#\s+#i', ' ', $str);
		$str = $this->db->safesql( $str );

		return $str;
	}

    /**
     * Preprocess paging
     * @param $intFrom
     * @param $intPer
     * @return void
     */
	public function parsPage( &$intFrom, &$intPer ) : void
	{
		$intFrom = intval( $intFrom );
		$intPer = intval( $intPer );

		if( $intFrom < 1 ) $intFrom = 1;
		if( $intPer < 1 ) $intPer = 30;

		$intFrom = ( $intFrom * $intPer ) - $intPer;
	}
}