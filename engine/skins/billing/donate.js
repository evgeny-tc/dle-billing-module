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
