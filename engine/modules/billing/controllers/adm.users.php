<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

Class ADMIN
{
	function main()
	{
		global $user_group;

		# Внести изменения в баланс пользователей
		#
		if( isset( $_POST['edit_user_btn'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$_Login = explode(",", $_POST['edit_name'] );
			$_Do = intval( $_POST['edit_do'] );
			$_Sum = $_POST['edit_summa'];
			$_Comment = $this->Dashboard->LQuery->db->safesql( $_POST['edit_comm'] );

			$_Errors = '';

			if( ! count( $_Login ) )
			{
				$_Errors = $this->Dashboard->lang['users_er_user'];
			}
			if( ! $_Sum )
			{
				$_Errors = $this->Dashboard->lang['users_er_summa'];
			}

			$_Sum = $this->Dashboard->API->Convert( $_Sum );

			if( $_Errors )
			{
				$this->Dashboard->ThemeMsg( $this->Dashboard->lang['error'], $_Errors );
			}
			else
			{
				foreach( $_Login as $login )
				{
					if( trim($login) )
					{
						if( $_Do )
						{
							$this->Dashboard->API->PlusMoney(
								trim($login),
								$_Sum,
								$_Comment,
								'users',
								$this->Dashboard->member_id['user_id']
							);
						}
						else
						{
							$this->Dashboard->API->MinusMoney(
								trim($login),
								$_Sum,
								$_Comment,
								'users',
								$this->Dashboard->member_id['user_id']
							);
						}
					}
				}

				$this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['users_ok'], $PHP_SELF . "?mod=billing&c=users" );
			}
		}

		# Внести изменения в баланс группы
		#
		if( isset( $_POST['edit_group_btn'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$_Group = intval( $_POST['edit_group'] );
			$_Do = intval( $_POST['edit_do_group'] );
			$_Sum = $_POST['edit_summa_group'];

			$_Errors = '';

			if( ! $_Group )
			{
				$_Errors = $this->Dashboard->lang['users_er_group'];
			}
			if( ! $_Sum )
			{
				$_Errors = $this->Dashboard->lang['users_er_summa'];
			}

			$_Sum = $this->Dashboard->API->Convert( $_Sum );

			if( $_Errors )
			{
				$this->Dashboard->ThemeMsg( $this->Dashboard->lang['error'], $_Errors );
			}
			else
			{
				if( $_Do )
		        {
		            $this->Dashboard->LQuery->db->query( "UPDATE " . USERPREFIX . "_users
		                                                    SET {$this->Dashboard->config['fname']} = {$this->Dashboard->config['fname']} + $_Sum
		                                                    WHERE user_group = '$_Group'");
		        }
		        else
		        {
		            $this->Dashboard->LQuery->db->query( "UPDATE " . USERPREFIX . "_users
		                                                    SET {$this->Dashboard->config['fname']} = {$this->Dashboard->config['fname']} - $_Sum
		                                                    WHERE user_group = '$_Group'");
		        }

		        $this->Dashboard->ThemeMsg( $this->Dashboard->lang['ok'], $this->Dashboard->lang['users_ok_group'], $PHP_SELF . "?mod=billing&c=users" );
			}
		}

		$this->Dashboard->ThemeEchoHeader( $this->Dashboard->lang['menu_3'] );

		# Поиск в базе
		#
		if( isset( $_POST['search_btn'] ) )
		{
			if( $_POST['user_hash'] == "" or $_POST['user_hash'] != $this->Dashboard->hash )
			{
				return "Hacking attempt! User not found {$_POST['user_hash']}";
			}

			$_WhereData = array();

			switch( substr( $_POST['search_balance'], 0, 1) )
			{
				case '>':
					$_WhereData["{$this->Dashboard->config['fname']} > '{s}'"] = substr($_POST['search_balance'], 1, strlen($_POST['search_balance']));
				break;
				case '<':
					$_WhereData["{$this->Dashboard->config['fname']} < '{s}'"] = substr($_POST['search_balance'], 1, strlen($_POST['search_balance']));
				break;
				case '=':
					$_WhereData["{$this->Dashboard->config['fname']} = '{s}'"] = substr($_POST['search_balance'], 1, strlen($_POST['search_balance']));
				break;
				default:
					$_WhereData["{$this->Dashboard->config['fname']} = '{s}'"] = $_POST['search_balance'];
			}

			$_WhereData["name LIKE '%{s}%' or email LIKE '%{s}%'"] = $_POST['search_name'];

			$this->Dashboard->LQuery->DbWhere( $_WhereData );

			$Data = $this->Dashboard->LQuery->DbSearchUsers();
		}
		else
		{
			$this->Dashboard->LQuery->DbWhere( array( "{$this->Dashboard->config['fname']} > 0 " => 1 ) );

			$Data = $this->Dashboard->LQuery->DbSearchUsers( 10 );
		}

		# Список пользователей
		#
		$this->Dashboard->ThemeAddTR( array(
			'<th>'.$this->Dashboard->lang['users_tanle_login'].'</th>',
			'<th>'.$this->Dashboard->lang['users_tanle_email'].'</th>',
			'<th>'.$this->Dashboard->lang['users_tanle_group'].'</th>',
			'<th>'.$this->Dashboard->lang['users_tanle_datereg'].'</th>',
			'<th>'.$this->Dashboard->lang['users_tanle_balance'].'</th>'
		) );

		foreach( $Data as $Value )
		{
			$this->Dashboard->ThemeAddTR( array(
				"<span onClick=\"usersAdd( '" . $Value['name'] . "' )\" id=\"user_".$Value['name']."\" style=\"cursor: pointer\"><i class=\"fa fa-plus\" style=\"margin-left: 10px; vertical-align: middle\"></i></span>" .
				$this->Dashboard->ThemeInfoUser( $Value['name'] ),
				$Value['email'],
				$user_group[$Value['user_group']]['group_name'],
				$this->Dashboard->ThemeChangeTime( $Value['reg_date']),
				$this->Dashboard->API->Convert( $Value[$this->Dashboard->config['fname']] ) ." ". $this->Dashboard->API->Declension( $Value[$this->Dashboard->config['fname']] )
			));
		}

		$ContentTab = $this->Dashboard->ThemeParserTable();

		if( ! count($Data) )
		{
			$ContentTab .=  $this->Dashboard->ThemePadded( $this->Dashboard->lang['history_no'], '' );
		}

		$tabs[] = array(
				'id' => 'users',
				'title' => $this->Dashboard->lang['users_title'],
				'content' => $ContentTab
		);

		# Форма поиска
		#
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['users_label'],
			$this->Dashboard->lang['users_label_desc'],
			"<input name=\"search_name\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $_POST['search_name'] ."\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['user_se_balance'],
			$this->Dashboard->lang['user_se_balance_desc'],
			"<input name=\"search_balance\" class=\"form-control\" type=\"text\" style=\"width: 100%\" value=\"" . $_POST['search_balance'] ."\">"
		);

		$ContentSearch = $this->Dashboard->ThemeParserStr();
		$ContentSearch .= $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("search_btn", $this->Dashboard->lang['history_search_btn'], "green") );

		$tabs[] = array(
				'id' => 'search',
				'title' => $this->Dashboard->lang['history_search'],
				'content' => $ContentSearch
		);

		# Статистика по группам
		#
		$this->Dashboard->ThemeAddTR( $this->Dashboard->lang['users_group_stats'] );

		foreach ($user_group as $group_id => $group_value)
		{
			if( $group_id == 5 ) continue;

			$users = $this->Dashboard->LQuery->db->super_query( "SELECT count(*) as `count`,
												 min(" . $this->Dashboard->config['fname'] . ") as `min`,
												 max(" . $this->Dashboard->config['fname'] . ") as `max`,
												 sum(" . $this->Dashboard->config['fname'] . ") as `sum`
											FROM " . USERPREFIX . "_users WHERE user_group='$group_id'" );

			$this->Dashboard->ThemeAddTR( array(
					$group_value['group_name'],
					$users['count'],
					$this->Dashboard->API->Convert( $users['min'] ) . '&nbsp;' . $this->Dashboard->API->Declension( $users['min'] ),
					$this->Dashboard->API->Convert( $users['max'] ) . '&nbsp;' . $this->Dashboard->API->Declension( $users['max'] ),
					$this->Dashboard->API->Convert( $users['sum'] ) . '&nbsp;' . $this->Dashboard->API->Declension( $users['sum'] )
			) );
		}

		$tabs[] = array(
				'id' => 'groups',
				'title' => $this->Dashboard->lang['users_groups_title'],
				'content' => $this->Dashboard->ThemeParserTable()
		);

		if( isset( $_POST['search_btn'] ) )
		{
			$Content .= $this->Dashboard->MakeMsgInfo(
				$this->Dashboard->lang['search_info'],
				"icon-search",
				"blue"
			);
		}

		$Content .= $this->Dashboard->PanelTabs( $tabs );

		# Изменение баланса
		#
		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['users_login'],
			$this->Dashboard->lang['users_login_desc'],
			"<input name=\"edit_name\" id=\"edit_name\" class=\"form-control\" style=\"width: 100%\" value=\"". $_GET['login'] ."\" type=\"text\">"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['users_edit_do'],
			$this->Dashboard->lang['users_edit_do_desc'],
			"<select name=\"edit_do\" class=\"uniform\"><option value=\"1\">" . $this->Dashboard->lang['users_plus']."</option><option value=\"0\">".$this->Dashboard->lang['users_minus']."</option></select>"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['users_summa'],
			$this->Dashboard->lang['users_summa_desc'],
			"<input name=\"edit_summa\" class=\"form-control\" type=\"text\" style=\"width: 20%\"> " . $this->Dashboard->API->Declension( 10 )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['users_comm'],
			$this->Dashboard->lang['users_comm_desc'],
			"<input name=\"edit_comm\" class=\"form-control\" type=\"text\" style=\"width: 100%\">"
		);

		$tabs = array();

		$tabs[] = array(
				'id' => 'user',
				'title' => $this->Dashboard->lang['users_edit_user'],
				'content' => $this->Dashboard->ThemeParserStr() . $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("edit_user_btn", $this->Dashboard->lang['act'], "green") )
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['users_group'],
			$this->Dashboard->lang['users_group_desc'],
			 "<select name=\"edit_group\" class=\"uniform\"><option value=\"\"> </option>" . $this->Dashboard->GetGroups(false, 5) . "</select>"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['users_edit_do'],
			$this->Dashboard->lang['users_edit_do_desc'],
			"<select name=\"edit_do_group\" class=\"uniform\"><option value=\"1\">" . $this->Dashboard->lang['users_plus']."</option><option value=\"0\">".$this->Dashboard->lang['users_minus']."</option></select>"
		);

		$this->Dashboard->ThemeAddStr(
			$this->Dashboard->lang['users_summa'],
			$this->Dashboard->lang['users_summa_desc'],
			"<input name=\"edit_summa_group\" class=\"form-control\" style=\"width: 20%\" type=\"text\"> " . $this->Dashboard->API->Declension( 10 )
		);

		$tabs[] = array(
				'id' => 'group',
				'title' => $this->Dashboard->lang['users_edit_group'],
				'content' => $this->Dashboard->ThemeParserStr() . $this->Dashboard->ThemePadded( $this->Dashboard->MakeButton("edit_group_btn", $this->Dashboard->lang['act'], "green") )
		);

		$Content .= $this->Dashboard->PanelTabs( $tabs );
		$Content .= $this->Dashboard->ThemeEchoFoother();

		return $Content;
	}
}
?>
