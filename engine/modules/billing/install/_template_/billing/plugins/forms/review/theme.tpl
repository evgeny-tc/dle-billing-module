   <div class="billingFormModal" data-width="400" data-height="300" title='{form_title}' style='display:none'>

       <link media="screen" href="/templates/{module.skin}/billing/css/styles.css" type="text/css" rel="stylesheet" />

       <form class="billingForm">

           {hidden_input}

           <!-- Сообщение об успешном сохранении форсы -->
           <input type="hidden" name="response[text]" value="Спасибо">

           <ul class="ui-form">
               <li class="form-group">
                   <label>Имя: *</label>
                   <input type="text" name="name" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <label>Комментарий: *</label>
                   <input type="text" name="comment" style="width: 100%" required>
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