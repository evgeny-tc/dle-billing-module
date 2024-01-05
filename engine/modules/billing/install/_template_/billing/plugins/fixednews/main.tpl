<div id='mainnewstpl' title='Оплатить публикацию на главной' style='display:none'>

	<link media="screen" href="/templates/{module.skin}/billing/css/styles.css" type="text/css" rel="stylesheet" />

	[error]
	<div style="color: red; border: 1px solid red; padding: 10px">
		{error.text}
	</div>
	[/error]

		<table width="100%" class="billing-table">
			<tr>
				<td width="30%">Статья:</td>
				<td>{post.title} (от {post.autor})</td>
			</tr>
			<tr>
				<td></td>
				<td>Статья будет опубликована на главной странице сайта</td>
			</tr>
		</table>

	<div class="billing_modal_footer">
		<h2>{pay.sum} {pay.sum.currency}</h2>
		<button type="submit" class="btn" onClick="BillingNews.Pay(2)">
			<span>Перейти к оплате</span>
		</button>
	</div>
</div>
