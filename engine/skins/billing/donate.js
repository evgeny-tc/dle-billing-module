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



let donateStringIncludePanel = '';
let donateStringIncludeList = '';
let donateStringIncludeSum = '';

function donateShow()
{
    donateCreate();

    var message = donate_lang_text + '<p><pre><code>' + donateStringIncludePanel + '</code></pre></p>'
                    + donate_lang_text_3 + '<p><pre><code>' + donateStringIncludeList + '</code></pre></p>'
                    + donate_lang_text_4 + '<p><pre><code>' + donateStringIncludeSum + '</code></pre></p>'
                    + donate_lang_close;

    $("#dlepopup").remove();

	$("body").append("<div id='dlepopup' class='dle-alert' title='" + donate_lang_created + "' style='display:none'>"+ message +"</div>");

	$('#dlepopup').dialog({
		autoOpen: true,
		width: 700,
		minHeight: 160,
		resizable: false
	});
}

function donateCreate()
{
    var arr = [];

    if( ! $("#create_login").val() )
    {
        $("#create_login").val("admin");
    }

    arr[arr.length] = 'login=' + $("#create_login").val();

    if( $("#create_all").val() && $("#create_all").val().match(/^[-\+]?\d+/) !== null )
    {
        arr[arr.length] = 'all=' + $("#create_all").val();
    }

    if( $("#create_code").val() )
    {
        arr[arr.length] = 'code=' + $("#create_code").val();
    }

    if( $("#create_theme_panel").val() )
    {
        arr[arr.length] = 'tpanel=' + $("#create_theme_panel").val();
    }

    console.log(arr);

    donateStringIncludePanel = '{include file="engine/modules/billing/plugins/donate/panel.php';

    arr.forEach(function(item, i, arr)
    {
        if( i == 0 )
        {
            donateStringIncludePanel += '?' + item.trim();
        }
        else
        {
            donateStringIncludePanel += '&' + item.trim();
        }
    });

    donateStringIncludePanel += '"}';

    donateStringShowAjax = "&lt;a href='#' onClick='BillingDonate.Form( \"" + $("#create_login").val() + "\", \"" + $("#create_code").val() + "\" )'&gt;" + donate_lang_link + "&lt;/a&gt;";

    donateStringIncludeList = '{include file="engine/modules/billing/widgets/history.php?plugin=donate' + ( $("#create_code").val() ? '&plugin_id=' + $("#create_code").val() : '' ) + '&login=' + $("#create_login").val() + '&minus_max=0"}';

    donateStringIncludeSum = '{include file="engine/modules/billing/plugins/donate/panel.sum.php?login=' + $("#create_login").val() + ( $("#create_code").val() ? '&code=' + $("#create_code").val() : '' ) + '"}';
}

if (!String.prototype.trim)
{
  (function()
  {
    String.prototype.trim = function() {
      return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
    };
  })();
}
