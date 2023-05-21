<table class="billing-table">
	<tr>
		<td colspan="4"><b>История движения средств</b></td>
	</tr>
	[history]
	<tr>
		<td width="80"><center>{date=j.m.Y G:i}</center></td>
		<td style="padding: 0 10px">{comment}</td>
		<td width="120">{sum}</td>
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
