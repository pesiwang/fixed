<?php
namespace Home\Controller;

use Think\Controller;
use Org\WeiXin\Sn;
use Org\WeiXin\Curl;
use phpseclib\Crypt\Random;
use Org\WeiXin\WeiXinPay;

class WxController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->wechat_userinfo = M('wechat_userinfo');
        $this->p = D('Cate');
    }

    public function ajax_payjsdk()
    {
        $money = I('money');
        $openid = I('openid');
        $for = I('for');
        $order_id = I('order_id');
//         $money = '0.01';
//         $openid = 'og0I1s9LEoIdmAPukrKVGFI6S_1M';
//         $order_id = '2016082514485352104';
        
        
        if ($openid != session('openid')) {
            $ret['code'] = 100;
            $ret['msg'] = '请刷新页面再试！';
            $this->ajaxReturn($ret);
        }
        
        if (! is_numeric($money) ) {
            $ret['code'] = 110;
            $ret['msg'] = '输入金钱不对';
            $this->ajaxReturn($ret);
        }
        
        if (! is_numeric($order_id)) {
            $ret['code'] = 120;
            $ret['msg'] = '订单id不合法';
            $this->ajaxReturn($ret);
        }
        
        $order = $this->p->isPayOrder($order_id, $openid, $money);
        
        if (!$order) {
            $ret['code'] = 130;
            $ret['msg'] = '该订单不存在';
            $this->ajaxReturn($ret);
        }
        
        if (empty($openid)) {
            $ret['code'] = 140;
            $ret['msg'] = '用户openid为空！';
            $this->ajaxReturn($ret);
        }
        
        if (empty($for)) {
            $for = '修好乐';
        }
        
	    $jsdk = $this->jssdkpay($openid, $money, $order_id, $for);
	    
	    if ($jsdk['code'] == 100 ){
	        $ret['code'] = 150;
	        $ret['msg'] = $jsdk['msg'];
	        $this->ajaxReturn($ret);
	    }
	    
        $ret['code'] = 0;
        $ret['msg'] = '请求成功';
        $ret['jsdk'] = $jsdk;
        $this->ajaxReturn($ret);
    }

    /**
     * 支付失败跳转页面
     */
    public function payerror()
    {
        $jsdk = $this->getJsdk();
        $this->assign('jsdk', $jsdk);
        $this->display();
    }

    /**
     * 支付成功跳转页面
     */
    public function paysuccess()
    {
        $jsdk = $this->getJsdk();
        $this->assign('jsdk', $jsdk);
        $this->display();
    }

    /**
     * 支付请求接口-主动生成页面JS代码。
     * 
     * @param
     *            $openid
     * @param
     *            $money
     * @param $pname 产品名称            
     */
    public function jssdkpay($openid, $money, $order_id, $pname = "修好乐")
    {
        Vendor('WeiXinPay.WxPayApi');
        Vendor('WeiXinPay.JsApiPay');
        $inputObj = new \WxPayUnifiedOrder();
        $input = new \WxPayApi();
        $jstool = new \JsApiPay();
        $inputObj->SetBody($pname);
        $inputObj->SetTotal_fee($money * 100);
        $inputObj->SetOut_trade_no($order_id);
        $inputObj->SetTrade_type("JSAPI");
        $inputObj->SetOpenid($openid);
        $inputObj->SetNotify_url("http://www.xiuhl.com/Home/Wx/getNotify");
        $result = $input->unifiedOrder($inputObj);
        if ($result['result_code'] == "FAIL") { $ret['code'] = 100; $ret['msg'] = $result['err_code_des']; return $ret;}
        $result = $jstool->GetJsApiParameters($result);
        // var_dump($result);exit;
        // 保存用户预支付数据-防止漏单
        $data['out_trade_no'] = $order_id;
        $data['openid'] = $openid;
        $data['add_time'] = time();
        M('pre_paylog')->data($data)->add();
        // 保存用户预支付数据-end
        $ret['result'] = $result; // 页面使用的js代码;
        $ret['order_id'] = $order_id;
        return $ret;
    }

    /**
     * 异步接收支付结果-微信请求接口
     */
    public function getNotify()
    {
        header('Content-Type: text/html; charset=utf-8');
        $timezone = "Asia/Shanghai";
        date_default_timezone_set($timezone); // 北京时间
                                              // $GLOBALS['HTTP_RAW_POST_DATA'] 微信采用的xml发送的post 只能采用原始数据获取
        $msg = array();
        $msg = (array) simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);
        
        extract($msg); // 数组转换成变量
        if ($result_code == 'SUCCESS') {
            // 更新你的数据库--验证签名
            if ($trade_type != "NATIVE") {
                $where['openid'] = $openid;
            }
            $where['out_trade_no'] = $out_trade_no;
            $is_pre = M('pre_paylog')->where($where)->find();
            $is_pay = M('paylog')->where($where)->find();
            if ($is_pre && ! $is_pay) { // 支付成功，只保存一次支付记录
                                   // 日志文件 txt
                ob_start();
                echo "\r\n------------------------------------------------\r\n";
                echo $GLOBALS['HTTP_RAW_POST_DATA'];
                $ob = ob_get_contents();
                file_put_contents("C:/wamp/www/paylog.txt", $ob, FILE_APPEND);
                ob_end_clean();
                // 日志文件---end
                
                // 更新数据库 -paylog
                $data['openid'] = $openid;
                $data['is_subscribe'] = $is_subscribe;
                $data['cash_fee'] = $cash_fee;
                $data['out_trade_no'] = $out_trade_no;
                $data['return_code'] = $return_code;
                $data['sign'] = $sign;
                $data['time_end'] = $time_end;
                $data['total_fee'] = $total_fee;
                $data['transaction_id'] = $transaction_id;
                $data['add_time'] = time();
                $data['module'] = 3;
                M('paylog')->data($data)->add();
                // 更新数据库 -paylog -end
                
                // 支付成功，额外操作
                
                D('Cate')->paySuccess($out_trade_no, $openid);
                $tmp = D('Tell')->CompletionOfPaymentData($total_fee);
                $this->sendTplMsg($openid, $tmp['template_id'], $tmp['url'], $tmp['data']);
                
                // 支付成功，额外操作-end
            }
            // 更新你的数据库--验证签名-end
            echo "success"; // 查看是否成功
        }
    }
}
