<div id='paygrouptpl' title='Переход в группу &laquo;{pay.group_name}&raquo;' style='display:none'>

    <link media="screen" href="/templates/{module.skin}/billing/css/styles.css" type="text/css" rel="stylesheet" />

    <table width="100%" class="billing-table">
        <tr>
            <td>Текущая группа:</td>
            <td>{user.group_name}</td>
        </tr>
        [pay_time]
        <tr>
            <td>Оплатить:</td>
            <td>
                <select id="BillingGroupDays" onchange="BillingGroup.Days()" style="width: 140px; height: 30px; padding: 2px">
                    [select]
                    <option value="{days}" data-price="{price}" data-currency="{currency}">{title}</option>
                    [/select]
                </select>
            </td>
        </tr>
        [/pay_time]
        [pay_one]
        <tr>
            <td>Время перехода: </td>
            <td>навсегда<input type="hidden" id="BillingGroupDays" data-price="{pay.sum}" data-currency="{pay.sum.currency}" value="1"></td>
        </tr>
        [/pay_one]
    </table>

    <input type="hidden" id="BillingGroupCurrency" value="{module.currency}">

    <div class="billing_modal_footer">
        <h2 id="BillingGroupBalancePay"></h2>
        <button type="submit" class="btn" onClick="BillingGroup.Pay()">
            <span>Перейти к оплате</span>
        </button>
    </div>
</div>