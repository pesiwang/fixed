$(function() {
	window.i = 0;
});
$(function() {
	// 请求数据
	$.ajax({
		url : '/Home/Phonefixed/phone_prbm',
		type : 'post',
		data : 'color_id=' + window.color_id,
		beforeSend : function() {
			show_loading();
		},
		success : function(msg) {
			hide_loading();
			refresh(msg);
			addDownAction();
		},
		statusCode : {
			404 : function() {
				hide_loading();
				show_alert("服务器不可用");
			}
		}
	});

})

function refresh(data) {
	if (data.code != 0) {
		show_alert(data.msg);
		return;
	}
	
	$("#model-name").html("型号：  " + data.model_name + '--' + window.color);
	$("[name=model_color]").val(data.model_name + '--' + window.color);
	
	var dom = '';
	$(data.list).each(function(k, v) {
		dom += typeDomHead(v.prbm);
		dom += detailDom(v.detail, v.id);
		dom += typeDomFoot();
	});
	
	$('#accordion').html(dom);
	addClickAction();
}

function typeDomHead(prbm) {
	var dom = '<li><div class="link"><i class="fa-chevron-down"><img src="'
			+ window.home
			+ '/img/plus.png" width="6%" class="plus"/><img src="'
			+ window.home + '/img/minus.png" width="6%" class="minus"/></i>'
			+ prbm
			+ '</div><ul class="submenu weui_cells weui_cells_checkbox">';
	return dom;
}

function typeDomFoot() {
	var dom = '	</ul></li>';
	return dom;
}


function detailDom(detail, id) {
	var dom = '';
	$(detail)
			.each(
					function(k, v) {
						i++;
						dom += '<li><label class="weui_cell weui_check_label" for="'
								+ window.i
								+ '"><div class="weui_cell_hd"><input type="checkbox" class="weui_check" val="'+ v
								+ '" name="'+ id
								+ '" id="'+ window.i
								+ '"><i class="weui_icon_checked"></i></div><div class="weui_cell_bd weui_cell_primary"><p>'
								+ v + '</p></div></label></li>';
					});
	return dom;
}
var href = "/Home/Phonefixed/before/prbm_id/'+ id+ '/color/'+ window.color+ '/prbm/'+ v+ '";

function addClickAction()
{
	$('.weui_check').click(function(){
		submitStatus();
		prbmlist();
	});
}

function prbmlist()
{
	var string = '';
	$('.weui_check').each(function(k, v){
		if ($(v).is(':checked')){
			if (string == '') {
				string += $(v).attr('val');
			}else{
				string += ',' + $(v).attr('val');
			}
		}
	});
	$('#prbm-list').html(string);
	$('[name=prbm_list]').val(string);
}

function submitStatus()
{
	var status = false;
	$('.weui_check').each(function(k, v){
		if ($(v).is(':checked')){
			status = true;
			return false;
		}
	});
	
	if (status){
		$('#submit').removeClass('weui_btn_disabled');
		$('#submit').addClass('weui_btn_primary');
	}else {
		$('#submit').removeClass('weui_btn_primary');
		$('#submit').addClass('weui_btn_disabled');
	}
}


// 初始化下拉事件
function addDownAction() {
	var Accordion = function(el, multiple) {
		this.el = el || {};
		this.multiple = multiple || false;

		// Variables privadas
		var links = this.el.find('.link');
		// Evento
		links.on('click', {
			el : this.el,
			multiple : this.multiple
		}, this.dropdown)
	}
	Accordion.prototype.dropdown = function(e) {
		var $el = e.data.el;
		$this = $(this), $next = $this.next();

		$next.slideToggle();
		$this.parent().toggleClass('open');

		if (!e.data.multiple) {
			$el.find('.submenu').not($next).slideUp().parent().removeClass(
					'open');
		}
		;
	}

	var accordion = new Accordion($('#accordion'), false);
	
	// 绑定下拉啦+、-图片切换
	$(".link").click(function() {
		var li = $($(this).parent()[0]);
		var i = $(this).children();
		$('.minus').css("display", "none");
		$('.plus').css("display", "inline");
		if ($(this).parent().hasClass("open")) {
			i.children(".plus").css("display", "none");
			i.children(".minus").css("display", "inline");
		} else {
			i.children(".plus").css("display", "inline");
			i.children(".minus").css("display", "none");
		}
	}) 
	
}