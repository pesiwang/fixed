$(function() {
	addDialogAction();
});

function addDialogAction() 
{
	$("#showDialog2").click(function() {
		$('#dialog2').show().on('click', '.weui_btn_dialog', function() {
			$('#dialog2').off('click').hide();
		});
	})
	$('#s11').click(function() {
		if ($(this).is(':checked')) {
			$('#submit').removeClass('weui_btn_disabled');
			$('#submit').addClass('weui_btn_primary');
		} else {
			$('#submit').addClass('weui_btn_disabled');
			$('#submit').removeClass('weui_btn_primary');
		}
	});
}

// 调用微信JS api 支付
function jsApiCall()
{
    WeixinJSBridge.invoke(
        'getBrandWCPayRequest',
        window.paymsg,
        function(res){
            // WeixinJSBridge.log(res.err_msg);
            // alert(res.err_code+res.err_desc+res.err_msg);
            if(res.err_msg == "get_brand_wcpay_request:ok"){
            	show_alert('支付成功');
                // alert(res.err_code+res.err_desc+res.err_msg);
                    window.location.href="http://www.xiuhl.com/Home/Checkfixed/paysuccess";
                }else{
                	alert('支付失败');
                    // 返回跳转到订单详情页面
                    // alert(支付失败);
                    window.location.href="http://www.xiuhl.com/Home/Checkfixed/payerror";
                }
        }
    );
}

function callpay()
{
    if (typeof WeixinJSBridge == "undefined"){
        if( document.addEventListener ){
            document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
        }else if (document.attachEvent){
            document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
            document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
        }
    }else{
        jsApiCall();
    }
}

function submit(){
	var money = $('#money').val();
	if (window.paing == 1){
		window.paing = 2;
    	$.ajax({
    		url:"http://www.xiuhl.com/Home/Wx/ajax_payjsdk/",
    		type:"GET",
    		dataType : "json",
    		data : {
    			"money" : window.money,
    			"openid" : window.openid,
    			"order_id" :window.order_id
    		},
    		beforeSend : function() {
    			show_loading('支付请求中...');
    		},
    		success:function(msg){
    			hide_loading();
    			if (msg.code != 0) {
    				alert(msg.msg);
    			} else {
    				window.paymsg = msg.jsdk.result;
        			window.paymsg = eval('('+ window.paymsg +')');
        			// alert(window.paymsg);
        			window.paing = 1;
        			callpay();
    			}
    		}
    	})
	} else {
		window.paing=2;
	}

}