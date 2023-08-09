   <div class="billingFormModal" data-width="400" data-height="300" title='{form_title}' style='display:none'>
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

           <div style="text-align: center; padding-top: 10px">
               <button type="submit" class="btn billingFormSend-{uniqid}" onClick='return false;'>
                   <span>Отправить</span>
               </button>
           </div>
       </form>
    </div>

<a href="#" class="billingFormShowModal-{uniqid}">Заполнить форму</a>