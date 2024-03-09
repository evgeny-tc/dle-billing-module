<h5 class="blue">Ваша реферальная ссылка</h5>

<p>
	<pre>{link}</pre>
</p>

<br>

<h5 class="blue">Вознаграждения</h5>

<table class="billing-table">
	<tr>
		<td><b>Пользователь</b></td>
        <td><b>Дата</b></td>
        <td><b>Действие</b></td>
        <td><b>Вознаграждение</b></td>
	</tr>

    [history]
	<tr>
		<td>{referral.name}</td>
		<td>{date=j.m.Y}</td>
        <td>{referral.desc}</td>
        <td><span style="color: green">+{referral.bonus} {referral.bonus.currency}</span></td>
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
		[page_link]<a href="%s">%s</a>[/page_link]
		[page_this] <strong>%s</strong> [/page_this]
	</div>
[/paging]

<br>

<h5 class="blue">Приглашенные пользователи ({count})</h5>

<p>
	{list}
</p>
