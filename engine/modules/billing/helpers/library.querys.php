<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

Class LibraryQuerys
{
	var $where = '';

	var $db = null;
	var $BalanceField = null;
	var $_TIME = null;

	function __construct( $db, $field, $time )
	{
		$this->db = $db;
		$this->BalanceField = $field;
		$this->_TIME = $time;
	}

	# Список пользователей
	#
	function DbSearchUsers( $limit = 100 )
	{
		$limit = intval( $limit );

		$answer = array();

		$this->db->query( "SELECT * FROM " . USERPREFIX . "_users " . $this->where . " order by " . $this->BalanceField . " desc limit " . $limit );

		while ( $row = $this->db->get_row() ) $answer[] = $row;

		return $answer;
	}

	# Поиск пользователя по логину или email
	#
	function DbSearchUserByName( $search_str )
	{
		return $this->db->super_query( "SELECT * FROM " . USERPREFIX . "_users
											WHERE name = '" . $this->db->safesql( $search_str ) . "' or
											      email = '" . $this->db->safesql( $search_str ) . "'" );
	}

	# Поиск запроса вывода средств по ID
	#
	function DbGetRefundById( $refund_id )
	{
		return $this->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_refund
											WHERE refund_id='" . intval( $refund_id ) . "'" );
	}

	# Изменить статус запроса вывода средств по ID
	#
	function DbRefundStatus( $refund_id, $new_status = 0 )
	{
		$new_status = $new_status ? intval( $new_status ) : 0;

		$this->db->super_query( "UPDATE " . USERPREFIX . "_billing_refund
									SET refund_date_return='" . $new_status . "'
									WHERE refund_id='" . intval( $refund_id ) . "'" );

		return;
	}

	# Удалить запроса вывода средств по ID
	#
	function DbRefundRemore( $refund_id )
	{
		$this->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_refund
									WHERE refund_id='" . intval( $refund_id ) . "'" );

		return;
	}

	# Всего запросов вывода средств
	#
	function DbGetRefundNum()
	{
		$result_count = $this->db->super_query( "SELECT COUNT(*) as count
													FROM " . USERPREFIX . "_billing_refund " . $this->where );

        return $result_count['count'];
	}

	# Список запросов вывода средств
	#
	function DbGetRefund( $intFrom = 1, $intPer = 30 )
	{
		$this->parsPage( $intFrom, $intPer );

		$answer = array();

		$this->db->query( "SELECT * FROM " . USERPREFIX . "_billing_refund " . $this->where . "
								ORDER BY refund_id desc LIMIT {$intFrom},{$intPer}" );

		while ( $row = $this->db->get_row() ) $answer[] = $row;

		return $answer;
	}

	# Всего квитанций
	#
	function DbGetInvoiceNum()
	{
		$result_count = $this->db->super_query( "SELECT COUNT(*) as count
													FROM " . USERPREFIX . "_billing_invoice " . $this->where );

        return $result_count['count'];
	}

	# Сумма у.е. из неоплаченных квитанций
	#
	function DbNewInvoiceSumm()
	{
		$sqlInvoice = $this->db->super_query( "SELECT SUM(invoice_get) as `sum`
													FROM " . USERPREFIX . "_billing_invoice
													WHERE invoice_get > '0' and invoice_date_pay > " . mktime(0,0,0) );

		return $sqlInvoice['sum'] ? $sqlInvoice['sum'] : 0;
	}

	# Список квитанций
	#
	function DbGetInvoice( $intFrom = 1, $intPer = 30 )
	{
		$this->parsPage( $intFrom, $intPer );

		$answer = array();

		$this->db->query( "SELECT * FROM " . USERPREFIX . "_billing_invoice " . $this->where . "
								ORDER BY invoice_id desc LIMIT {$intFrom},{$intPer}" );

		while ( $row = $this->db->get_row() ) $answer[] = $row;

		return $answer;
	}

	# Всего транзакций
	#
	function DbGetHistoryNum()
	{
		$result_count = $this->db->super_query( "SELECT COUNT(*) as count
													FROM " . USERPREFIX . "_billing_history " . $this->where );

        return $result_count['count'];
	}

	# Список транзакций
	#
	function DbGetHistory( $intFrom = 1, $intPer = 30 )
	{
		$this->parsPage( $intFrom, $intPer );

		$answer = array();

		$this->db->query( "SELECT * FROM " . USERPREFIX . "_billing_history " . $this->where . "
							ORDER BY history_id desc LIMIT {$intFrom},{$intPer}" );

		while ( $row = $this->db->get_row() ) $answer[] = $row;

		return $answer;
	}

	# Удалить транзакцию по ID
	#
	function DbHistoryRemoveByID( $history_id )
	{
		$this->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_history
									WHERE history_id = '" . intval( $history_id ) . "'" );

		return;
	}

	# Создать квитанцию
	#
	function DbCreatInvoice( $strPaySys, $strUser, $floatGet, $floatPay )
	{
		$this->parsVar( $strUser );
		$this->parsVar( $strPaySys, "/[^a-zA-Z0-9\s]/" );
		$this->parsVar( $floatGet, "/[^.0-9\s]/" );
		$this->parsVar( $floatPay, "/[^.0-9\s]/" );

		$this->db->query( "INSERT INTO " . USERPREFIX . "_billing_invoice
							(invoice_paysys, invoice_user_name, invoice_get, invoice_pay, invoice_date_creat) values
							('" . $strPaySys . "',
							 '" . $strUser . "',
							 '" . $floatGet . "',
							 '" . $floatPay . "',
							 '" . $this->_TIME . "')" );

		return $this->db->insert_id();
	}

	# Получить квитанцию по ID
	#
	function DbGetInvoiceByID( $id )
	{
		$id = intval( $id );

		if( ! $id ) return false;

		return $this->db->super_query( "SELECT * FROM " . USERPREFIX . "_billing_invoice
											WHERE invoice_id='" . $id . "'" );
	}

	# Обновить статус квитанции по ID
	#
	function DbInvoiceUpdate( $invoice_id, $wait = false, $invoice_paysys = '', $invoice_pay = '', $check_payer_requisites = '' )
	{
		$time = ! $wait ? $this->_TIME : 0;

		$this->db->super_query( "UPDATE " . USERPREFIX . "_billing_invoice
									SET invoice_date_pay = '" . $time . "',
										invoice_paysys = '" . $invoice_paysys . "',
										invoice_pay = '" . $invoice_pay . "',
										invoice_payer_requisites = '" . $check_payer_requisites . "'
									WHERE invoice_id = '" . intval( $invoice_id ) . "'" );

		return;
	}

	# Удалить квитанию по ID
	#
	function DbInvoiceRemove( $invoice_id )
	{
		$this->db->super_query( "DELETE FROM " . USERPREFIX . "_billing_invoice
									WHERE invoice_id='" . intval( $invoice_id ) . "'" );

		return;
	}

	# Создать запрос вывода средств
	#
	function DbCreatRefund( $strUser, $floatSum, $floatComm, $strReq )
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

	# Задать условия запроса
	#
	function DbWhere( $where_array )
	{
		$this->where = '';

		foreach( $where_array as $key => $value )
		{
			$this->parsVar( $value );

			if( empty( $value ) ) continue;

			$this->where .= ! $this->where ? "where " . str_replace("{s}", $value, $key) : " and " . str_replace("{s}", $value, $key);
		}

		return;
	}

	# Примеры фильтров:
	#		/[^-_рРА-Яа-яa-zA-Z0-9\s]/
	#		/[^a-zA-Z0-9\s]/
	#		/[^.0-9\s]/
	#
	function parsVar( &$str, $filter = '' )
	{
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

	function parsPage( &$intFrom, &$intPer )
	{
		$intFrom = intval( $intFrom );
		$intPer = intval( $intPer );

		if( $intFrom < 1 ) $intFrom = 1;
		if( $intPer < 1 ) $intPer = 30;

		$intFrom = ( $intFrom * $intPer ) - $intPer;

		return;
	}
}
?>
