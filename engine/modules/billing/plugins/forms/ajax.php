<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

const BILLING_MODULE = TRUE;
const MODULE_PATH = ENGINE_DIR . "/modules/billing";
const MODULE_DATA = ENGINE_DIR . "/data/billing";

try
{
    $_Config = Billing\DevTools::getConfig('forms');
    $_Lang = Billing\DevTools::getLang('forms');
    $_ConfigBilling = Billing\DevTools::getConfig('');

    if( ! $_Config['status'] )
    {
        billing_error( $_Lang['off'] );
    }

    # Admin actions
    #
    if( $show_form_id = intval( $_POST['show_form_id'] )
        and $member_id['user_group'] == 1 )
    {
        $db->query( "UPDATE " . USERPREFIX . "_billing_forms SET form_show='1' WHERE form_create_id='{$show_form_id}'" );

        billing_ok(
            [
                'status' => 'check'
            ]
        );
    }

    $_Save = [];

    # check data
    #
    $Data = json_decode($_POST['params']['data'], 1);

    foreach ($Data as $key => $value)
    {
        $Data[$key] = $db->safesql(strip_tags(htmlspecialchars($value)));
    }

    if( isset($Data['URL']) )
    {
        $_Save['URL'] = $Data['URL'];
    }

    $Data['hidden_params[theme]'] = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $Data['hidden_params[theme]'] ) );

    # Check hash
    #
    if( empty($Data['hidden_params[dle_login_hash]']) or $Data['hidden_params[dle_login_hash]'] != $dle_login_hash )
    {
        billing_error( $_Lang['errors']['hash_params'] );
    }

    if( empty($Data['hidden_params[theme]']) or ! file_exists(ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/forms/' . $Data['hidden_params[theme]'] . '/info.ini') )
    {
        billing_error( $_Lang['errors']['ini'] );
    }

    $_Theme = parse_ini_file( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/forms/' . $Data['hidden_params[theme]'] . '/info.ini', true );

    $arHash = [];

    $arHash['form_title'] = $Data['hidden_params[form_title]'];
    $arHash['price'] = $Data['hidden_params[price]'];
    $arHash['pay_desc'] = $Data['hidden_params[pay_desc]'];
    $arHash['name'] = $Data['hidden_params[name]'];
    $arHash['theme'] = $Data['hidden_params[theme]'];
    $arHash['key'] = $Data['hidden_params[key]'];
    $arHash['dle_login_hash'] = $Data['hidden_params[dle_login_hash]'];

    if( isset($_Theme['params_not_change']) and is_array($_Theme['params_not_change']) )
    {
        foreach ($_Theme['params_not_change'] as $key => $value)
        {
            $arHash['param_' . $key] = $Data['hidden_params[param_' . $key . ']'];
            $_Save[$key] = $Data['hidden_params[param_' . $key . ']'];
        }
    }

    ksort($arHash);

    if( empty($Data['hidden_params[sign]']) or $Data['hidden_params[sign]'] !== md5(implode(':', $arHash)) )
    {
        billing_error( $_Lang['errors']['hash_params'] );
    }

    # valid datas
    #
    if( isset($_Theme['input']) and is_array($_Theme['input']) )
        foreach ($_Theme['input'] as $field_name => $valid_params )
        {
            $fieldValid = billingFormParseValid($valid_params);

            if( isset($fieldValid['int']) )
            {
                $Data[$field_name] = intval($Data[$field_name]);

                if( isset($fieldValid['min']) and $Data[$field_name] < $fieldValid['min'] )
                    $Data[$field_name] = $fieldValid['min'];

                if( isset($fieldValid['max']) and $Data[$field_name] > $fieldValid['max'] )
                    $Data[$field_name] = $fieldValid['max'];

                if( isset($fieldValid['price']) )
                {
                    $arHash['price'] *= $Data[$field_name];
                }
            }

            if( isset($fieldValid['datetime']) )
            {
                $Data[$field_name] = $Data[$field_name] ? date("d.m.Y H:i:s", strtotime($Data[$field_name])) : '-';
            }

            if( isset($fieldValid['text']) )
            {
                if( isset($fieldValid['max']) and iconv_strlen($Data[$field_name]) > $fieldValid['max'] )
                    $Data[$field_name] = mb_substr($Data[$field_name], 0, $fieldValid['max']);
            }

            if( isset($fieldValid['required']) and empty( $Data[$field_name] ) )
                billing_error( $_Lang['valid']['required'] );

            $_Save[$field_name] = $Data[$field_name];
        }

    $_Save['params'] = $arHash;

    $userUid = $member_id['name'] ?: $_SERVER['REMOTE_ADDR'];

    # save form
    #
    $db->query( "INSERT INTO " . USERPREFIX . "_billing_forms
					(form_key, form_name, form_price, form_theme, form_data, form_create, form_username )
					values ('" . $arHash['key'] . "',
					        '" . $arHash['form_title'] . "',
							'" . floatval($arHash['price']) . "',
							'" . $arHash['theme'] . "',
							'" . serialize($_Save) . "',
							'" . $_TIME . "',
							'" . $userUid . "')" );

    $create_form_id = $db->insert_id();

    $payFromBalance = $member_id['name'] ? 1 : 0;

    # pay
    #
    if( $arHash['price'] = floatval($arHash['price']) )
    {
        $LQuery 	= new Billing\Database( $db, $_ConfigBilling['fname'], $_TIME );

        $invoice_id = $LQuery->DbCreatInvoice(
            '',
            $userUid,
            $arHash['price'],
            $arHash['price'],
            [
                'billing' => [
                    'from_balance' => $payFromBalance
                ],
                'params' => [
                    'form_id' => $create_form_id
                ]
            ],
            'forms:pay'
        );

        billing_ok(
            [
                'status' => 'pay',
                'title' => $arHash['form_title'],
                'url' => "/{$_ConfigBilling['page']}.html/pay/waiting/id/{$invoice_id}"
            ]
        );
    }

    billing_ok(
        [
            'title' => $arHash['form_title'],
            'text' => $Data['response[text]']
        ]
    );
}
catch (Exception $e)
{
    billing_error( $e->getMessage() );
}

function billingFormParseValid(string $valid)
{
    $_return = [];

    $ex = explode(',', $valid);

    foreach( $ex as $param )
    {
        if (strripos($param, ':'))
        {
            $ex_param = explode(':', $param);

            $_return[$ex_param[0]] = $ex_param[1];
        }
        else
            $_return[$param] = 1;
    }

    return $_return;
}