<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

global $dle_login_hash;

$_Config = [];
$_Theme = [];

$_Lang = include ENGINE_DIR . "/modules/billing/plugins/forms/lang.php";

$visitUniqid = uniqid();

if( file_exists( ENGINE_DIR . "/data/billing/plugin.forms.php" ) )
{
    $_Config = include ENGINE_DIR . "/data/billing/plugin.forms.php";
}

if( $_Config['status'] )
{
    include ENGINE_DIR . '/modules/billing/OutAPI.php';

    try
    {
        # loaded theme
        #
        if( $_Theme['name'] = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $theme ) ) )
        {
            if( file_exists(ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/forms/' . $_Theme['name'] . '/info.ini') )
            {
                $_Theme['info'] = parse_ini_file( ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/forms/' . $_Theme['name'] . '/info.ini', true );
            }
            else
                throw new Exception($_Lang['errors']['ini']);

            if( ! $_Theme['key'] = preg_replace("/[^a-zA-Z0-9\s]/", "", trim( $key ) ) )
            {
                $_Theme['key'] = totranslit($_Theme['name']);
            }

            if( file_exists(ROOT_DIR . '/templates/' . $config['skin'] . '/billing/plugins/forms/' . $_Theme['name'] . '/theme.tpl') )
            {
                $tpl = new dle_template();
                $tpl->dir = TEMPLATE_DIR;
                $tpl->load_template( '/billing/plugins/forms/' . $_Theme['name'] . '/theme.tpl' );

                $js_code = <<<HTML
<script>
let dialog{$visitUniqid} = null;

document.addEventListener("DOMContentLoaded", function(event)
{    
    document.querySelector('.billingFormShowModal-{$visitUniqid}').addEventListener('click', function () 
    {
      let div = $(this).parents('.billing_form_content')[0];
      let dialog = $(div).children('.billingFormModal');

      dialog{$visitUniqid} = $(dialog).clone().appendTo(dialog);
      
      dialog{$visitUniqid}.dialog(
        {
             autoOpen: true,
             show: 'fade',
             hide: 'fade',
             resizable: false,
             width: dialog.data('width') ?? 500,
             height: dialog.data('height') ?? 500
        }); 
    });  
    
    $( document ).on( "click", ".billingFormSend-{$visitUniqid}", function() 
    {
        let form = $(this).parents('.billingForm')[0]; console.log(form);
        let data = new FormData(form);
     
        data.set('URL', window.location.href);
        
        BillingJsCore.ajax('forms', {
            data: JSON.stringify(Object.fromEntries(data))
        })
            .then(
                result => {
                    HideLoading('');
              
                    if( dialog{$visitUniqid} )
                    {
                        $(dialog{$visitUniqid}[0]).remove();
                    }
                    
                    if( result.status == 'pay' )
                    {
                        location.href = result.url;
                        
                        return;
                    }
                    
                    DLEalert( result.text, result.title );
                },
                error => {
                    HideLoading('');
                    DLEalert( error, "Ошибка" );
                }
            );
        
        return false;
    });
});
</script>
HTML;

                $params = [];

                $params['form_title'] = $db->safesql( urldecode($title) );
                $params['price'] = $BillingAPI->Convert( $price );
                $params['pay_desc'] = $db->safesql( urldecode($desc) );

                if( floatval($price) > 0 )
                {
                    $tpl->set( '[price]', '' );
                    $tpl->set( '[/price]', '' );

                    $tpl->set_block( "'\\[price_not\\](.*?)\\[/price_not\\]'si", '' );
                }
                else
                {
                    $tpl->set( '[price_not]', '' );
                    $tpl->set( '[/price_not]', '' );

                    $tpl->set_block( "'\\[price\\](.*?)\\[/price\\]'si", '' );
                }

                $tpl->set( '{key}', $_Theme['key'] );
                $tpl->set( '{uniqid}', $visitUniqid );
                $tpl->set( '{form_title}', $params['form_title'] );
                $tpl->set( '{price}', $params['price'] );
                $tpl->set( '{dec}', $BillingAPI->Declension( $params['price'] ) );
                $tpl->set( '{pay_desc}', $params['pay_desc'] );
                $tpl->set( '{theme}', $_Theme['name'] );
                $tpl->set( '{module.skin}', $config['skin'] );

                if( $member_id['name'] )
                {
                    $tpl->set( '{login}', $member_id['name'] );

                    $tpl->set( '[login]', '' );
                    $tpl->set( '[/login]', '' );

                    $tpl->set_block( "'\\[not_login\\](.*?)\\[/not_login\\]'si", '' );
                }
                else
                {
                    $tpl->set( '[not_login]', '' );
                    $tpl->set( '[/not_login]', '' );

                    $tpl->set_block( "'\\[login\\](.*?)\\[/login\\]'si", '' );
                }

                $tpl->set( '{user_group}', $member_id['user_group'] );

                # Hash
                #
                $arHash = [];

                $arHash['form_title'] = $params['form_title'];
                $arHash['price'] = $params['price'];
                $arHash['pay_desc'] = $params['pay_desc'];
                $arHash['name'] = $_Theme['name'];
                $arHash['theme'] = $_Theme['name'];
                $arHash['key'] = $_Theme['key'];
                $arHash['dle_login_hash'] = $dle_login_hash;

                if( isset($param) and is_array($param) )
                {
                    foreach ($param as $key => $value)
                    {
                        $tpl->set( '{param_' . $key . '}', $value );

                        if( isset($_Theme['info']['params_not_change'][$key]) )
                        {
                            $arHash['param_' . $key] = $value;
                        }
                    }
                }

                ksort($arHash);

                $hidden_inputs = [];

                foreach($arHash as $key => $value )
                {
                    $hidden_inputs[] = '<input type="hidden" name="hidden_params[' . $key . ']" value="' . $value . '">';
                }

                $hidden_inputs[] = '<input type="hidden" name="hidden_params[sign]" value="' . md5(implode(':', $arHash)) . '">';

                $tpl->set( '{hidden_input}', implode($hidden_inputs) );

                $tpl->compile( 'content' );
                $tpl->clear();

                echo $js_code . '<div class="billing_form_content">' . $tpl->result['content'] . '</div>';
            }
            else
                throw new Exception($_Lang['errors']['tpl']);
        }
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}
else
{
    echo $_Lang['off'];
}



