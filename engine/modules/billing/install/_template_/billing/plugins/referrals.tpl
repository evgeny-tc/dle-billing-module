<h5>Выша реферальная ссылка</h5>

<p>
	{link}
</p>

<br>

<h5>Действия рефералов</h5>

<table class="billing-table">
	<tr>
		<td width="20%"><b>Пользователь</b></td>
        <td width="15%"><b>Дата</b></td>
        <td><b>Действие</b></td>
        <td width="20%"><b>Отчисления</b></td>
	</tr>

    [history]
	<tr>
		<td>{referral.name}</td>
		<td>{date=j.m.Y}</td>
        <td>{referral.desc}</td>
        <td>{referral.bonus} {referral.bonus.currency}</td>
    </tr>
    [/history]

	[not_history]
    <tr>
		<td colspan="5">&raquo; Вознаграждений нет</td>
    </tr>
    [/not_history]
</table>

[paging]
	<div class="billing-pagination">
		[page_link]<a href="{page_num_link}">{page_num}</a>[/page_link]
		[page_this] <strong>{page_num}</strong> [/page_this]
	</div>
[/paging]

<br>

<h5>Приглашенные пользователи ({count})</h5>

<p>
	{list}
</p>
