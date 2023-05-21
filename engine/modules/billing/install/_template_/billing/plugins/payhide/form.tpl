<script type="text/javascript">
function billing_Payhide()
{	
	var genForm = '[payhide';
	
	var arr = [];

	$('#phGenFormGroups option:selected').each(function(index)
	{
		arr[index] = $(this).val();
	});
				
	if( arr[0] )
	{
		genForm += ' open='+arr.join(',');
	}
				
	if( ! $("#phGenFormPrice").val() )
	{	
		DLEalert('<b>Цена доступа</b> - обязательный параметр', 'Внимание');
				
		$("#phGenFormPrice").val("10.00");
	}
				
	if( $("#phGenFormTime").val() )
	{
		genForm += ' time='+$("#phGenFormTime").val();
	}
				
	genForm += ' post=1';
	genForm += ' autor=1';
	genForm += ' key={key}';
	genForm += ' price='+$("#phGenFormPrice").val();
	genForm += ']Ваш контент[/payhide';
	genForm += ']';
				
	$("#phGenFormTag").val( genForm );
}
			
window.onload = function()
{
	billing_Payhide();
}
</script>

<li class="form-group">
	<label>
		<a href="#" onclick="$('.addpayhide').toggle();return false;">Создать платный тег</a>
	</label>
</li>

<li class="form-group addpayhide" style="display:none;">
	<label for="phGenFormGroups">Открыть доступ:</label>
	<select id="phGenFormGroups" class="wide" onClick="billing_Payhide()" multiple>{groups}</select>
</li>

<li class="form-group addpayhide" style="display:none;">
	<label for="phGenFormGroups">Цена доступа, RUR:<label> <span style="float: right">Ваш доход составит {percent}% от стоимости доступа</span>
	<input id="phGenFormPrice" class="wide" onkeyup="billing_Payhide()" class="edit bk" type="text" size="10" value="10.00">
</li>

<li class="form-group addpayhide" style="display:none;">
	<label for="phGenFormGroups">Время доступа в минутах:<label>
	<input id="phGenFormTime" class="wide" onkeyup="billing_Payhide()" class="edit bk" type="text" size="10" value="">
</li>
	
<li class="form-group addpayhide" style="display:none;">
	<label for="phGenFormGroups">Код для вставки:<label>
	<textarea style="width:100%;height:40px; text-align: center; " class="edit bk" onClick="this.focus(); this.select()" id="phGenFormTag"></textarea>
</li>
