<h5>Мои ключи</h5>

<table class="billing-table">
	<tr>
		<td><b>Дата</b></td>
        <td><b>Цена</b></td>
        <td><b>Ключ</b></td>
	</tr>

    [history]
	<tr>
		<td>{date=j.m.Y G:i}</td>
        <td>{price} {price.currency}</td>
        <td>{key}</td>
    </tr>
    [/history]

	[not_history]
    <tr>
		<td colspan="5">&raquo; Ключей нет</td>
    </tr>
    [/not_history]
</table>

[paging]
	<div class="billing-pagination">
		[page_link]<a href="{page_num_link}">{page_num}</a>[/page_link]
		[page_this] <strong>{page_num}</strong> [/page_this]
	</div>
[/paging]
