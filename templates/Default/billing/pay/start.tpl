<form action="" id="payform" method="post">

<h4>Пополнение баланса</h4>

	<span class="billing-pay-step" style="background-color: #f1fbff; line-height: 60px;">
		1. Пополнить баланс на сумму:
		<input type="text" value="{get.sum}" name="billingPaySum" id="billingPaySum" style="height: 40px; width: 100px" required> {module.get.currency}

		<button type="submit" id="billingPayBtn" name="submit" class="btn" style="margin-left:25px;">
			Пополнить
		</button>
	</span>

	<input type="hidden" name="billingHash" value="{hash}" />
</form>