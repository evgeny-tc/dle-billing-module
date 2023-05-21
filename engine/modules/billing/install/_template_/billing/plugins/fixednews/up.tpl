<div id='upnewstpl' title='Поднятие статьи' style='display:none'>

	<link media="screen" href="/templates/{module.skin}/billing/css/styles.css" type="text/css" rel="stylesheet" />

	[error]
	<div style="color: red; border: 1px solid red; padding: 10px">
		{error.text}
	</div>
	[/error]

		<table width="100%" class="billing-table">
			<tr>
				<td width="30%">Статья:</td>
				<td>{post.title}</td>
			</tr>
			<tr>
				<td>Автор:</td>
				<td>{post.autor}</td>
			</tr>
			<tr>
				<td></td>
				<td>Единоразовое поднятие статьи</td>
			</tr>
			<tr>
				<td>К оплате:</td>
				<td>{pay.sum} {pay.sum.currency}<td>
			</tr>

		</table>

		<div style="text-align: center; padding-top: 10px">
			<button type="submit" class="btn" onClick="BillingNews.Pay(1)">
				<span>Перейти к оплате</span>
			</button>
		</div>
</div>
