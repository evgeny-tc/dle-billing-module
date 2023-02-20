<?php

return array
(
	/* 0.7.4 */
	'invoice_max_num' => "Вы имеете больше %s неоплаченных квитанций, просим вас их оплатить или удалить.",
	'invoice_good_desc' => "Пополнение баланса пользователя",
	'invoice_paid_error' => "Квитанция уже оплачена, невозможно удалить.",
	'pay_incorect_sum' => "Введите корректную сумму.",
	'pay_invoice_now' => "Оплатить",

	/* 0.7 */
	'handler_error_id' => "Номер квитанции не получен",

	/* Cabinet */
	'cabinet_off' => "Личный кабинет отключен",
	'cabinet_controller_error' => "Файл плагина user.%s не найден!",
	'cabinet_metod_error' => "Метод плагина user.%s->%s не найден!",
	'cabinet_theme_error' => "Невозможно загрузить шаблон ",
	'cabinet_now' => "Сегодня в ",
	'cabinet_rnow' => "Вчера в ",

	/* Pay */
	'pay_need_login' => "Требуется авторизация!",
	'pay_hash_error' => "Время ожидания модуля закончилось. Повторите попытку",
	'pay_paysys_error' => "Платёжная система не выбрана",
	'pay_summa_error' => "Не указана сумма",
	'pay_minimum_error' => "Минимальная сумма оплаты для %s составляет %s %s",
	'pay_max_error' => "Максимальная сумма оплаты для %s составляет %s %s",
	'pay_main_error' => "Пополнение баланса не доступно",
	'pay_invoice_error' => "Квитанция не найдена",
	'pay_invoice_pay' => "Квитанция уже оплачена",
	'pay_invoice_payment' => "Платежная система не соответствует указанной в квитанции",
	'pay_file_error' => "Файл платёжной системы не найден!",

	'pay_invoice' => "Квитанция #{id}",
	'pay_msgOk' => "Пополнения баланса через %s на %s %s",

	'pay_error_title' => "Ошибка",

	'pay_getErr_key' => "Ключ доступа платёжной системы устарел",
	'pay_getErr_paysys' => "Платежная система не найдена",
	'pay_getErr_invoice' => "Квитанция не найдена, либо уже оплачена",
	'pay_desc' => "Пополнение баланса пользователя %s на сумму %s %s",

	/* Refund */
	'refund_error_requisites' => "Не указаны <a href=\"\">реквизиты</a>",
	'refund_error_balance' => "Недостаточно средств",
	'refund_error_minimum' => "Минимальная сумма для вывода - %s %s",
	'refund_msgOk' => "Вывод средств из системы, номер запроса - %s",
	'refund_wait' => "Ожидается",
	'refund_email_title' => "Запрос вывода средств",
	'refund_email_msg' => "Пользователь %s запросил вывод средств в размере %s %s на реквизиты %s.<br /><br />Подробнее - %s",
	'refund_ok_title' => "Запрос создан",
	'refund_ok_text' => "Ваш запрос  вывода средств создан. В ближайшее время его рассмотрит администратор. <br>",

	/* Transfer */
	'transfer_error_get' => "Получатель не найден",
	'transfer_error_minimum' => "Минимальная сумма для перевода - %s %s <br>",
	'transfer_error_name_me' => "Вы не можете отправить средства самому себе",

	'transfer_log_for' => "Перевод средств для <a href=/user/%s>%s</a>, из них комиссия - %s %s",
	'transfer_log_from' => "Перевод средств от <a href=/user/%s>%s</a>",
	'transfer_log_text' => "Перевод для пользователя <a href=\"/user/%s\">%s</a> выполнен. Комиссия составила %s %s<br>",
	'transfer_msgOk' => "Перевод отправлен",

	/* Bonus */
	'bonus_first_comment' => "Бонус первого пополнения баланса",
	'bonus_comment' => "Бонус пополнения баланса",

	'off' => "<div style='background-color: #ff2e2e; color: white; padding: 10px; margin: 5px; border-radius: 5px'>Личный кабинет отключен для всех, кроме администраторов. <br />Включите модуль в админ.панели: Баланс пользователя &rarr; Настройки.</div>"

);

?>
