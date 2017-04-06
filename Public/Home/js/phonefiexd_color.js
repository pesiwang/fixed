$(function() {
	addDownAction
});
$(function() {
	// 请求数据
	$.ajax({
		url : '/Home/Phonefixed/phone_type_color',
		type : 'post',
		data : 'brand_id=' + window.brand_id,
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
	var dom = '';
	$(data.list).each(function(k, v) {
		dom += typeDomHead(v.name);
		dom += colorDom(v.color, v.id);
		dom += typeDomFoot();
	});
	$('#accordion').html(dom);
}

function typeDomHead(name) {
	var dom = '<li><div class="link"><i class="fa-chevron-down"><img src="'
			+ window.home
			+ '/img/plus.png" width="6%" class="plus"/><img src="'
			+ window.home + '/img/minus.png" width="6%" class="minus"/></i>'
			+ name + '</div><ul class="submenu">';
	return dom;
}

function typeDomFoot() {
	var dom = '	</ul></li>';
	return dom;
}

function colorDom(color, id) {
	var dom = '';
	$(color).each(
			function(k, v) {
				dom += '<li><a href="/Home/Phonefixed/prbm/color_id/' + id
						+ '/color/' + v + '">' + v
						+ '<span class="right"></span></a></li>';
			});
	return dom;
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