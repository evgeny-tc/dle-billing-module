[plugin_on]
    <div style="min-height: 90px; font-size: 12px; border: 2px solid [stop_list]#ee4848[/stop_list][no_stop_list]#8fc400[/no_stop_list]; padding: 10px;">
        <div style="padding: 4px;border-bottom: 1px solid #ccc">
            <span style="float: right; color: #969696">
                [stop_list]<font color="red">Прием пожертвований для этого пользователя заблокирован</font>[/stop_list]
                [no_stop_list]собрано <b>{sum} {sum.currency}</b> [limit]из <b>{limit} {limit.currency} ({percent}%)</b>[/limit][/no_stop_list]
            </span>
            <b>Поддержать автора</b>
        </div>

        <span style="float: right; margin: 8px">
            [no_stop_list]
                [login_no][/login_no]
                [login_yes][/login_yes]
                    <div style="margin-top: 10px; padding: 5px" id="billing-donate-form-sum-{panel-id}">
                        <input id="billing-donate-value-{panel-id}" value="{setting.min}" style="padding: 5px; text-align: right; width: 60px; border: 1px solid #ccc" /> {setting.min.currency}
                        <button class="billing-donate-send"
                                data-id="{panel-id}"
                                data-user="{donate.login}"
                                data-group-id="{donate.code}"
                                data-min="{setting.min}"
                                data-max="{setting.max}"
                                data-balance="{balance}"
                                data-langerror="Внимание"
                                data-error-min="Минимальная сумма пожертвования - {setting.min} {setting.min.currency}"
                                data-error-max="Максимальная сумма пожертвования - {setting.max} {setting.max.currency}"
                                type="submit" name="button" title="Отправить средства" style="padding: 5px; border: 0; cursor: pointer; background-color: #3394e6; color: white">Отправить</button>
                    </div>
                    <div  id="billing-donate-form-ok-{panel-id}" style="margin: 20px; display: none">
                        <font color="green"><b>Спасибо!</b></font>
                    </div>
            [/no_stop_list]
        </span>

        <br />
        Все собранные средства будут переданы автору этой статьи -  <a href="/user/{donate.login.urlencode}">{donate.login}</a>
        <br />
        Сумма перевода от {setting.min} {setting.min.currency} [max_sum]до {setting.max} {setting.max.currency}[/max_sum][percent], комиссия {setting.percent}%[/percent]
        [no_stop_list]
            [login_yes]
            <span id="billing-donate-form-comment-{panel-id}">
                <br />
                <input id="billing-donate-comment-{panel-id}" placeholder="За отличную статью.." maxlength="128" style="padding: 5px; width: 98%; border: 1px solid #ccc" />
            </span>
            [/login_yes]
        [/no_stop_list]
    </div>
[/plugin_on]

[plugin_off]
<font color="red">Прием пожертвований остановлен</font>
[/plugin_off]
