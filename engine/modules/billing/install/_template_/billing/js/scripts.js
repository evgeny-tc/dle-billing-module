/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

function BillingJS( hash )
{
	this.hash = hash;

	this.iframe = function(title, url, params = {})
	{
		$('#billing-modal').remove();

		params['width'] = params['width'] ?? 700;
		params['height'] = params['height'] ?? 395;
	
		$("body").append(`<div id='billing-modal' title='${title}' style='padding: 0; display:none; '>
			 <iframe src="${url}" width="100%%" height="100%" style="border: none" align="left">
				Ваш браузер не поддерживает плавающие фреймы! <a href="${url}" target="_blank">Открыть ссылку в новом окне</a>
			 </iframe>
		</div>`);

		$("#billing-modal").dialog(
			{
				autoOpen: true,
				show: 'fade',
				hide: 'fade',
				resizable: false,
				width: params['width'] ,
				height: params['height']
			});
	};

	this.ajax = function(plugin, params)
	{
		return new Promise((resolve, reject) => {

			ShowLoading('');

			$.post("/engine/ajax/controller.php?mod=billing", { plugin: plugin, params: params, hash: this.hash }, function(result)
			{
				HideLoading('');

				if( result.status == 'ok' )
				{
					resolve(result.data);
				}

				reject(result.message);

			}, "json");
		});
	}

	this.declension = function( number, currency )
	{
		currency = currency.split(',');
		cases = [2, 0, 1, 1, 1, 2];

		return ' ' + currency[ (number%100>4 && number%100<20)? 2 : cases[(number%10<5)?number%10:5] ];
	}
}

let BillingJsCore = new BillingJS(dle_login_hash);