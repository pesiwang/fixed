$(function() {
	$.ajax({
		url : '/Home/Phonefixed/getBrandList',
		type : "GET",
		beforeSend : function() {
			show_loading();
		},
		success : function(msg) {
			hide_loading();
			refresh(msg.data);
		},
		statusCode : {
			404 : function() {
				hide_loading();
				show_alert("服务器不可用");
			}
		}
	});

});
function refresh(data) {
	$('.hd').nextAll('.brand').remove();
	$(data).each(function(k, v) {
		if (v.brand.length == 0) {
			return true;
		}
		var topdom = typeDom(v.name, v.id);
		$('.hd').after(topdom);
		$(v.brand).each(function(k, v) {
			var branddom = brandDom(v.name, v.id);
			var html = "";
			html = $('#brand' + v.parent).html() + branddom;
			$('#brand' + v.parent).html(html);
		});

	});
}
function typeDom(name, id) {
	var dom = '<div class="brand"><div class="weui_cells_title xiuhaole_title">'
			+ name
			+ '</div><div class="content"><div class="weui_cells weui_cells_access" id=brand'
			+ id + '></div></div><div style="width: 100%;">&nbsp;</div></div>';
	return dom;
}
function brandDom(name, id) {
	var dom = '<a class="weui_cell" href="/Home/Phonefixed/color/brand_id/'
			+ id 
			+ '"><div class="weui_cell_bd weui_cell_primary"><p>'
			+ name
			+ '</p></div><div class="weui_cell_ft"></div></a>';
	return dom;
}