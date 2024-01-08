/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */
function BillingJSAdmin( hash )
{
	/**
	 * Ожидание применения изменений
	 * @param elem
	 */
	this.progressBtn = function(elem)
	{
		let btnText = elem.html();
		let i = 3;

		elem.html(btnText + '..' + i);

		let timerId = setInterval(function() {
			i -= 1;

			elem.html(btnText + '..' + i);

			if( ! i )
			{
				elem.html(btnText);
				elem.removeAttr('disabled');
				elem.prop('onclick', '');

				clearInterval(timerId);
			}

			console.log(i, elem);

		}, 1000);
	}

	/**
	 * Отметить все чекбоксы на странице
	 * @param obj
	 */
	this.checkAll = function(obj)
	{
		let items = obj.form.getElementsByTagName("input"), len, i;

		for (i = 0, len = items.length; i < len; i += 1)
		{
			if (items.item(i).type && items.item(i).type === "checkbox")
			{
				if (obj.checked)
				{
					items.item(i).checked = true;
				}
				else
				{
					items.item(i).checked = false;
				}
			}
		}
	};

	/**
	 * Открыть модальное/диалог. окно по ID
	 * @param id
	 */
	this.openDialog = function( id )
	{
		$(id).dialog(
			{
				autoOpen: true,
				show: 'fade',
				width: 480,
				dialogClass: "modalfixed"
			}
		);

		$('.modalfixed.ui-dialog').css({position:"fixed"});
		$(id).dialog( "option", "position", ['0','0'] );
	}

	/**
	 * Пользователи
	 */
	this.users = [];

	this.usersAdd = function( name )
	{
		if( this.users.in_array(name) )
		{
			this.users.clean(name);

			$('#user_'+name).html('<i class=\"fa fa-plus\" style=\"margin-left: 10px; vertical-align: middle\"></i>');
		}
		else
		{
			this.users[this.users.length+1] = name;

			$('#user_' + name).html('<i class=\'fa fa-check\' style=\'margin-left: 10px; vertical-align: middle\'></i>');
		}

		this.users.clean(undefined);

		$('#edit_name').val( this.users.join(', ') );
	};

	/**
	 * Замена URL
	 * @type {*|jQuery}
	 */
	this.url_items = $("#url-count").val();

	this.urlAdd = function()
	{
		this.url_items ++;

		let field = `<div id="url-item-${this.url_items}" class="url-item">
			<span onClick="BillingJS.urlRemove(${this.url_items})"><i class="fa fa-trash"></i></span>
			<input name="save_url[${this.url_items}][start]" class="form-control" style="width: 90%; text-align: center"  type="text" placeholder="start..." value="">
			<i class="fa fa-refresh"></i>
			<input name="save_url[${this.url_items}][end]" class="form-control" style="width: 90%; text-align: center"  type="text" placeholder="end..." value="">
		</div>`;

		$(".url-list").append(field);
	}

	this.urlRemove = function( id )
	{
		$("#url-item-" + id).remove();
	}
}

let BillingJS = new BillingJSAdmin(dle_login_hash);

Array.prototype.in_array = function(p_val)
{
	for(let i = 0, l = this.length; i < l; i++)
	{
		if(this[i] == p_val)
		{
			return true;
		}
	}
	return false;
};

Array.prototype.clean = function(deleteValue)
{
    for (let i = 0; i < this.length; i++)
    {
        if (this[i] == deleteValue)
        {
            this.splice(i, 1);
            i--;
        }
    }
    return this;
};
