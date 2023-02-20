<h4>{title}</h4>

<hr />

<table width="400" style="margin: 0 auto; text-align: left">
	<tr>
		<td>Статус платежа:</td>
		<td style="color: blue">Ожидание оплаты..</td>
	</tr>
	[step2]
	<tr>
		<td>К оплате:</td>
		<td>{invoive.pay} {invoive.pay.currency}</td>
	</tr>
	<tr>
		<td>Способ оплаты:</td>
		<td>{invoive.payment.title}</td>
	</tr>
	[/step2]
	<tr>
		<td>Будет зачислено:</td>
		<td>{invoive.get} {invoive.get.currency}</td>
	</tr>
</table>

<br />
[step1]
Выберите способ оплаты:
			<br />
		[payment]
			<label class="billing-pay-label">
				<input name="billingPayment" id="{payment.name}" type="radio" value="{payment.name}" class="paymentSelect">
				<img src="{THEME}/billing/icons/{payment.name}.png" alt="{payment.title}" title="{payment.title}" />
				{payment.topay} {payment.currency}
			</label>
		[/payment]
[/step1]
<center>{button}</center>