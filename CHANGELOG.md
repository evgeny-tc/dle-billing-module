# Change Log

## [0.9.5] - 9.01.2024
### Добавлено
- Обновлен личный кабинет пользователя
- Новый интерфейс процесса оплаты услуг
### Изменено
- Оптимизирован код модуля
### Исправлено
- Другие исправления и улучшения

## [0.9.4] - 15.10.2023
### Добавлено
### Изменено
### Исправлено
- Исправлена ошибка в интеграции с ЮMoney
- Исправлена ошибка в ссылках на профили пользователей
- Другие исправления и улучшения

## [0.9.3] - 23.09.2023
### Добавлено
- Добавлена интеграция с платежной системой BetaTransfer.io
### Изменено
- Обновлена страница статистики
### Исправлено
- Исправлены ошибки в отображении админпанели в темной версии

## [0.9.2] - 30.08.2023
### Добавлено
- Добавлены купоны, дающие скидку на оплату услуг
- Плагин Возврат средств - добавлен статус заявки "Отменен"
### Изменено
### Исправлено
- Исправлена ошибка в плагине оплаты скрытого текста при использовании параметра "Описание платежа"
- Другие исправления и улучшения

## [0.9] - 09.08.2023
### Добавлено
- Новая панель управления плагинами
- Добавлены настройки: Главная страница ПУ и Время жизни квитанции
- Добавлен плагин - Формы
### Изменено
### Исправлено
- Исправлена ошибка в платежной системе Free-kassa
- Другие исправления и улучшения

## [0.8.7] - 21.06.2023
### Добавлено
- Оплата скрытого текста - добавлен вывод описания платежа в список оплаченных доступов (теги [pay_desc] {pay_desc} [/pay_desc], [not_pay_desc] ... [/not_pay_desc])
- Оплата скрытого текста: добавлена возможность динамически указывать описание платежа для каждого тега; код закрытого тега может быть указан непосредственно в шаблоне с контентом, т.е. в нем можно использовать штатные теги dle.
- Робокасса - добавлена передача фискальных данных
- Добавлена интеграция с платежным агрегатором AnyPay
### Изменено
- Обновлен раздел статистики
### Исправлено
- Исправление багов

## [0.8] - 20.05.2023
### Добавлено
- Обновлены и добавлены в модуль некоторые плагины
- Добавлена возможность прямой оплаты услуг, в обход баланса
- Добавлена возможность оплаты некоторых услуг неавторизованным пользователям
### Изменено
### Исправлено

## [0.7.6] - 24.02.2023
### Добавлено
### Изменено
### Исправлено
- Исправление багов

## [0.7.5] - 20.02.2023
### Добавлено
- Переход на PHP 8.*
### Изменено
### Исправлено
- Адаптирован для DLE 16.0

## [0.7.4] - 25.08.2019
### Добавлено
- В личный кабинет пользователя добавлен раздел **«Квитанции»**, в котором отображается список всех квитанций
- В настройках модуля добавлен новый пункт, указывающий максимальное количество неоплаченных квитанций которые может создать пользователь
- В личный кабинет пользователя добавлена возможность удалять неоплаченные квитанции
### Изменено
- Изменён принцип создание новых квитанций: теперь создается квитанция и её можно оплачивать любой платежной системой
### Исправлено
- Модуль полностью адаптирован к системе плагинов
- Исправлена ошибка при отображении статистики в админ панель модуля

## [0.7.3] - 06.06.2019
### Добавлено
### Изменено
- Изменены иконки в админ панель модуля.
### Исправлено
- Модуль адаптирован под DLE 13.0 и выше
