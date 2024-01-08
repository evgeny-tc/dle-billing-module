   <div class="billingFormModal" data-width="400" data-height="580" title='{form_title}' style='display:none'>

       <link media="screen" href="/templates/{module.skin}/billing/css/styles.css" type="text/css" rel="stylesheet" />

       <form class="billingForm">

           {hidden_input}

           <!-- Сообщение об успешном сохранении форсы -->
           <input type="hidden" name="response[text]" value="Заявка сохранена">

           <ul class="ui-form">
               <li class="form-group">
                   <label>Имя:</label>
                   <input type="text" name="fio" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <label>* Количество часов:</label>
                   <input type="number" name="hours" value="1" min="1" max="8" step="1" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <label>* Самокат:</label>
                   <select name="select">
                       <option value="1" selected>Модель 1</option>
                       <option value="2">Модель 2</option>
                   </select>
               </li>
               <li class="form-group">
                   <label>Дата и время:</label>
                   <input type="datetime-local" style="width: 100%"
                          name="date">
               </li>
               <li class="form-group">
                   <label>* Email:</label>
                   <input type="text" name="email" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <div class="checkbox">
                       <label> <input type="checkbox" name="agree" value="yes" required> С правилами согласен *</label>
                   </div>
               </li>
           </ul>

           <div class="billing_modal_footer">
               [price]<h2>{price} {dec}</h2>[/price]
               <button type="submit" class="btn billingFormSend-{uniqid}" onClick='return false;'>
                   [price]<span>Оплатить</span>[/price]
                   [price_not]<span>Продолжить</span>[/price_not]
               </button>
           </div>
       </form>
    </div>

<a href="#" class="billingFormShowModal-{uniqid}">Заполнить форму</a>