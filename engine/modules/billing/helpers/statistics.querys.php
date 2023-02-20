<?php	if( ! defined( 'BILLING_MODULE' ) ) die( "Hacking attempt!" );
/**
 * DLE Billing
 *
 * @link          https://github.com/mr-Evgen/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2017, mr_Evgen
 */

return array
(
    # Рост доходов и расходов пользователей
    #
    'main' => "SELECT DAY(FROM_UNIXTIME(`history_date`)) as `D`,
                      MONTH(FROM_UNIXTIME(`history_date`)) as `M`,
                      YEAR(FROM_UNIXTIME(`history_date`)) as `Y`,
                      SUM(history_plus) as `plus`,
                      SUM(history_minus) as `minus`, history_date
                FROM " . USERPREFIX . "_billing_history
                WHERE history_date >= '%s' and history_date <= '%s'
                GROUP BY %s",

    # Всего средств в системе
    #
    'balance_all' => "SELECT count(name) as `count`, SUM(%1\$s) as `sum`
                        FROM " . USERPREFIX . "_users
                        WHERE %1\$s != 0",

    # Пополнено сегодня
    #
    'balance_today' => "SELECT SUM(invoice_get) as `sum`
                            FROM " . USERPREFIX . "_billing_invoice
                            WHERE invoice_date_pay >= '%s'",

    # Пополнено вчера
    #
    'balance_yesterday' => "SELECT SUM(invoice_get) as `sum`
                                FROM " . USERPREFIX . "_billing_invoice
                                WHERE invoice_date_pay >= '%s' and invoice_date_pay <= '%s'",

    # Выведено из системы всего
    #
    'refund_all' => "SELECT SUM(refund_summa) as `sum`, SUM(refund_commission) as `commission`
                        FROM " . USERPREFIX . "_billing_refund
                        WHERE refund_date_return != 0",

    # Выведено из системы, ожидается
    #
    'refund_wait' => "SELECT SUM(refund_summa) as `sum`
                        FROM " . USERPREFIX . "_billing_refund
                        WHERE refund_date_return = 0",

    # Пополнено через платежные системы, всего
    #
    'pay_all' => "SELECT SUM(invoice_get) as `sum`
                        FROM " . USERPREFIX . "_billing_invoice
                        WHERE invoice_date_pay != 0",

    # Пополнено через платежные системы, ожидается
    #
    'pay_wait' => "SELECT SUM(invoice_get) as `sum`
                        FROM " . USERPREFIX . "_billing_invoice
                        WHERE invoice_date_pay = 0",

    # Всего переведено
    #
    'transfer' => "SELECT SUM(history_plus) as `plus`, SUM(history_minus) as `minus`
                        FROM " . USERPREFIX . "_billing_history
                        WHERE history_plugin = 'transfer'",

    # ---
    #

    # Статистика по платежным системам
    #
    'billing_up' => "SELECT count(*) as `rows`, invoice_paysys, SUM(invoice_get) as `get`
                        FROM " . USERPREFIX . "_billing_invoice
                        WHERE invoice_date_pay  != 0 and invoice_date_creat >= '%s' and invoice_date_creat <= '%s'
                        GROUP BY invoice_paysys",

    'billing_up_null' => "SELECT count(*) as `rows`, invoice_paysys, SUM(invoice_get) as `get`
                            FROM " . USERPREFIX . "_billing_invoice
                            WHERE invoice_date_pay  = 0 and invoice_date_creat >= '%s' and invoice_date_creat <= '%s'
                            GROUP BY invoice_paysys",

    'billing_exp' => "SELECT DAY(FROM_UNIXTIME(`invoice_date_pay`)) as `D`,
                          MONTH(FROM_UNIXTIME(`invoice_date_pay`)) as `M`,
                          YEAR(FROM_UNIXTIME(`invoice_date_pay`)) as `Y`,
                          SUM(invoice_get) as `sum`
                    FROM " . USERPREFIX . "_billing_invoice
                    WHERE invoice_date_pay >= '%s' and invoice_date_pay <= '%s'
                    GROUP BY %s",

    # ---
    #

    # Доход/ расход за промежуток времени
    #
    'plugins_main' => "SELECT SUM(history_plus) as `plus`,  SUM(history_minus) as `minus`
                        FROM " . USERPREFIX . "_billing_history
                        WHERE history_date >= '%s' and history_date <= '%s'",

    # График расходов и доходов
    #
    'plugins_cost' => "SELECT DAY(FROM_UNIXTIME(`history_date`)) as `D`,
                              MONTH(FROM_UNIXTIME(`history_date`)) as `M`,
                              YEAR(FROM_UNIXTIME(`history_date`)) as `Y`,
                            SUM(history_plus) as `plus`,
                            SUM(history_minus) as `minus`, history_plus
                        FROM " . USERPREFIX . "_billing_history
                        WHERE history_date >= '%s' and history_date <= '%s'
                        GROUP BY %s",

    # Популярные платежи, доходы
    #
    'plugins_populars_minus' => "SELECT count(*) as `rows`, `history_plugin`, SUM(history_minus) as `pay`
                                    FROM " . USERPREFIX . "_billing_history
                                    WHERE history_minus != 0 and history_date >= '%s' and history_date <= '%s'
                                    GROUP BY history_plugin",

    # Популярные платежи, расходы
    #
    'plugins_populars_plus' => "SELECT count(*) as `rows`, `history_plugin`, SUM(history_plus) as `pay`
                                    FROM " . USERPREFIX . "_billing_history
                                    WHERE history_minus = 0 and history_date >= '%s' and history_date <= '%s'
                                    GROUP BY history_plugin",

    # ---
    #

    # Ожидают вывода
    #
    'users_refund' => "SELECT SUM(refund_summa) as `sum`
                        FROM " . USERPREFIX . "_billing_refund
                        WHERE refund_date_return = 0 and refund_user = '%s'",

    # Доход/ расход за промежуток времени
    #
    'users_plugins_main' => "SELECT SUM(history_plus) as `plus`,  SUM(history_minus) as `minus`
                        FROM " . USERPREFIX . "_billing_history
                        WHERE history_date >= '%s' and history_date <= '%s' and history_user_name = '%s'",

    'users_billing_up' => "SELECT count(*) as `rows`, invoice_paysys, SUM(invoice_get) as `get`
                        FROM " . USERPREFIX . "_billing_invoice
                        WHERE invoice_date_pay  != 0 and invoice_date_creat >= '%s' and invoice_date_creat <= '%s' and invoice_user_name = '%s'
                        GROUP BY invoice_paysys",

    'users_billing_up_null' => "SELECT count(*) as `rows`, invoice_paysys, SUM(invoice_get) as `get`
                            FROM " . USERPREFIX . "_billing_invoice
                            WHERE invoice_date_pay  = 0 and invoice_date_creat >= '%s' and invoice_date_creat <= '%s' and invoice_user_name = '%s'
                            GROUP BY invoice_paysys",

    'users_billing_exp' => "SELECT DAY(FROM_UNIXTIME(`invoice_date_pay`)) as `D`,
                          MONTH(FROM_UNIXTIME(`invoice_date_pay`)) as `M`,
                          YEAR(FROM_UNIXTIME(`invoice_date_pay`)) as `Y`,
                          SUM(invoice_get) as `sum`
                    FROM " . USERPREFIX . "_billing_invoice
                    WHERE invoice_date_pay >= '%s' and invoice_date_pay <= '%s' and invoice_user_name = '%s'
                    GROUP BY %s",

    # График расходов и доходов
    #
    'users_plugins_cost' => "SELECT DAY(FROM_UNIXTIME(`history_date`)) as `D`,
                              MONTH(FROM_UNIXTIME(`history_date`)) as `M`,
                              YEAR(FROM_UNIXTIME(`history_date`)) as `Y`,
                            SUM(history_plus) as `plus`,
                            SUM(history_minus) as `minus`, history_plus
                        FROM " . USERPREFIX . "_billing_history
                        WHERE history_date >= '%s' and history_date <= '%s' and history_user_name = '%s'
                        GROUP BY %s",

    # Популярные платежи, доходы
    #
    'users_plugins_populars_minus' => "SELECT count(*) as `rows`, `history_plugin`, SUM(history_minus) as `pay`
                                    FROM " . USERPREFIX . "_billing_history
                                    WHERE history_minus != 0 and history_date >= '%s' and history_date <= '%s' and history_user_name = '%s'
                                    GROUP BY history_plugin",

    # Популярные платежи, расходы
    #
    'users_plugins_populars_plus' => "SELECT count(*) as `rows`, `history_plugin`, SUM(history_plus) as `pay`
                                    FROM " . USERPREFIX . "_billing_history
                                    WHERE history_minus = 0 and history_date >= '%s' and history_date <= '%s' and history_user_name = '%s'
                                    GROUP BY history_plugin"

);
