<h5 class="blue">История движения средств</h5>

<table class="billing-table">
	[history]
	<tr>
		<td style="width: 5%">
			<img src="/engine/skins/billing/plugins/{plugin}.png" onError="this.src='/engine/skins/billing/icons/transactions.png'" class="billing-history-item-image">
		</td>
		<td style="padding: 0 10px">
			<div style="font-size: 11px; color: grey">{date=j.m.Y G:i}</div>
			{comment}</td>
		<td>{sum}</td>
	</tr>
	[/history]
	[not_history]
	<tr>
		<td colspan="2">&raquo; Записей не найдено</td>
	</tr>
	[/not_history]
</table>

[paging]
	<div class="billing-pagination">
		[page_link]<a href="{page_num_link}">{page_num}</a>[/page_link]
		[page_this] <strong>{page_num}</strong> [/page_this]
	</div>
[/paging]
