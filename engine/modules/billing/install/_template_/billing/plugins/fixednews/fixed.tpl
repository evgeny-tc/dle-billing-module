<div id='fixednewstpl' title='Фиксация статьи' style='display:none'>

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
				<td>Срок фиксации:</td>
				<td>
					<select id="BillingFixedDays" onchange="BillingNews.Days()" style="width: 140px">
						[select]
							<option value="{days}" data-price="{price}" data-currency="{currency}">{title}</option>
						[/select]
					</select>
				</td>
			</tr>
		</table>

	<div class="billing_modal_footer">
		<h2 id="BillingFixedBalancePay"></h2>
		<button type="submit" class="btn" onClick="BillingNews.Pay()">
			<span>Перейти к оплате</span>
		</button>
	</div>
</div>
