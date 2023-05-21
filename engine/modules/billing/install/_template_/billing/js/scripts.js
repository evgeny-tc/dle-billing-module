/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

function BillingJS( hash )
{
	this.hash = hash;

	this.ajax = function(plugin, params)
	{
		return new Promise((resolve, reject) => {

			ShowLoading('');

			$.post("/engine/ajax/BillingAjax.php", { plugin: plugin, hash: this.hash, params: params }, function(result)
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