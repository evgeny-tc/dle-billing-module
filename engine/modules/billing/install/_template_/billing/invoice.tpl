<h5 class="blue">Список платежей</h5>

<form method="post">
	<table class="billing-table">
		<tr>
			<td width="60"><b>№</b></td>
			<td><b>Дата</b></td>
			<td><b>Оплата за услуги</b></td>
			<td><b>Сумма</b></td>
			<td width="20%"></td>
		</tr>
		[invoice]
		<tr [paid]style="color: green"[/paid]>
			<td>#{id}</td>
			<td>{creat-date=j.m.Y G:i}</td>
			<td>{desc}</td>
			<td>{sum}</td>
			<td>
				[paid]<button type="button" class="billing-btn_clear" onclick="window.location = '{paylink}'">Оплачено</button>[/paid]
				[not_paid]
					<button type="button" onclick="window.location = '{paylink}'" class="billing-btn_clear">Оплатить</button>
					<button name="invoice_delete" value="{id}" class="billing-btn_clear">Удалить</button>
				[/not_paid]
			</td>
		</tr>
		[/invoice]
		[not_invoice]
		<tr>
			<td colspan="5">&raquo; Записей не найдено</td>
		</tr>
		[/not_invoice]
	</table>
	<input type="hidden" name="bs_hash" value="{hash}" />
</form>

[paging]
	<div class="billing-pagination">
		[page_link]<a href="{page_num_link}">{page_num}</a>[/page_link]
		[page_this] <strong>{page_num}</strong> [/page_this]
	</div>
[/paging]