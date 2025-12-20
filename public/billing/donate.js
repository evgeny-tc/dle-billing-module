'use strict';

let DLE_Billing_Donate = class
{
    /**
     *
     * @type {string}
     */
    static MODAL_TITLE = '';

    /**
     *
     * @type {string}
     */
    static MODAL_BODY = '';

    /**
     *
     * @type {string}
     */
    static TAG_MAIN = '';

    /**
     *
     * @type {string}
     */
    static TAG_LIST = '';

    /**
     *
     * @type {string}
     */
    static TAG_SUM = '';

    /**
     *
     * @constructor
     */
    static Show()
    {
        DLE_Billing_Donate.Build();

        $("#donate_popup").remove();

        let body = DLE_Billing_Donate.MODAL_BODY.replace('{tag1}', DLE_Billing_Donate.TAG_MAIN)
            .replace('{tag2}', DLE_Billing_Donate.TAG_LIST)
            .replace('{tag3}', DLE_Billing_Donate.TAG_SUM);

        $("body").append(`<div id='donate_popup' class='dle-alert' title='${DLE_Billing_Donate.MODAL_TITLE}' style='display:none'>${body}</div>`);

        BillingJS.openDialog('#donate_popup', {width: 600});
    }

    /**
     *
     * @constructor
     */
    static Build()
    {
        let params = [];

        params.push(
            'login=' + encodeURI($("#create_login").val())
        );

        if( $("#create_all").val() && $("#create_all").val().match(/^[-\+]?\d+/) !== null )
        {
            params.push(
                'all=' + $("#create_all").val()
            );
        }

        if( $("#create_code").val() )
        {
            params.push(
                'code=' + $("#create_code").val()
            );
        }

        if( $("#create_theme_panel").val() )
        {
            params.push(
                'tpanel=' + $("#create_theme_panel").val()
            );
        }

        DLE_Billing_Donate.TAG_MAIN = `{include file="engine/modules/billing/plugins/donate/panel.php?${params.join('&')}"}`;
        DLE_Billing_Donate.TAG_LIST = '{include file="engine/modules/billing/widgets/history.php?plugin=donate' + ( $("#create_code").val() ? '&plugin_id=' + $("#create_code").val() : '' ) + '&login=' + $("#create_login").val() + '&minus_max=0"}';
        DLE_Billing_Donate.TAG_SUM = '{include file="engine/modules/billing/plugins/donate/panel.sum.php?login=' + $("#create_login").val() + ( $("#create_code").val() ? '&code=' + $("#create_code").val() : '' ) + '"}';
    }
}