<div id='paygrouptpl' title='Переход в группу {pay.group_name}' style='display:none'>

    <link media="screen" href="/templates/{module.skin}/billing/css/styles.css" type="text/css" rel="stylesheet" />

    <table width="100%" class="billing-table">
        <tr>
            <td>Текущая группа:</td>
            <td>{user.group_name}</td>
        </tr>
        [pay_time]
        <tr>
            <td>Срок оплаты:</td>
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
        <tr>
            <td>К оплате:</td>
            <td><span id="BillingGroupBalancePay"></span><td>
        </tr>
    </table>

    <input type="hidden" id="BillingGroupCurrency" value="{module.currency}">

    <div style="text-align: center; padding-top: 10px">
        <button type="submit" class="btn" onClick="BillingGroup.Pay()">
            <span>Перейти  оплате</span>
        </button>
    </div>
</div>