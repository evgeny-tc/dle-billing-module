<script>
if( typeof window['openPayPage'] != 'function' )
{
	function openPayPage(e)
	{
	  var h = 165,
		  w = 500;

	  window.open(e, '', 'scrollbars=1,height='+Math.min(h, screen.availHeight)+',width='+Math.min(w, screen.availWidth)+',left='+Math.max(0, (screen.availWidth - w)/2)+',top='+Math.max(0, (screen.availHeight - h)/2));
	}
}
</script>

<fieldset style="padding:10px;margin:10px;border: 1px solid #c2c2c2;">
	<legend><b>Доступ закрыт</b></legend>

	<p>Требуется оплатить {price} RUR.</p>

	[time]<p>После оплаты доступ будет открыт в течении {time}</p>[/time]

	[login]<p>Оплатить из <a href="/billing.html/payhide/pay/sign/{link}" onclick="openPayPage(this.href); return false;">личного кошелька на сайте</a>.</p>[/login]

	[not-login]<p>Для оплаты Вам необходимо <a href="/index.php?do=register">зарегистрироваться</a> либо войти на сайт под своим именем.</p>[/not-login]
</fieldset>
