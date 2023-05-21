/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

$(function()
{
    $('.billing-donate-send').click(function()
    {
        let id = $(this).data("id");
        let balance = $(this).data("balance");
        let min = $(this).data("min");
        let max = $(this).data("max");
        let modal_title = $(this).data("langerror");

        let sum = $("#billing-donate-value-" + id).val();
        let comment = $("#billing-donate-comment-" + id).val();

        let toautor = $(this).data("user");
        let togrouping = $(this).data("group-id");

        if( parseFloat( min ) > parseFloat( sum ) )
        {
            DLEalert( $(this).data("error-min"), modal_title );

            return false;
        }

        if( parseFloat( max ) && parseFloat( max ) < parseFloat( sum ) )
        {
            DLEalert( $(this).data("error-max"), modal_title );

            return false;
        }

        BillingJsCore.ajax('donate', {
            user: toautor,
            group_id: togrouping,
            sum: sum,
            comment: comment
        })
            .then(
                result => {
                    window.location.href = result.url;
                },
                error => {
                    DLEalert( error, modal_title );
                }
            );
    });
});
