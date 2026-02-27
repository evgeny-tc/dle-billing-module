<?PHP

return array
(
    'to_history' => "Действие реферала <a href=/user/%s>%s</a>: %s",

	'added' => "+ Добавить",
	'settings' => "Настройки",
	'pay_desc' => "Регистрация по реферальной ссылке пользователя <a href=/user/%s>%s</a>",
	'pay2_desc' => "Регистрация по реферальной ссылке <a href=/user/%s target='_blank'>%s</a>",

    'from' => "Приглашенный пользователь",
	'to' => "Кто пригласил",
	'bonus_add' => "Вознаграждения добавлены",
	'bonus_remove' => "Вознаграждение удалено",
	'bonus_save' => "Вознаграждение сохранено",
	'users' => "Приглашения",
	'table_header' => "<th width='5%'></th>
						<th>Действие <i class='help-button visible-lg-inline-block text-primary-600 fa fa-question-circle position-right position-left' data-rel='popover' data-trigger='hover' data-placement='auto right' data-content='Символьный код плагина' data-original-title='' title=''></i></th>
						<th>Описание</th>
						<th>Операция</th>
						<th>Сумма операции</th>
						<th width='20%'>Вознаграждение</th>
						<th width='10%'></th>",
	'plus' => "Поступление средств",
	'minus' => "Расход средств",
	'partner_bonus' => "Партнерские отчисления",

	'setting_1' => "Название пункта меню:",
	'setting_1_d' => "Название ссылки в меню личного кабинета пользователя",
	'setting_2' => "Редирект приглашенных пользователей:",
	'setting_2_d' => "Ссылка на которую будут перенаправляться приглашенные пользователи",
	'setting_3' => "Бонус за регистрацию партнеру:",
	'setting_3_d' => "Размер вознаграждения за регистрацию по партнерской ссылке (необязательно)",
	'setting_4' => "Бонус за регистрацию новому пользователю:",
	'setting_4_d' => "Размер вознаграждения за регистрацию по партнерской ссылке (необязательно)",
	'edit' => "Редактировать",
	'remove' => "Удалить",
	'null' => "<tr><td colspan='7'><div style='margin: 10px'>Сохраненных вознаграждений нет</div></td></tr>",
    'install' => '<div style="text-align: left">
                        Установка правила обработки адресов не выполнена, создайте <a href="?mod=friendlyurl" target="_blank">правило</a> вручную:
                        <pre>custom.custom.billing.referrals
/partner/{user_id}.html
/index.php?do=static&page=billing&seourl=billing&route=referrals/redirect&p={user_id}</pre>
                    </div>'
);