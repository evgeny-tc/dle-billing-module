<?php

return array
(
	'hash_error' => "Время ожидания модуля закончилось. Повторите попытку",
	'access_denied' => "Доступ запрещен",

    'register_pay_unknown_invoice' => "<i>Квитанция не найдена</i>",
    'register_pay_payed_invoice' => "<i>Квитанция оплачена</i>",
    'register_pay_unknown_title' => "<i>неизвестно</i>",
    'register_pay_unknown_currency' => "у.е.",

    'plus_add' => "+ добавить",

    'coupons_title' => "Купоны",
    'coupons' => [
        'menu' => [
            'name' => "Купоны",
            'desc' => "Список купонов на скидку, добавление новых купонов"
        ],
        'list' => [
            'key' => "Купон",
            'value' => "Номинал",
            'time' => "Ограничение",
            'delete' => "Удалить",
            'use' => "Использован",
            'delete_ok' => "Купоны удалены",
            'title' => "Купоны"
        ],
        'create' => [
            'title' => "Добавить",
            'col' => "Количество:",
            'col_desc' => "Сколько купонов создать",
            'type' => "Тип:",
            'type_desc' => "Тип применяемой скидки",
            'value' => "Номинал купона:",
            'value_desc' => "Точная сумма или процент",
            'theme' => "Шаблон купона:",
            'theme_desc' => "Используйте цифру <b>0</b> для обозначения случайного символа",
            'use' => "Количество использований:",
            'use_desc' => "Сколько раз указанный купон может быть применен",
            'date' => "Срок действия:",
            'date_desc' => "После указанной даты купон нельзя будет применить",
            'btn' => "Создать",
            'ok' => "Купоны созданы"
        ],
    ],

    'live_title' => 'События',

    'stats_filter_dates' => [
        'now' => "Сегодня",
        'week' => "Текущая неделя",
        'month'=> "Текущий месяц",
        'year'=> "Текущий год"
    ],

    'refund_date_cancel' => "Отменен",

    'settings_start_admin' => "Главная страница ПУ:",
    'settings_start_admin_desc' => "Главная страница панели управления: плагин/метод/параметр/значение<br />Например: main/main",

    'paysys_url_v2' => "URL обработчика платежей (без ЧПУ):",
    'paysys_url_desc_v2' => "Если данные нужно получать через php://input",

    'settings_invoice_delete_time' => "Время жизни квитанции:",
    'settings_invoice_delete_time_desc' => "Удалять неоплаченные квитанции через указанное количество минут:",
    'plugin_install' => "Плагин установлен",
    'plugin_uninstall' => "Плагин удален",
    'plugin_install_js' => '<div style="text-align: left"><a href="%s" target="_blank">Подключите JS скрипты в ваш шаблон</a></div>',
    'plugin_update' => "Плагин обновлен",

    'plugins_table_head' => [
        '<th style="width: 40px"></th>',
        '<th>Тег</th>',
        '<th>Название</th>',
        '<th>Автор</th>',
        '<th>Версия</th>',
        '<th style="width: 140px">Статус</th>',
        '<th></th>'
    ],

    'plugins_table_status' => [
        'delete' => "Удалить",
        'install' => "Установить",
        'not_install' => "Не установлен",
        'off' => "Выключен",
        'installed' => "Установлен",
        'updating' => "Обновить",
        'need_update' => "Обновите до",
        'confirm' => "Вы уверены?"
    ],

    'need_install' => "Плагин не установлен",
    'need_update' => "Требуется обновить плагин",
    'need_install_go' => "Установить",
    'need_update_go' => "Установить",

    # 0.7.4
	#
	'settings_invoice_max_num' => "Количество неоплаченных квитанций:",
	'settings_invoice_max_num_desc' => "Укажите максимальное количество неоплаченных квитанций, которые могут быть созданы",

	# 0.7.2
	#
	'072_req' => "Реквизиты плательщика: ",
	'072_payer_info' => "Информация о платеже: ",
	'076_handler' => "Обработчик: ",

	# 0.7
	#
	'history_search_oper' => "Операция: ",
	'history_transaction' => "Транзакция: ",
	'history_transaction_text' => "Описание платежа:",
	'history_search_oper_desc' => "Выберите тип транзакции: доход, расход или все операции",
	'history_search_sum' => "Сумма:",
	'history_search_sum_desc' => "Вы можете использовать один из символов сравнения: > <",
	'search_info' => "Показаны результаты поиска по вашему запросу <span style='float: right'><a href=''>Отмена поиска</a></span>",
	'history_max_remove_ok' => "Выбранные транзакции удалены",
	'invoice_all_payments' => "Все платежные системы",
	'invoice_search_sum_get' => "Сумма к получению:",
	'invoice_search_sum_get_desc' => "Фильтр поиска по значению &laquo;Зачислено&raquo;<br>Вы можете использовать один из символов сравнения: > <",
	'invoice_search_date_create' => "Дата и время создания квитанции:",
	'invoice_search_date_pay' => "Дата и время поступления оплаты:",
	'invoice_was_pay' => "Оплачен ",
	'pay_msgOk' => "пополнение баланса: %s на %s %s",
	'pay_from_admin' => "вручную",

	'nullpadding' => "<div style='padding: 10px'>Пусто</div>",
	'null' => "Пусто",

	'date_from' => "от ",
	'date_to' => " до ",
	'help' => "Документация",

	'menu_1' => "Настройки",
	'menu_1_d' => "Настройка параметров модуля, используемая валюта, секретный ключ, уведомления пользователей",
	'menu_2' => "История движения средств",
	'menu_2_d' => "История расходов и доходов пользователей, поиск платежей по параметрам",
	'menu_3' => "Пользователи и группы",
	'menu_3_d' => "Поиск пользователей по логину и балансу, редактирование баланса пользователей и групп",
	'menu_4' => "Поступление средств",
	'menu_4_d' => "Просмотр и редактирование запросов пользователей на пополнение баланса через платежные системы",
	'menu_5' => "Статистика",
	'menu_5_d' => "Статистика дохода и расхода пользователей, статистика плагинов и платежных систем, сводка по доходу сайта",
	'menu_6' => "Каталог плагинов",
	'menu_6_d' => "Каталог плагинов и платежных систем, проверка актуальных версий",
    'menu_7' => "Режим отладки",
    'menu_7_d' => "Просмотр лога входящих запросов на обработчик платежей",

	'tab_1' => "Панель управления",
	'tab_2' => "Платежные системы",
	'tab_3' => "Плагины",

	'logger_text_1' => "Дата и время",
	'logger_text_2' => "Статус",
	'logger_text_3' => "Тип",
	'logger_text_4' => "Содержимое",

	'logger_do_0' => "Получен запрос от платежной системы",
	'logger_do_1' => "Параметры платежа получены",
	'logger_do_3' => "Проверка секретного ключа провалена",
	'logger_do_4' => "Платежная система недоступна",
	'logger_do_5' => "Секретный ключ принят, платежная система доступна",
	'logger_do_6' => "Файл adm.settings.php платежной системы подключен",
	'logger_do_7' => "Номер квинанции не определён",
	'logger_do_8' => "Номер квинанции определён",
	'logger_do_9' => "Данные платежа проверены и приняты",
	'logger_do_9.1' => "Платеж запрещен файлом-обработчиков",
	'logger_do_10' => "Платеж зачислен",
	'logger_do_11' => "Ошибка зачисления платежа",
	'logger_do_12' => "Файл adm.settings.php платежной системы не подключен",
	'logger_do_14' => "Все операции завершены",
	'logger_do_14' => "Оповещение пользователя",

	'logger_do_15' => "Квитанци с указанным id не найдена",
	'logger_do_16' => "Квитанци с указанным id уже оплачена",
	'logger_do_17' => "Платежная система не соответствует указанной в квитанции",
	'logger_do_18' => "Загружен обработчик для %s",

	'statistics_dashboard_all' => "Всего на счетах",
	'statistics_dashboard_today' => "пополнено сегодня",
	'statistics_dashboard_refund' => "Выведено из системы",
	'statistics_dashboard_comission' => "комиссия составила",
	'statistics_dashboard_to_refund' => "заявлено к выводу",
	'statistics_dashboard_pay' => "Пополнено через платежные системы",
	'statistics_dashboard_to_pay' => "ожидается к пополнению",
	'statistics_dashboard_transfer' => "Переведено между пользователями",
	'statistics_dashboard_search_user' => "Поиск по пользователям",
	'statistics_dashboard_all_refund' => "Все запросы вывода средств",
	'statistics_dashboard_invoices' => "Обработка квитанций",
	'statistics_dashboard_search_reansfer' => "Поиск переводов",
	'statistics_users_balance' => "текущий баланс",
	'statistics_users_refund' => "к выводу",
	'statistics_graph_get' => "Привлечено средств",
	'statistics_graph_plus' => "Доход пользователей",
	'statistics_graph_minus' => "Расход пользователей",
	'statistics_dashboard_yesterday_up' => "Сегодня пополнено на %s %s vs вчера: %s %s",

	'catalog_get_update' => "Получить обновление",

	'users_group_stats' => array(
		"<th>Группа</th>",
		"<th>Пользователей</th>",
		"<th>Минимальный баланс</th>",
		"<th>Максимальный</th>",
		"<th>Всего на счетах</th>"
	),

    'payment_convert_text' => "Конвертация:",
    'payment_convert_text_desc' => "Стоимость 1 еденицы валюты сайта по отношению к валюте платежной системы.<br>Укажите <b>1</b> - если 1 к 1.",
    'payment_convert_in' => "Интеграция",

	'payment_convert' => array(
		"<th width='50%'><center>Сайт</center></th>",
		"<th>Платежная система</th>"
	),

	# settings, url
	#
	'url' => "Изменить URL",
	'url_help' => "Укажите начальное значение части url - <b>start</b> и конечное - <b>end</b>. Например: для замены /billing.html/<u>log</u>/ на /billing.html/<u>history</u>/, укажите <b>log</b> - <b>history</b> ",

	# 0.5.6
	#
	'main_settings_1' => "Главные настройки",
	'main_settings_2' => "Расширенные настройки",
	'main_settings_3' => "Безопасность",
	'main_now' => "Сегодня в ",
	'main_rnow' => "Вчера в ",
	'main_next' => "Продолжить",
	'main_re' => "Повторить",
	'main_back' => "Вернуться назад",
	'main_report' => "Сообщить об ошибке",
	'main_log' => "Режим тестирования",
	'main_report_close' => "Больше не показывать",
	'main_error_controller_file' => "Файл плагина не найден",
	'main_error_controller' => "Класс плагина не найден",
	'main_error_method' => "Функция плагина не найден",
	'main_error_upgrade_file' => "Отсутствует файл с обновлениями",

	# main
	#
	'main_settings' => "Настройки",
	'main_settings_desc' => "Общие настройки модуля",
	'main_mail' => "Настройки уведомлений",
	'main_mail_desc' => "Редактирование шаблонов email и ЛС оповещения",
	'main_paysys' => "Платежные системы",
	'main_plugins' => "Плагины",
	'main_news' => "За сегодня",
	'main_users' => "пользователь,пользователя,пользователей",
	'main_users_plus' => "пополнили свой баланс суммарно на",
	'main_users_refund' => "запросили возврат средств суммарно на",

	# catalog
	#
	'catalog_er' => "Каталог недоступен",
	'catalog_er_title' => "URL адрес каталога не задан, либо недоступен",
	'catalog_er2_title' => "Ваш сервер не позволяет использовать Curl методы",
	'catalog_version_yes' => "Вы используете актуальную версия модуля - ",
	'catalog_verplug_ok' => "Установлено",
	'catalog_verplug_update' => "Доступно обновление v.",
	'catalog_version_no' => "<b>Внимание!</b> Вы используете устаревшую версию модуля. Актуальная версия - ",
	'catalog_tab1' => "Платежные системы",
	'catalog_tab2' => "Плагины",
	'catalog_free' => "Бесплатно",
	'catalog_doc' => "Документация",
	'catalog_forum' => "Форум",
	'catalog_autor' => "Автор",

	'settings_status' => "Включить:",
	'settings_status_desc' => "Включить личный кабинет для всех пользователей",
	'settings_page' => "Страница личного кабинета:",
	'settings_page_desc' => "Укажите название <a href=\"?mod=static\">существующей статической страницы</a> с личным кабинетом",
	'settings_currency' => "Наименование валюты:",
	'settings_currency_desc' => "Отображается рядом с суммой. Формат настройки: рубль,рубля,рублей",
	'settings_summ' => "Сумма оплаты по умолчанию",
	'settings_summ_desc' => "Используется на странице пополнения баланса",
	'settings_redirect' => "Редирект на сайт платежной системы:",
	'settings_redirect_desc' => "После создания квитанции, пользователь будет автоматически перенаправлен на сайт платежной системы для оплаты счёта",
	'settings_paging' => "Результатов на страницу:",
	'settings_paging_desc' => "Количество записей на странице",
	'settings_admin' => "Логин администратора:",
	'settings_admin_desc' => "Будет использоваться как отправитель в служебных сообщениях пользователям",
	'settings_key' => "Ключ доступа платежной системы:",
	'settings_key_desc' => "Введите произвольный нобор букв и цифр, ключ используется для формировании result url.<br />Никому не сообщайте этот ключ",
	'settings_test' => "Режим тестирования:",
	'settings_test_desc' => "Включить <a href='?mod=billing&m=log'>логирование входящих запросов</a>",
	'settings_field' => "Поле в БД с балансом пользователя:",
	'settings_field_desc' => "Название столбца в таблице " . PREFIX . "_users,  в которой хранится баланс пользователя",
	'settings_start' => "Главная страница ЛК:",
	'settings_start_desc' => "Главная страница личного кабинета: плагин/метод/параметр/значение<br />Например: log/main/page/1",
	'settings_format' => "Сумма:",
	'settings_format_desc' => "В каком виде отображать суммы",

    'settings_hide_menu' => "Скрыть пункты меню:",
    'settings_hide_menu_desc' => "Скрыть в боковом меню остальные пункты cms, за исключением ссылки на модуль",

	'settings_catalog' => "Каталог плагинов:",
	'settings_catalog_desc' => "URL сервера каталога плагинов",
	'settings_informers' => "Информеры:",
	'settings_informers_desc' => "Использовать следующие информеры в админ.панели",
	'settings_pdf_outputs' => array(
		'0'=> "Отключить",
		'1'=>"Вывести на экран",
		'2'=>"Вывести на экран и загрузить"
	),
	'mail_table' => array(
		"<td>Действие пользователя</td>",
		"<td>Сообщение в личную почту</td>",
		"<td>Сообщение на email</td>"
	),
	'mail_pay_ok' => "<h8 class=\"media-heading text-semibold\">Квитанция оплачена</h8><p>Пользователь успешно завершил оплату на сайте платежной системы</p>",
	'mail_pay_new' => "<h8 class=\"media-heading text-semibold\">Новая квитанция</h8><p>Пользователь начал процесс пополнения баланса</p>",
	'mail_balance' => "<h8 class=\"media-heading text-semibold\">Баланс изменён</h8><p>Баланс пользователя на сайте был изменён (не платежной системой)</p>",

	'ok' => "Действие выполнено",
	'info' => "Системное сообщение",
	'error' => "Ошибка",
	'save' => "Сохранить",
	'save_settings' => "Настройки успешно сохранены!",
	'save_mail' => "Шаблоны оповещения сохранены!",
	'mail' => "Шаблоны оповещения сохранены!",
	'status' => "Статус",
	'remove' => "Удалить",
	'info_login' => "- информация по пользователю",
	'act' => "Выполнить",
	'apply' => "Применить",

	# catalog 0.5.6
	#
	'catalog_title' => "Каталог плагинов",
	'catalog_desc' => "Каталог плагинов и платежных систем, доступных для загрузки",

	# payments
	#
	'paysys_on' => "Включить:",
    'paysys_status_desc' => "Включить оплату через платежную систему",
	'paysys_save_ok' => "Настройки платежной системы успешно сохранены!",
	'paysys_fail_error' => "Файл платежной системы не найден!",
	'paysys_url' => "URL обработчика платежей:",
	'paysys_url_desc' => "На этот url приходят запросы с сайта платежной системы, при изменении статуса оплаты",
	'paysys_name' => "Название:",
	'paysys_name_desc' => "Название платежной системы",
	'paysys_convert' => "Цена 1 ед. данной валюты:",
	'paysys_convert_desc' => "Относительно валюты на сайте",
	'paysys_minimum' => "Минимальная сумма платежа:",
	'paysys_minimum_desc' => "Минимальная сумма платежа в валюте сайта",
	'paysys_max' => "Максимальная сумма платежа:",
	'paysys_max_desc' => "Максимальная сумма платежа в валюте сайта",
	'paysys_currency' => "Название валюты:",
	'paysys_currency_desc' => "Название валюты платежной системы",
	'paysys_format' => "Формат данных:",
	'paysys_format_desc' => "Например: 0.00",
	'paysys_icon' => "Иконка:",
	'paysys_icon_desc' => "Путь до иконки платежной системы. Например: ",
	'paysys_about' => "Краткое описание:",
	'paysys_about_desc' => "Краткое описание платежной системы",
	'pay_title' => "Пополнение баланса",
	'pay_status_on' => "Работает",
	'pay_status_off' => "Отключено",
	'pay_name' => "Система оплаты",

	# Invoice 0.7.1
	#
	'invoice_key' => "Квитанция: ",
	'invoice_payer_requisites' => "Реквизиты плательщика:",
	'invoice_payer_requisites_desc' => "Вы можете использовать символ <b>%</b> - вместо части запроса<br /><br />Например: <b>WMID3320%</b>",
	'invoice_payer_info' => "Дополнительная информация:",

	# Invoice 0.5.6
	#
	'invoice_info' => "Информация",
	'invoice_new' => "Привлечено средств",
	'invoice_payok' => "Оплачено",

	# unvoice 0.5.5
	#
	'invoice_title' => "Поступление средств",
	'invoice_desc' => "Просмотреть все квитанции пользователей",
	'invoice_ok' => "Квитанции обработаны",
	'invoice_all' => "Все",

	'invoice_summa' => "Сумма к оплате:",
	'invoice_summa_desc' => "Фильтр поиска по значению &laquo;Оплачено&raquo;<br>Вы можете использовать один из символов сравнения: > <",
	'invoice_ps' => "Платежная система:",
	'invoice_ps_desc' => "При необходимости выберите систему оплаты",
	'invoice_status' => "Состояние:",
	'invoice_status_desc' => "Выберите интересуюший вас статус квитанции",

	#'invoice_status_1' => "Любой",
	#'invoice_status_2' => "Оплачено",
	#'invoice_status_3' => "Не оплачено",

	'invoice_status_arr' => array(
		'' => "Все операции",
		'ok' => "Оплачено",
		'no' => "Не оплачено"
	),

	'invoice_str_payok' => "К оплате",
	'invoice_str_get' => "К зачислению",
	'invoice_str_ps' => "Система оплаты",
	'invoice_str_status' => "Статус",

	'invoice_edit_1' => "Изменить статус на &laquo;Оплачено&raquo;",
	'invoice_edit_2' => "Изменить статус на &laquo;Не оплачено&raquo;",
	'invoice_edit_3' => "Изменить статус на &laquo;Оплачено&raquo; и зачислить средства",

	/* Search 0.5.5 */
	'search_pcode' => "Код плагина:",
	'search_pcode_desc' => "Если не нужно учитывать - оставьте поле пустым",
	'search_pid' => "ID операции плагина:",

	'search_tsd' => array(
		'' => "Все операции",
		'plus' => "Доход",
		'minus' => "Расход",
	),

	'search_type_operation' => array(
		'' => "Тип операции",
		'>' => "больше",
		'<' => "меньше",
		'=' => "равно",
		'!=' => "не равно"
	),

	'search_summa_desc' => "Фильтр поиска по значению суммы",
	'search_user' => "Пользователь:",
	'search_user_desc' => "Введите логин пользователя или его часть.<br />Вы можете использовать символ <b>%</b> - вместо части запроса<br /><br />Например: <b>mr_%</b> - пользователи, чей логин начинается с mr_",
	'search_comm' => "Комментарий:",
	'search_comm_desc' => "Вы можете использовать символ <b>%</b> - вместо части запроса",
	'search_date' => "Дата и время:",
	'search_date_desc' => "Фильтр поиска по дате",

	# history plugin
	#
	'transactions_title' => "История движения средств",
	'history_desc' => "Просмотреть историю движения средств",
	'history_for' => " для ",
	'history_code' => "ID плагина",
	'history_summa' => "Сумма",
	'history_date' => "Дата и время",
	'history_user' => "Пользователь",
	'history_user_null' => "<i>Гость</i>",
	'history_balance' => "Остаток на балансе",
	'history_comment' => "Комментарий",
	'history_paging' => "Страницы",
	'history_no' => "<div style='padding: 10px'>Записей не найдено</div>",
	'history_search' => "Поиск",
	'history_search_btn' => "Найти",
	'history_search_btn_null' => "Сбросить",
	'export_btn' => "Экспорт",

	# refund plugin
	#
	'refund_title' => "Возврат средств",
	'refund_back' => "Запрос на вывод средств #{remove_id} отменён администратором",
	'refund_act' => "Запросы вывода средств обработаны",
	'refund_summa' => "Сумма к выводу",
	'refund_commision_list' => "+ Комиссия учтена",
	'refund_requisites' => "Реквизиты",
	'refund_change' => "Изменить статус и вернуть средства:",
	'refund_change_status' => "Изменить статус:",
	'refund_wait' => "Ожидается",
	'refund_act_ok' => "Выполнено",
	'refund_act_no' => "Отменить",
	'refund_status_desc' => "Включить плагин для всех пользователей",
	'refund_name_desc' => "Название плагина для меню пользователя",
	'refund_minimum' => "Минимальная сумма для вывода:",
	'refund_minimum_desc' => "В текущей валюте сайта",
	'refund_commision' => "Комиссия сайта:",
	'refund_commision_desc' => "Данный процент от суммы вывода будет удерживаться сайтом в качестве комиссии",
	'refund_field' => "Поле с реквизитами:",
	'refund_field_desc' => "Дополнительное <a href=\"?mod=userfields&xfieldsaction=configure\">поле профиля</a> пользователя с реквизитами для вывода",
	'refund_email' => "Email для уведомления:",
	'refund_email_desc' => "Укажите email на который будет отправлено уведомление о новом запросе на вывод средств",
	'refund_email_title' => "Новый запрос вывода средств на сайте",
	'refund_email_msg' => "Ваш пользователь создал запрос на вывод средств<br /><br />Больше информации в админ. панели: ",
	'refund_status_desc' => "Включить плагин для всех пользователей",

	'refund_se_summa' => "Сумма к выводу:",
	'refund_se_summa_desc' => "Фиьтр по сумме вывода<br>Вы можете использовать один из символов сравнения: > <",
	'refund_se_req' => "Реквизиты",
	'refund_se_req_desc' => "Вы можете использовать символ <b>%</b> - для составления маски запроса<br />Например: <b>R%</b> - запросы вывода на R-кошелёк (WebMoney)",
	'refund_se_status' => "Статус запроса:",
	'refund_se_status_desc' => "Выберите интересуюший вас статус запроса",

	'refund_search' => array(
		'' => "Любой",
		'wait' => "Ожидается",
		'ok' => "Выполнен",
	),

	'refund_informer_title' => "Новых запросов",
	'refund_informer' => "Ожидают вывода",

	# transfer plugin
	#
	'transfer_title' => "Перевод средств",
	'transfer_minimum' => "Минимальная сумма для перевода:",
	'transfer_minimum_desc' => "В текущей валюте сайта",

	# theme
	#
	'title' => "Баланс пользователя",
	'title_short' => "Баланс",
	'main' => "Панель управления",
	'desc' => "DLE-Billing",
	'more' => "Показать больше",
	'go_plugin' => "Перейти к плагину ",
	'no_plugin' => "Плагины не установлены!",
	'on' => "Вкл",
	'off' => "Выкл",
	'go_paysys' => "Перейти к настройкам платежной системы ",
	'no_paysys' => "Платежные системы не установлены!",
	'user_profily' => "Профиль на сайте",
	'user_history' => "История баланса",
	'user_refund' => "Запросы вывода",
	'user_balance' => "Изменить баланс",
	'user_edit_ap' => "Редактировать",
	'user_invoice' => "Квитанции",
	'user_stats' => "Общая статистика",

	'user_se_balance' => "Баланс:",
	'user_se_balance_desc' => "Используйте один из символов сравнения: > < =",

	# Users
	#
	'users_title' => "Пользователи",
	'users_groups_title' => "Группы пользователей",
	'users_title_full' => "Результаты поиска",
	'users_desc' => "Поиск пользователей, изменение баланса",
	'users_search' => "Найти пользователя",
	'users_label' => "Данные для поиска:",
	'users_label_desc' => "Введите логин ( email ) пользователя или его часть",
	'users_btn' => "Найти",
	'users_plus' => "Пополнить баланс",
	'users_login' => "Пользователи:",
	'users_login_desc' => "Укажите через запятую логины пользователей",
	'users_summa' => "Сумма:",
	'users_summa_desc' => "Введите сумму",
	'users_group' => "Изменить баланс группе:",
	'users_group_desc' => "Баланс будет изменен каждому пользователю выбранной группы",
	'users_stats_title' => "Админ. действия",
	'users_tanle_login' => "Пользователь",
	'users_tanle_email' => "Email",
	'users_tanle_group' => "Группа",
	'users_tanle_datereg' => "Дата регистрации",
	'users_tanle_balance' => "Баланс",
	'users_comm' => "Комментарий",
	'users_comm_desc' => "Введите описание платежа",
	'users_er_user' => "Не указан логин пользователя",
	'users_er_group' => "Не указана группа",
	'users_er_summa' => "Не указана сумма",
	'users_er_comm' => "Не указан комментарий",
	'users_ok_group' => "Баланс группы изменён",
	'users_ok' => "Баланс выбранных пользователей изменен",
	'users_ok_reserv' => "Баланс пользователя понижен",
	'users_minus' => "Понизить баланс",
	'users_edit' => "Изменить баланс",
	'users_edit_user' => "Изменить баланс пользователю",
	'users_edit_group' => "Изменить баланс группе",
	'users_edit_do' => "Действие:",
	'users_edit_do_desc' => "Выберите из придложенного списка",

	# Statistics
	#
	'statistics_title' => "Статистика",
	'statistics_title_desc' => "Статистика пополнения и расхода баланса пользователей",
	'stats_error_remove' => "Ошибка hash строки",
	'stats_ok_remove' => "Информация о платеже удалена",
	'stats_tr_balance' => "Пополнение баланса",
	'stats_tr_payhide' => "Оплата файла",
	'stats_remove' => "Удалить счёт",
	'stats_ome' => "раз.",

	# 0.5.6
	#
	'statistics_error_load' => "Ошибка загрузки файла статистики",
	'statistics_show' => "Показать",
	'statistics_info' => "Справка",
	'statistics_info_text' => "text",
	'statistics_interval' => "Указать: ",
	'statistics_info1' => "За указанный промежуток времени:",
	'statistics_info2' => "доход пользователей составил",
	'statistics_info3' => "расход ",
	'statistics_diagram_1' => "График расходов пользователей",
	'statistics_null' => "<p style='text-align: center; padding: 10px'>В указанные промежутки времени платежи не совершались.</p>",
	'statistics_minus' => "Расход",
	'statistics_plus' => "Доход",
	'statistics_pay' => "Пополнение через биллинг",
	'statistics_admin' => "Действие администратора",
	'statistics_d_end' => "Итого",
	'statistics_d_per' => " раз.",
	'statistics_d_title1' => "Расходы пользователей",
	'statistics_d_subtitle' => "За указанные промежуток времени — %s %s",
	'statistics_d_title2' => "Доходы пользователей",
	'statistics_users_error' => "Пользователь не найден",
	'statistics_users_21' => "До ",
	'statistics_users_9' => "Отправить сообщение на сайте",
	'statistics_users_10' => "Отправить email",

	# 0.5.5
	#
    'statistics_0' => "<i class='fa fa-balance-scale'></i> Динамика",
    'statistics_0_title' => "Динамика за день",
    'statistics_1' => "Общая статистика",
    'statistics_2' => "Пополнение баланса",
    'statistics_2_tab_2' => "Объем привлеченных средств",
    'statistics_2_title' => "<i class='fa fa-money'></i> Платежные системы",
    'statistics_3' => "Объем расходов и доходов пользователей",
    'statistics_3_user' => "Объем расходов и доходов",
    'statistics_3_tab2' => "Плагины",
    'statistics_3_title' => "<i class='fa fa-cogs'></i> Плагины",
    'statistics_4' => "Статистика пользователя",
    'statistics_4_title' => "<i class='fa fa-group'></i> Пользователи",
    'statistics_5_title' => "Сбросить статистику",
    'statistics_5' => "<i class='fa fa-trash'></i> Сбросить статистику",
    'statistics_6_title' => "Вернуться",
    'statistics_6' => "Главное меню",
    'statistics_7' => "<i class='fa fa-area-chart'></i> Сводка",

	'statistics_new_1' => "Статистика движения средств",
	'statistics_new_1_graf' => "Динамика доходов и расходов пользователей",

	'statistics_clean_1_ok' => "Очистка данных выполнена",
	'statistics_clean_info' => "<p><b>Внимание!</b></p>
									<p>Данные из <b>истории баланса</b> используются при составлении статистики.</p>
									<p>Перед очисткой данных настоятельно рекомендуем <a href=\"?mod=dboption\" style=\"border-bottom: 1px solid\">сделать резервную копию</a> базы данных.</p>",
	'statistics_clean_2' => "Отметить все",
	'statistics_clean_3' => "Очистить историю баланса:",
	'statistics_clean_3d' => "Очистить историю баланса для следующий плагинов",
	'statistics_clean_4' => "Удалить квитанции на оплату:",
	'statistics_clean_4d' => "Удалить квитанции на оплату со следующими статусами",

	'statistics_clean_invoice' => array(
		'' => "",
		'all' => "Все",
		'ok' => "Оплачено",
		'no' => "Не оплачено"
	),

	'statistics_clean_refund' => array(
		'' => "",
		'all' => "Все",
		'all' => "Выполнено",
		'ok' => "Ожидается"
	),

	'statistics_clean_balance' => array(
		'' => "",
		'1' => "Да",
	),

	'statistics_clean_5' => "Удалить запросы возврата средств:",
	'statistics_clean_5d' => "Удалить запросы возврата средств со следующими статусами",
	#'statistics_clean_5_s1' => "Выполнено",
	#'statistics_clean_5_s2' => "Ожидается",
	'statistics_clean_6' => "Обнулить баланс всех пользователей:",
	'statistics_clean_6d' => "Обнулить баланс всех пользователей",
	#'statistics_clean_6d_yep' => "Да",
	'statistics_billings_invoices_0' => "из",
	'statistics_billings_invoices_1' => "квитанций",
	'statistics_billings_invoices_summ' => "суммарно",

	'sectors' => array(
		'D' => "По дням",
		'M' => "По месяцам",
		'Y' => "По годам"
	),

	'months' => array(
		'1'		=>	"янв",
		'2'		=>	"фев",
		'3'		=>	"мар",
		'4'		=>	"апр",
		'5'		=>	"май",
		'6'		=>	"июн",
		'7'		=>	"июл",
		'8'		=>	"авг",
		'9'		=>	"сен",
		'10'	=>	"окт",
		'11'	=>	"ноя",
		'12'	=>	"дек",
	),

	'months_full' => array(
		'1'		=>	"Январь",
		'2'		=>	"Февраль",
		'3'		=>	"Март",
		'4'		=>	"Апрель",
		'5'		=>	"Май",
		'6'		=>	"Июнь",
		'7'		=>	"Июль",
		'8'		=>	"Август",
		'9'		=>	"Сентябрь",
		'10'	=>	"Октябрь",
		'11'	=>	"Ноябрь",
		'12'	=>	"Декабрь",
	),

	# upgrade
	#
	'upgrade_title' => "Обновление модуля до v.",
	'upgrade_wsql' => "Внимание, будут выполнены следующие SQL запросы:",
	'upgrade_theme' => "<p>
                            <b>Внимание, в этом обновлении были обновлены шаблоны модуля:</b></p> 
                            <p>Текущий шаблон модуля (%s) будет сохранен в %s, на его место будет установлен новый шаблон из обновления.</p>",
	'upgrade_ok' => "Модуль обновлен до версии ",

	# install
	#
	'currency' => "рубль,рубля,рублей",
	'cabinet' => "Личный кабинет",

    'install_pre_ok' => "Последний шаг",
    'install_pre_ok_btn' => "Готово",
    'install_pre_ok_text' => '<div style="text-align: left">
							Осталось подключить необходимые js скрипты в шаблон сайта - <a href="?mod=templates" target="_blank">откройте</a> файл <b>/templates/' . $config['skin'] . '/main.tpl</b> и перед <b>&lt;/head></b> добавьте строки:
<p><pre>&lt;!-- основной скрипт dle-billing -->
{include file="{THEME}/billing/js/scripts.js"}
&lt;!-- dle-billing: push уведомления -->
{include file="engine/modules/billing/widgets/push.php"}
</pre></p>
						</div>',

    'install_ok' => "Модуль установлен",
	'install_ok_text' => "<div style='text-align: left'>
							<font color='green'><b>Модуль DLE-Billing установлен</b></font>
							<br /><br />Теперь вам доступна <a href='?mod=billing'><u>панель управления</u></a> и <a href='/billing.html'><u>личный кабинет</u></a>   
						</div>",

	'install_plugin' => "Плагин установлен",
	'install_plugin_desc' => "Первичные настройки плагина установлены. Вы можете вернуться к панеле управления плагином.",
	'install_bad' => "Установка не завершена",
	'install_next' => "Вы можете вернуться в <b><a href=\"\">гланое меню</a></b> модуля",
	'install_error' => "<p>Файл <b>.htaccess</b> закрыт для записи. Откройте этот файл и в самом конце добавьте:</p>",
	'install_error_config' => "<p>Не удалось сохранить настройки модуля. Создайте и сохраните файл <b>/engine/data/billing/config.php</b> с содержимым:</p>",
	'install_error_templates' => "<p>Обнаружены файлы шаблонов модуля в каталоге %s. <br>Переименовать каталог и продолжить установку?</p>",
    'install_error_templates_error' => "<p>Не удалось переименовать каталог с шаблоном %s. <br>Переименуйте каталог в ручную и продолжите установку</p>",
    'install_error_templates_error2' => "<p>Не удалось установить шаблоны модуля. <br>Скопируйте содержимое каталога /www/engine/modules/billing/install/_template_/ в %s</p>",
    'install' => "Установка модуля",
	'install_button' => "Я согласен",
	'install_button2' => "Установить модуль",
    'install_need' => [
        'title' => 'Системные требования',
        'php' => 'Версия PHP:',
        'php_desc' => 'для работы модуля необходим php версии 8 и выше',
        'file' => 'Файл .htaccess доступен для записи:',
        'file_desc' => 'Для установки чпу модуля потребуется внести правки в файл .htaccess в корне сайта',
        'file_close' => '<span style="color: orange">Файл недоступен для записи</span>',
        'catalog' => 'Каталог engine/data доступен для записи:',
        'catalog_desc' => 'В этом каталоге будут созданы файлы конфигурации модуля и плагинов',
        'catalog_close' => '<span style="color: red">Каталог недоступен для записи</span>',
        'yes' => '<span style="color: green">Да</span>',
        'update' => "Обновить",
    ],
    'install_okbtn' => "Перейти к панели управления",
	'license' => "<p><a href=\"https://opensource.org/license/mit/\">The MIT License (MIT)</a></p>
					<p>Copyright (c) 2024 evgeny.tc@gmail.com (https://dle-billing.ru/)</p>
					<p>Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации (в дальнейшем именуемыми «Программное Обеспечение»), безвозмездно использовать Программное Обеспечение без ограничений, включая неограниченное право на использование, копирование, изменение, добавление, публикацию, распространение, сублицензирование и/или продажу копий Программного Обеспечения, также как и лицам, которым предоставляется данное Программное Обеспечение, при соблюдении следующих условий:</p>
					<p>Указанное выше уведомление об авторском праве и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.</p>
					<p>ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ, ВКЛЮЧАЯ, НО НЕ ОГРАНИЧИВАЯСЬ ГАРАНТИЯМИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И ОТСУТСТВИЯ НАРУШЕНИЙ ПРАВ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО ИСКАМ О ВОЗМЕЩЕНИИ УЩЕРБА, УБЫТКОВ ИЛИ ДРУГИХ ТРЕБОВАНИЙ ПО ДЕЙСТВУЮЩИМ КОНТРАКТАМ, ДЕЛИКТАМ ИЛИ ИНОМУ, ВОЗНИКШИМ ИЗ, ИМЕЮЩИМ ПРИЧИНОЙ ИЛИ СВЯЗАННЫМ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ ИЛИ ИСПОЛЬЗОВАНИЕМ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫМИ ДЕЙСТВИЯМИ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.</p>",
);
