<form action="" id="payform" method="post">

	<span class="billing-pay-step billing-pay-step-start">
		Пополнить баланс на сумму:
		<input type="text" value="{get.sum}" name="billingPaySum" id="billingPaySum" style="height: 40px; width: 100px" required> {module.get.currency}

		<button type="submit" id="billingPayBtn" name="submit" class="btn" style="margin-left:25px;">
			Продолжить
		</button>
	</span>

	<input type="hidden" name="billingHash" value="{hash}" />
</form>