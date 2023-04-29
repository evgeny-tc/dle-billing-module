<h4>Список квитанций</h4>
<table class="billing-table">
	<tr>
		<td width="60"><center><b>№</b></center></td>
		<td><center><b>Дата</b></center></td>
		<td><center><b>Оплата за услуги</b></center></td>
		<td><center><b>Сумма</b></center></td>
		<td><center></center></td>
	</tr>
	[invoice]
	<tr>
		<td><center>#{id}</center></td>
		<td><center>{creat-date=j.m.Y G:i}</center></td>
		<td><center>{desc}</center></td>
		<td><center>{sum}</center></td>
        <td><center>
			[paid]<button type="button" class="btn" onclick="window.location = '{paylink}'">Счёт оплачен</button>[/paid]
			[not_paid]
				<button name="invoice_delete" value="{id}" class="btn">Удалить</button>
				<button type="button" onclick="window.location = '{paylink}'" class="btn">Оплатить</button>
			[/not_paid]
		</center></td>
	</tr>
	[/invoice]
	[not_invoice]
	<tr>
		<td colspan="4">&raquo; Записей не найдено</td>
	</tr>
	[/not_invoice]
</table>

[paging]
	<div class="billing-pagination">
		[page_link]<a href="{page_num_link}">{page_num}</a>[/page_link]
		[page_this] <strong>{page_num}</strong> [/page_this]
	</div>
[/paging]