<h4>Оплаченный доступ</h4>

<table class="billing-table">
	<tr>
		<td><b>Дата</b></td>
		<td><b>Доступ открыт на странице</b></td>
		<td><b>Оплачено</b></td>
		<td><b>Доступ</b></td>
	</tr>

	[history]
	<tr>
		<td>{date=j.m.Y G:i}</td>
		<td>{page} [pay_desc] - {pay_desc} [/pay_desc]</td>
		<td>{price}</td>
		<td>{time}</td>
	</tr>
	[/history]

	[not_history]
	<tr>
		<td colspan="4">&raquo; Записей не найдено</td>
	</tr>
	[/not_history]
</table>

[paging]
<div class="billing-pagination">
	[page_link]<a href="{page_num_link}">{page_num}</a>[/page_link]
	[page_this] <strong>{page_num}</strong> [/page_this]
</div>
[/paging]
