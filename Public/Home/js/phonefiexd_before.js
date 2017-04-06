$(function() {
	// 请求数据
	$.ajax({
		url : '/Home/Phonefixed/count_fee',
		type : 'post',
		data : 'prbm_list=' + window.prbm_list + '&model_color='
				+ window.model_color,
		beforeSend : function() {
			show_loading();
		},
		success : function(msg) {
			hide_loading();
			refresh(msg);
		},
		statusCode : {
			404 : function() {
				hide_loading();
				show_alert("服务器不可用");
			}
		}
	});

});
function refresh(msg) {
	$('#model').html(window.model_color);
	$('#total').html(msg.total + '元');
	$('[name=model_color]').val(window.model_color);
	$('[name=prbm_list]').val(window.prbm_list);
	build4Dom_div(msg.list);
	
}

function build4Dom_div(list)
{
	var dom = '';
	$(list).each(function(k, v){
		dom += '<div class="weui_cells_con">';
		dom += build4Dom_p('故障', v.detail);
		dom += build4Dom_p('方案', v.solution);
		dom += build4Dom_p('费用', v.fee + '元');
		dom += '</div>';
	});
	
	$('#list').html(dom);
}

function build4Dom_p(describe, val) 
{
	var dom = '<p class="weui_cells_total"><span class="weui_cells_title">'
			+ describe + '：</span><span class="weui_cells_reason" >' + val
			+ '</span></p>';
	return dom;
}