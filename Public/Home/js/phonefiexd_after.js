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
	$('#model').html('机型：'+window.model_color);
	$('#total').html(msg.total + '元');
	$('[name=model]').val(window.model_color);
	$('[name=prbmlist]').val(window.prbm_list);
	$('[name=comments]').val(window.comments);
	$('#comments').html(window.comments);
	build4Dom_div(msg.list);
	hideIndoorTime();
	getPronvince();
	bindAreaAction();

}
 
function build4Dom_div(list) {
	var dom = '';
	$(list).each(function(k, v) {
		dom += '<div class="weui_cells_con">';
		dom += build4Dom_p('故障', v.detail);
		dom += build4Dom_p('方案', v.solution);
		dom += build4Dom_p('费用', v.fee + '元');
		dom += '</div>';
	});

	$('#list').html(dom);
}

function build4Dom_p(describe, val) {
	var dom = '<p class="weui_cells_total"><span class="weui_cells_title">'
			+ describe + '：</span><span class="weui_cells_reason" >' + val
			+ '</span></p>';
	return dom;
}

function getPronvince() {
	$.ajax({
		url : "/Home/Phonefixed/get_pronvince",
		type : 'get',
		success : function(msg) {
			if (msg.code == 0) {
				var dom = '<option value="0">请选择</option>';
				$(msg.list).each(
						function(k, v) {
							dom += '<option value="' + v.code + ' " >' + v.name
									+ '</option>';
						});
				$("#address0").html(dom);
			}
		}
	});
}

function getCity(city_code) {
	$.ajax({
		url : "/Home/Phonefixed/get_city",
		type : 'post',
		data : 'city_code=' + city_code,
		success : function(msg) {
			if (msg.code == 0) {
				var dom = '<option value="0">请选择</option>';
				$(msg.list).each(
						function(k, v) {
							if (k == 0) {
								return true;
							}
							;
							dom += '<option value="' + v.code + '" >' + v.name
									+ '</option>';
						});
				$("#address1").html(dom);
			}
		}
	});
}

function getCounty(county_code) {
	$.ajax({
		url : "/Home/Phonefixed/get_county",
		type : 'post',
		data : 'county_code=' + county_code,
		success : function(msg) {
			if (msg.code == 0) {
				var dom = '<option value="0">请选择</option>';
				$(msg.list).each(
						function(k, v) {
							if (k == 0) {
								return true;
							}
							;
							dom += '<option value="' + v.code + '">' + v.name
									+ '</option>';
						});
				$("#address2").html(dom);
			}
		}
	});
}

function bindAreaAction() {
	$("#address0").change(function() {
		var city_code = $("#address0").val();
		getCity(city_code);
	});

	$("#address1").change(function() {
		var city_code = $("#address1").val();
		getCounty(city_code);
	});
}

function hideIndoorTime() {
	$('.weui_check').click(function() {
		if ($(this).val() == 'indoor') {
			alert('目前仅开通珠海地区上门维修，其他地区暂未开通！');
			$('#indoor-time').show();
			$('#indoor-tips').show();
		} else if ($(this).val() == 'post') {
			$('#indoor-time').hide();
			$('#indoor-tips').hide();
		}
	});
}

function formSubmit()
{
	var user_name = $('[name=user_name]').val();
	var phone = $('[name=phone]').val();
	var address0 = $('#address0').val();
	var address1 = $('#address1').val();
	var address2 = $('#address2').val();
	var address3 = $('#address3').val();
	var indoor_time = $('[name=indoor_time]').val();
	var p = /^1[3-8]\d{9}$/gi ;
	
	if($('#s12').is(":checked")) {
		var way = 'post';
	}else{
		var way = 'indoor';
	}
	
	if (user_name == '') {
		alert('用户名不能为空');
		return false;
	}
	
	if (phone == '' || !p.test(phone)) {
		alert('手机号不合法');
		return false;
	}
	
	if (address0 == '0') {
		alert('省不能为空');
		return false;
	}
	
	if (address1 == '0') {
		alert('市不能为空');
		return false;
	}
	
	if (address2 == '0') {
		alert('地区不能为空');
		return false;
	}
	
	if (address3 == '') {
		alert('详细地址不能为空');
		return false;
	}
	
	if (way == 'indoor' && indoor_time == '') {
		alert('上门时间不能为空');
		return false;
	}
	$('form').submit();
}