<h4>{title}</h4>

<hr />

<form action="" method="post">
	<input type="hidden" name="billingHash" value="{hash}" />

	<div class="billing-pay-grid">
		<div>
			<table width="100%">
				<tr>
					<td>Статус платежа:</td>
					<td style="color: blue">Ожидание оплаты..</td>
				</tr>
				<tr>
					<td>Сумма платежа:</td>
					<td>[old]<s>{old.invoice.get} {old.invoice.get.currency}</s>[/old] {invoice.get} {invoice.get.currency}</td>
				</tr>
				[coupon]
				<tr>
					<td>Купон:</td>
					<td>
						<input type="text" name="coupon" value="{coupon}" style="height: 20px; width: 130px">
						<button type="submit" name="coupon_check" style="margin-left: -22px; background-color: rgba(28,28,28,0); border: none; cursor: pointer">
							&#10003;
						</button>
						<br>{coupon_result}
					</td>
				</tr>
				[/coupon]
				[more]
				<tr>
					<td>{title}</td>
					<td>{value}</td>
				</tr>
				[/more]
			</table>
			<p style="text-align: center">
				<input type="submit" name="submit" class="btn" value="Оплатить">
			</p>
		</div>
		<div>
			<div class="billing-pay-labels">
				[payment_balance]
				<div class="billing-pay-grid">
					<div>
						<label class="billing-pay-label">
							<input name="billingPayment" id="balance" type="radio" value="balance" class="paymentSelect">
							<img src="/templates/{module.skin}/billing/icons/balance.png" alt="Оплатить с баланса" title="Оплатить с баланса" />
						</label>
					</div>
					<div class="payment__desc"><span>К оплате</span><br>{invoice.get} {invoice.get.currency}</div>
				</div>
				<hr class="payment__section">
				[/payment_balance]
				[payment]
				<div class="billing-pay-grid">
					<div>
						<label class="billing-pay-label">
							<input name="billingPayment" id="{payment.name}" type="radio" value="{payment.name}" class="paymentSelect">
							<img src="/templates/{module.skin}/billing/icons/{payment.name}.png" onerror="this.src='/templates/{module.skin}/billing/icons/payment_icon.png'" alt="{payment.title}" title="{payment.title}" />

						</label>
					</div>
					<div class="payment__desc"><span>К оплате</span><br>{payment.topay} {payment.currency}</div>
				</div>
				<hr class="payment__section">
				[/payment]
			</div>
		</div>
	</div>

</form>
