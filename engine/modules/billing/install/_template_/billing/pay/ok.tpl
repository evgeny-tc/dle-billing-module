<style>
	.b__invoice
	{
		background-color: #eeeeee;
		font-size: 12px;
		color: #4f4f4f;
		padding: 10px;
		width: 70%;
		margin: auto;
	}

	.b__invoice_payeed
	{
		background-image: url("/templates/{module.skin}/billing/icons/payed.png");
		float: right;
		display: block;
		width: 120px;
		height: 88px;
		margin: 15px;
	}
</style>

<div class="b__invoice">
	<div class="b__invoice_payeed"></div>
	<table width="60%">
		<tr>
			<td>Счет №</td>
			<td>{invoice.id}</td>
		</tr>
		<tr>
			<td>Дата создания:</td>
			<td>{invoice.date.create}</td>
		</tr>
		<tr>
			<td>Дата оплаты:</td>
			<td>{invoice.date.pay}</td>
		</tr>
		<tr>
			<td>Платежная система:</td>
			<td>{invoice.payment.title}</td>
		</tr>
		<tr>
			<td>К оплате:</td>
			<td>{invoice.get} {invoice.get.currency}</td>
		</tr>
		<tr>
			<td>Оплачено на стороне агрегатора:</td>
			<td>{invoice.pay} {invoice.pay.currency}</td>
		</tr>
		<tr>
			<td>Описание:</td>
			<td>{invoice.desc}</td>
		</tr>
	</table>
</div>

