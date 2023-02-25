<h4>{title}</h4>

<hr />

<div class="billing-pay-grid">
	<div>
		<table >
			<tr>
				<td>Статус платежа:</td>
				<td style="color: blue">Ожидание оплаты..</td>
			</tr>
			<tr>
				<td>Будет зачислено:</td>
				<td>{invoive.get} {invoive.get.currency}</td>
			</tr>
			[more]
				<tr>
					<td>{title}</td>
					<td>{value}</td>
				</tr>
			[/more]
		</table>
		<p>
			{button}
		</p>
	</div>
	<div>
		<h5>Выберите способ оплаты:</h5>
				<br />
				<div class="billing-pay-labels">
					[payment]
					<div class="billing-pay-grid">
						<div>
							<label class="billing-pay-label">
								<input name="billingPayment" id="{payment.name}" type="radio" value="{payment.name}" class="paymentSelect">
								<img src="{THEME}/billing/icons/{payment.name}.png" alt="{payment.title}" title="{payment.title}" />

							</label>
						</div>
						<div style="valign: middle">{payment.topay} {payment.currency}</div>
					</div>
					<hr>
					[/payment]
			</div>
	</div>
</div>
