/*
=====================================================
 Billing
-----------------------------------------------------
 evgeny.tc@gmail.com
-----------------------------------------------------
 This code is copyrighted
=====================================================
*/

var users = [];

function logShowDialogByID( id )
{
    $(id).dialog(
    {
        autoOpen: true,
        show: 'fade',
        width: 380,
        dialogClass: "modalfixed"
    });

    $('.modalfixed.ui-dialog').css({position:"fixed"});
    $(id).dialog( "option", "position", ['0','0'] );
}

function showEditDate( tag, tag_to )
{
	$(tag).hide();
	$(tag_to).show();
	$("#EditDateButton").show();
}

function checkAll(obj)
{
	var items = obj.form.getElementsByTagName("input"), len, i;

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

function usersAdd( name )
{
	if( users.in_array(name) )
	{
		users.clean(name);

		$('#user_'+name).html('<i class=\"fa fa-plus\" style=\"margin-left: 10px; vertical-align: middle\"></i>');
	}
	else
	{
		users[users.length+1] = name;

		$('#user_' + name).html('<i class=\'fa fa-check\' style=\'margin-left: 10px; vertical-align: middle\'></i>');
	}

	users.clean(undefined);

	$('#edit_name').val( users.join(', ') );
};

var url_items = $("#url-count").val();

function urlAdded()
{
    url_items ++;

    var field = '<div id="url-item-' + url_items + '" class="url-item">';

    field += '<span onClick="urlRemove(' + url_items + ')"><i class="fa fa-trash"></i></span>';
    field += '<input name="save_url[' + url_items + '][start]" class="form-control" style="width: 90%; text-align: center"  type="text" placeholder="start..." value="">';
    field += '<i class="fa fa-refresh"></i>';
    field += '<input name="save_url[' + url_items + '][end]" class="form-control" style="width: 90%; text-align: center"  type="text" placeholder="end..." value="">';

    field += '</div>';

    $(".url-list").append(field);
}

function urlRemove( id )
{
    $("#url-item-" + id).remove();
}

Array.prototype.in_array = function(p_val)
{
	for(var i = 0, l = this.length; i < l; i++)
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
    for (var i = 0; i < this.length; i++)
    {
        if (this[i] == deleteValue)
        {
            this.splice(i, 1);
            i--;
        }
    }
    return this;
};
