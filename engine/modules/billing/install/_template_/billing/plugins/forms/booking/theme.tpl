   <div class="billingFormModal" data-width="400" data-height="580" title='{form_title}' style='display:none'>
       <form class="billingForm">

           {hidden_input}

           <!-- Сообщение об успешном сохранении форсы -->
           <input type="hidden" name="response[text]" value="Заявка сохранена">

           <!-- Скрытые параметры -->
           <input type="hidden" name="hotel" value="Name Hotel">

           <ul class="ui-form">
               <li class="form-group">
                   <label>Имя:</label>
                   <input type="text" name="fio" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <label>Дней: *</label>
                   <input type="number" name="days" value="1" min="1" step="1" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <label>Гостей: *</label>
                   <input type="number" name="guests" value="1" min="1" max="5" step="1" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <label>Дата и время заезда:</label>
                   <input type="datetime-local" style="width: 100%"
                          name="date">
               </li>
               <li class="form-group">
                   <label>Email: *</label>
                   <input type="text" name="email" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <label>Телефон: *</label>
                   <input type="text" name="phone" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <label>Пожелания:</label>
                   <input type="text" name="comment" style="width: 100%" required>
               </li>
               <li class="form-group">
                   <div class="checkbox">
                       <input type="checkbox" name="agree" value="yes" required>&nbsp;<label for="agree">С правилами согласен *</label>
                   </div>
               </li>
           </ul>

           <div style="text-align: center; padding-top: 10px">
               <button type="submit" class="btn billingFormSend-{uniqid}" onClick='return false;'>
                   <span>Продолжить</span>
               </button>
           </div>
       </form>
    </div>

<a href="#" class="billingFormShowModal-{uniqid}">Заполнить форму</a>