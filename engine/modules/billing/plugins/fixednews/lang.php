<?PHP

return [
	'settings_title' => "Настройки",
	'title' => "Платная фиксация новостей",
	'dashboard_desc' => "Настройка цен фиксации новостей в различных категориях",
	'desc' => "Оплата фиксации статьи <a href='/index.php?newsid=%s' target='_blank'>%s</a> на %s дн.",
	'desc_up' => "Оплата поднятия статьи <a href='/index.php?newsid=%s' target='_blank'>%s</a>",
	'desc_main' => "Оплата публикации статьи <a href='/index.php?newsid=%s' target='_blank'>%s</a> на главной странице сайта",
	'stop' => "Закрыть группы:",
	'stop_desc' => "Группы для которых закрыты настройки",
	'stop_cat' => "Закрыть категории:",
	'stop_cat_desc' => "Категории для которых закрыты настройки",

	'fix' => "Фиксация статьи",
	'up' => "Поднятие статьи",
	'post_main' => "Публикация на главной",

	'link' => "<b>Ссылка на окно оплаты:</b>",
	'link_name_1' => "<pre><code>&lt;a href='#' onClick='BillingNews.Form( 0, {news-id} ); return false'&gt;Оплатить фиксацию статьи&lt;/a&gt;</code></pre>",
	'link_name_2' => "<pre><code>&lt;a href='#' onClick='BillingNews.Form( 1, {news-id} ); return false'&gt;Оплатить поднятие статьи&lt;/a&gt;</code></pre>",
	'link_name_3' => "<pre><code>&lt;a href='#' onClick='BillingNews.Form( 2, {news-id} ); return false'&gt;Оплатить публикацию статьи на главной странице&lt;/a&gt;</code></pre>",

	'link_help' => "<b>Формат: / пример</b>",
	'link_help_instr' => "<table width='100%'>
							<tr>
								<td width='45%'><pre><code>{количество дней фиксации}|{название}|{цена}<br>{количество дней фиксации}|{название}|{цена}</code></pre></td>
								<td width='5%'></td>
								<td><pre><code>1|1 день|100<br>7|1 неделя|700<br>30|1 месяц|3000</code></pre></td>
							</tr>
							</table>",

	'error' => [
		'login' => 'Войдите на сайт как пользователь',
		'off' => 'Оплата временно недоступна',
		'post_not_found' => 'Статья не найдена',
        'price' => 'Оплата за указанный период недоступна'
	],

    'handler' => [
      'post' => 'Статья:',
      'fixed_days' => 'Зафиксировать, дней:',
      'fixed' => [
          'story' => 'Фиксация %s на %s дн.'
      ],
      'up' => [
          'story' => 'Поднятие %s'
      ],
      'main' => [
          'story' => 'Публикация на главной %s'
      ]
    ],

    'html_pay_wait' => '<div id="#modal_id#" title="Оплата" style="display:none">Процесс оплаты...<a href="%s" target="_blank">перейти к оплате</a>.</div>'
];
