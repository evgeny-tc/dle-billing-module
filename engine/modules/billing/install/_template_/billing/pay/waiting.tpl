<form action="" method="post">
	<input type="hidden" name="billingHash" value="{hash}" />

	<!-- Оформление из шаблона -->
	<link href="/templates/Default/css/engine.css" rel="stylesheet" type="text/css">
	<link href="/templates/Default/css/styles.css" rel="stylesheet" type="text/css">

	<div class="billing-pay-grid">
		<div>
			<div class="billing-pay-fields">
				<div class="billing-form-item">
					<div>Платеж</div>
					#{id}
				</div>
				<div class="billing-form-item">
					<div>Назначение</div>
					{invoice.desc}
				</div>
				<div class="billing-form-item">
					<div>Статус</div>
					Ожидание оплаты
				</div>
				<div class="billing-form-item">
					<div>Сумма к зачислению</div>
					[old]<s>{old.invoice.get} {old.invoice.get.currency}</s>[/old] {invoice.get} {invoice.get.currency}
				</div>
				[more]
					<div class="billing-form-item">
						<div>{title}</div>
						{value}
					</div>
				[/more]
			</div>
		</div>
		<div>
			<h5 class="blue">Выберите способ оплаты</h5>
				[payment_balance]
					<label class="billing-pay-label billing-payment-item">
						<input name="billingPayment" id="{payment.name}" type="radio" value="balance" class="paymentSelect">
						<div class="payment__desc">{invoice.get} {invoice.get.currency}</div>
						<img src="/templates/{module.skin}/billing/icons/balance.png" alt="Оплатить с баланса" title="Оплатить с баланса" />
					</label>
				[/payment_balance]
				[payment]
					<label class="billing-pay-label billing-payment-item">
						<input name="billingPayment" id="{payment.name}" type="radio" value="{payment.name}" class="paymentSelect">
						<div class="payment__desc">{payment.topay} {payment.currency}</div>
						<img src="/templates/{module.skin}/billing/icons/{payment.name}.png" onerror="this.src='/templates/{module.skin}/billing/icons/payment_icon.png'" alt="{payment.title}" title="{payment.title}" />
					</label>
				[/payment]
				[coupon]
					<div style="clear: both;padding-top:10px"></div>
					<h5 class="blue">Использовать купон?</h5>

					<input type="text" name="coupon" value="{coupon}" placeholder="Введите код купона и примените его &rarr;" style="width: 80%">
					<button title="Применить" type="submit" name="coupon_check" class="btn btn-white">
						&#10003;
					</button>

					[coupon_result]
						<div style="padding: 10px">
							{coupon_result}
						</div>
					[/coupon_result]
				[/coupon]
				<p>
					<input type="submit" name="submit" class="btn" value="Оплатить">
				</p>
			</div>
	</div>

</form>
