<h5>Создать запрос</h5>

<form action="" method="post">
	<ul class="ui-form">
		<li class="form-group">
			<label>Сумма вывода:</label>
			<input type="text" value="{minimum}" name="bs_summa" style="width: 30%" required> {minimum.currency}
			<span style="margin-left: 50px">( минимум: {minimum} {minimum.currency}, из них комиссия {commission}% )</span>
		</li>
		<li class="form-group">
			<label>Ваши реквизиты:</label>
			<input type="text" name="bs_requisites" style="width: 100%" value="{requisites}" required>
		</li>
	</ul>

	<p style="padding-top: 20px">
		<button type="submit" name="submit" class="btn"><span>Создать запрос</span></button>
	</p>

	<input type="hidden" name="bs_hash" value="{hash}" />
</form>

<h5>История вывода средств</h5>

<table class="billing-table">
	<tr>
		<td><b>Дата</b></td>
        <td><b>Сумма</b></td>
        <td><b>Из них комисия</b></td>
        <td><b>Реквизиты</b></td>
        <td><b>Выполнено</b></td>
	</tr>

    [history]
	<tr>
		<td>{date=j.m.Y G:i}</td>
		<td>{refund.sum} {refund.sum.currency}</td>
        <td>{refund.commission} {refund.commission.currency}</td>
        <td>{refund.requisites}</td>
        <td>{refund.status}</td>
    </tr>
    [/history]

	[not_history]
    <tr>
		<td colspan="5">&raquo; Вывода средств не было</td>
    </tr>
    [/not_history]
</table>

[paging]
	<div class="billing-pagination">
		[page_link]<a href="{page_num_link}">{page_num}</a>[/page_link]
		[page_this] <strong>{page_num}</strong> [/page_this]
	</div>
[/paging]
