/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

function BillingNews()
{
	this.post_id = 0;
	this.days = 0;

	this.tags = [
		"#fixednewstpl",
		"#upnewstpl",
		"#mainnewstpl"
	];

	this.type = [
		"fixed",
		"up",
		"main"
	];

	this.Form = function( tag, post_id, pay = 0 )
	{
		let _this = this;

		$(this.tags[tag]).remove();

		this.post_id = post_id;

		let height = 280;

		BillingJsCore.ajax('fixednews', {
			type: this.type[tag],
			post_id: post_id,
			days: this.days,
			pay: pay
		})
			.then(
				result => {
					if( result.url )
					{
						window.open(result.url);

						_this.invoice_id = result.invoice_id;

						height = 140;
					}

					_this.ShowModal(tag, result.html.replace('#modal_id#', _this.tags[tag].substring(1)), height);
					_this.Days();
				},
				error => {
					DLEalert( error, 'Ошибка' );
				}
			);
	};

	this.Days = function()
	{
		var change = $("#BillingFixedDays option:selected");
		var balance = parseFloat($("#BillingFixedMyBalance").val());
		var price = change.attr('data-price');

		this.days = change.val();

		$("#BillingFixedBalancePay").html( price + ' ' + change.attr('data-currency'));
	}

	this.Pay = function( tag )
	{
		BillingNews.Form( tag, this.post_id, 1 );
	}

	this.ShowModal = function( tag, modal, height = 0 )
	{
		$("body").append(modal);

		$(this.tags[tag]).dialog(
		{
			autoOpen: true,
			show: 'fade',
			hide: 'fade',
			resizable: false,
			width: 400,
			height: height
		});
	}
}

var BillingNews = new BillingNews();
