/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

function BillingGroup()
{
    this.group_id = 0;
    this.days = 0;

    this.Form = function( group_id, pay = 0 )
    {
        let _this = this;
        let height = 240;

        $('#paygrouptpl').remove();

        this.group_id = group_id;

        BillingJsCore.ajax('paygroups', {
            group_id: group_id,
            days: this.days,
            pay: pay
        })
            .then(
                result => {
                    if( result.url )
                    {
                        _this.ShowModal(result.html, 140);
                        BillingJsCore.iframe('Оплата', result.url);

                        return;
                    }

                    _this.ShowModal(result.html, height);
                    _this.Days();
                },
                error => {
                    DLEalert( error, "Предупреждение" );
                }
            );
    };

    this.Pay = function()
    {
        BillingGroup.Form( this.group_id, 1 );
    }

    this.Days = function()
    {
        let change = $("#BillingGroupDays option:selected");

        if( ! change.attr('data-price') )
        {
            change = $("#BillingGroupDays");
        }

        let balance = parseFloat($("#BillingGroupMyBalance").val());
        let price = parseFloat(change.attr('data-price'));

        this.days = change.val();

        $("#BillingGroupBalancePay").html( price.toFixed(2) + ' ' + change.attr('data-currency'));
    }

    this.ShowModal = function( modal, height = 0 )
    {
        $("body").append(modal);

        $("#paygrouptpl").dialog(
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

var BillingGroup = new BillingGroup();