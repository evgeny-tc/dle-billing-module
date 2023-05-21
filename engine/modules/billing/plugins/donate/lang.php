<?PHP

return array
(
	'title' => "Пожертвования",
	'pay_desc' => "Пожертвование для: ",

	'setting' => "Настройки плагина",
	'pay_from' => "Для %s: %s",
	'pay' => "От %s: %s",
	'pay_no_comment' => "Пользователь не оставил комментария",

	'setting_get' => "Включить:",
	'setting_get_desc' => "Разрешить пользователям делать пожертвования",
	'setting_stop' => "Запретить прием пожертвований:",
	'setting_stop_desc' => "Запретить прием пожертвований на указанные <b>логины</b> пользователей.<br />Через запятую.",
	'setting_alertpm' => "Включить оповещение в личную почту:",
	'setting_alertpm_desc' => "Отправить пользователю сообщение в персональную почту на сайте при изменении его баланса после получения пожертвования",
	'setting_alertemail' => "Включить оповещение по email:",
	'setting_alertemail_desc' => "Отправить пользователю сообщение на email при изменении его баланса после получения пожертвования",
	'setting_removehtml' => "Удалять html из комментариев:",
	'setting_removehtml_desc' => "Удалять html теги из комментариев к пожертвованиям",
	'setting_comm' => "Комиссия сайта:",
	'setting_comm_desc' => "Данный процент от суммы вывода будет удерживаться сайтом в качестве комиссии",

	'mail_off' => "<br /><span style='color:red'>Выключено в <a href='?mod=billing&m=settings' target='_blank'><u>настройках уведомлений</u></a></span>",

	'js_ok' => "Теги созданы",
	'js_text' => "Вы можете использовать следующие теги в .tpl файлах Вашего шаблона<br /><br /><b>Панель пожертвований:</b><br />",
	'js_text_3' => "<br /><b>Список принятых пользователем пожертвований:</b> <span style='float: right'>[ <a href='https://dle-billing.ru/doc/widgets/' target='_blank'><u>Подробнее на сайте</u></a> ]</span>",
	'js_text_4' => "<br /><b>Всего пожертвовано пользователю:</b>",
	'js_close' => "<br /><b>Внимание. Теги не сохраняются в модуле</b>.",
	'js_link' => "Сделать пожертвование",

	'create' => "Создание тегов",
	'create_login' => "Пользователь:",
	'create_login_desc' => "Логин пользователя-получателя пожертвований<br /><b>{login}</b> - для полной, краткой новости, комментариев.<b></b>",
	'create_min' => "Минимальный платеж:",
	'create_min_desc' => "Минимальная сумма пожертвования",
	'create_max' => "Максимальный платеж:",
	'create_max_desc' => "Максимальная сумма пожертвования (необязательно)",
	'create_all' => "Собираемая сумма:",
	'create_all_desc' => "В панели пожертвований будет отмечено как только наберется указанная сумма (необязательно)",
	'create_theme_panel' => "Шаблон информационной панели:",
	'create_theme_panel_desc' => "Название файла шаблона для информационной панели",
	'create_code' => "Группировка пожертвований:",
	'create_code_desc' => "Пользователь может принимать пожертвования с неограниченного количества страниц. Для разделения платежей в общем списке - используйте уникальные <b>цифровые</b> обозначения.<br />Например, {news-id} - для разных новостей (только в shortstory.tpl и/или fullstory.tpl).",

	'error_tpl' => "Шаблон не загружен - ",

	'ajax_er1' => "Прием пожертвований отключен",
	'ajax_er2' => "Прием пожертвований для этого пользователя заблокирован",
	'ajax_er3' => "Максимальная сумма пожертвования - %s %s",
	'ajax_er4' => "Минимальная сумма пожертвования - %s %s",
	'ajax_er5' => "На вашем балансе недостаточно средств, <a href='/%s.html/pay/main/sum/%s' target='_blank'>пополните ваш баланс</a> и обновите эту страницу",
	'ajax_er6' => "Вы не можете перевести средства самому себе",
	'ajax_er7' => "Пользователь не найден",
	
	'next' => "Далее",
);

?>