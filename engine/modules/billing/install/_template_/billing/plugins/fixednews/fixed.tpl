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
				<td>{post.title} ({post.autor})</td>
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
			<tr>
				<td>К оплате:</td>
				<td><span id="BillingFixedBalancePay"></span><td>
			</tr>
		</table>

		<div style="text-align: center; padding-top: 10px">
			<span id="BillingFixedBtn">
				<button type="submit" class="btn" onClick="BillingNews.Pay(0)">
					<span>Перейти к оплате</span>
				</button>
			</span>
		</div>
</div>
