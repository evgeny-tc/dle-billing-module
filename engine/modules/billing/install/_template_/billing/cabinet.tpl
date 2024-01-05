<link media="screen" href="/templates/{module.skin}/billing/css/styles.css" type="text/css" rel="stylesheet" />

[panel]
	<div class="billing-panel">

		<img src="{user.foto}" class="billing-foto" title="{user.name}">

		<div class="billing-balance">
			<div>
				<a href="/{module.cabinet}/pay/" class="ui-button billing-pay-btn[active]pay[/active]">Пополнить</a>
			</div>
			<span>Текущий баланс</span>
			<br>{user.balance} &#8381;
		</div>
	</div>

	<div class="billing-menu">
		<div class="billing-menu-content">
			<a href="/{module.cabinet}/log/" title="История движения средств" class="billing-item[active]log[/active]">Операции по счету</a>
			<a href="/{module.cabinet}/invoice/" title="Поступление средств" class="billing-item[active]invoice[/active]">Платежи</a>
			[plugin]
				<a href="/{module.cabinet}/{plugin.tag}/" title="{plugin.name}" class="{plugin.active}">{plugin.name}</a>
			[/plugin]
		</div>
	</div>

[/panel]

<div class="billing-content">
	{content}
</div>

<script>
	const scrollContainer = document.querySelector(".billing-menu");

	scrollContainer.addEventListener("wheel", (evt) => {
		evt.preventDefault();
		scrollContainer.scrollLeft += evt.deltaY;
	});

</script>
