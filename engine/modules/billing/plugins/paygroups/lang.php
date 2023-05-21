<?php

$plugin_lang = array
(
	'error_tpl' => "Неудалось загрузить шаблон плагина",
	'error_group' => "Группа не найдена",
	'error_login' => "Войдите на сайт как пользователь",
	'error_off' => "Оплата временно недоступна",

	'group_denied' => "Для вашей группы невозможен переход в выбранную группу",
	'group_was_paid' => "Вы уже находитесь в данной группе",

	'settings_title' => "Настройки плагина",
	'log' => "Оплата перехода в группу %s",
	'time' => " на %s дн. до %s",
	'fulltime' => " бессрочно",

	'title' => "Переход в группу",
	'desc' => "Управление платными переходами пользователей между группами",
	'a_update' => "Настройки групп обновлены",
	'a_time_all' => "Навсегда",
	'a_time' => "На время",
	'a_status' => "Включить переход:",
	'a_status_desc' => "Разрешить переход в эту группу",
	'a_start' => "Переход из групп:",
	'a_start_desc' => "Разрешить переход из следующих группу",
	'a_type' => "Вариант перехода:",
	'a_type_desc' => "Вариант перехода в группу",
	'a_type_info' =>  '<i class="help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right position-left" data-rel="popover" data-trigger="hover" data-placement="auto right" data-content="Временное размещение в группе отключено" data-original-title="" title=""></i> [ <a href="?mod=usergroup&action=edit&id=%s" target="_blank">включить</a> ]',
	'a_price' => "Цена перехода:",
	'a_price_desc' => "Укажите цену перехода в группу, формат:
													<br /><br />Для перехода <b>&laquo;Навсегда&raquo;</b> : цена перехода,
													<br />Для перехода <b>&laquo;На время&raquo;</b> : количество дней|название для меню|цена перехода (построчно)",
	'a_link' => "Ссылка для оплаты:",
	'a_link_desc' => "Ссылка вызова окна оплаты перехода в данную группу",
	'a_go' => "Перейти в группу ",
	'a_btn_update' => "Обновить",
	'a_title' => "Переход в группу",
	'a_stop' => "Закрыть группы:",
	'a_stop_desc' => "Группы, закрытые для перехода и настроек",

    'handler' => [
      'group' => 'Переход в группу:',
      'days' => 'На срок, дней:',
      'time_null' => 'бессрочно',
        'error' => [
          'group_id' => 'Ошибка идентификации группы',
        ],
    ],

	'html_pay_wait' => '<div id="paygrouptpl" title="Оплата" style="display:none">Процесс оплаты...<a href="%s" target="_blank">перейти к оплате</a>.</div>'
);
