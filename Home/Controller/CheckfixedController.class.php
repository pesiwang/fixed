<?php  
namespace Home\Controller;
use Think\Controller;
use Org\WeiXin\Sn;
use Org\WeiXin\Curl;
use phpseclib\Crypt\Random;
use Org\WeiXin\WeiXinPay;
class CheckfixedController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->sn_record=M('sn_record');
		$this->wechat_userinfo=M('wechat_userinfo');
		$this->check_fixed=M('check_fixed');
		$this->sn_result=M('sn_result');
	}
	/**
	 * 通过用户发送的sn码，显示查询结果
	 */
	public function checkFixed(){
	    if(session('openid')==null){//查询是否最近访问了-----防止多次授权【烦人】
	        $userInfo=$this->getUserInfo();
	    }else{
	        $where['openid']=session('openid');
	        $userInfo=$this->wechat_userinfo->where($where)->find();
	    }
	    session('openid',$userInfo['openid']);
	    $openid=session('openid');
	    if(!$this->checkSubscribe($openid)){//防止分享访问
	        exit('请从公众号内链接访问');
	    }
	    $imei=I('get.sn');
	    $timestap=I('get.timestap');
	    $result=$this->checkIsResult($imei, $timestap);
	    $hadtimes=$this->checkTimes($openid);
	    if($result){//检验是否已经查询过-保存数据，防止二次查询
	        $result=json_decode($result['result'],true);
	        $this->assign('hadtimes',$hadtimes);
	        $this->assign('result',$result);
	        $this->assign('userInfo',$userInfo);
	        $this->display();exit;
	    }
	    if($hadtimes['left']==0){//检验查询次数----没有查询次数了---微信支付
	        $val=$this->check_fixed->where('id=1')->getField('money');
	        $addlimiter=$this->check_fixed->where('id=1')->getField('addlimiter');
	        $wxpayjs= $this->jssdkpay($openid,$val,'增加查询次数');//$val*100
	        $this->assign('wxpayjs',$wxpayjs['result']);
	        $this->assign('orderid',$wxpayjs['out_trade_no']);
	        $this->assign('addlimiter',$addlimiter);
	        $this->display("notimes");exit;
	    }
	    
	    $sn=new Sn();
	    $result=$sn->checkSn($imei,$userInfo['openid']);
	    if($result['sn']!=''){//进行查询操作
	        $where['openid']=$userInfo['openid'];
	        $this->wechat_userinfo->where($where)->setInc('times');
	        $data['sn']=$imei;
	        $data['result']=json_encode($result);
	        $data['timestap']=$timestap;
	        $this->sn_result->data($data)->add();
	        //显示剩余次数
	        $this->assign('hadtimes',$hadtimes);
	        //显示剩余次数-end
	        $this->assign('result',$result);
	        $this->assign('userInfo',$userInfo);
	        $this->display();
	    }else{
	        $this->display("checkerror");
	    }
	}
	/**
	 * 友情赞助
	 */
	public function helper(){ 
	    if(session('openid')==null){//查询是否最近访问了-----防止多次授权【烦人】
	        $userInfo=$this->getUserInfo();
	    }else{
	        $where['openid']=session('openid');
	        $userInfo=$this->wechat_userinfo->where($where)->find();
	    }
	    session('openid',$userInfo['openid']);
	    $openid=session('openid');
	    if(!$this->checkSubscribe($openid)){//防止分享访问
	        exit('请从公众号内链接访问');
	    }
	    $val=$this->check_fixed->where('id=1')->getField('money');
	    $addlimiter=$this->check_fixed->where('id=1')->getField('addlimiter');
	    $wxpayjs= $this->jssdkpay($openid,$val,'友情增加查询');
	    $this->assign('wxpayjs',$wxpayjs['result']);
	    $this->assign('orderid',$wxpayjs['out_trade_no']);
	    $this->assign('addlimiter',$addlimiter);
	    $this->display();exit;
	}
	public function paytest(){
	   $userinfo=$this->getUserInfobase();
	    //var_dump($userinfo);
	  // var_dump($userinfo['openid']);
	   // $userinfo['openid']= "og0I1s9LEoIdmAPukrKVGFI6S";
	    $this->assign('openid',$userinfo['openid']);
	    $this->display();
	}
	public function ajax_payjsdk(){
	    $money=I('money');
	    $openid=I('openid');
	    if(!is_numeric($money)){
	        $bak['code']=1;
	        $bak['msg']='输入金钱不对';
	        $this->ajaxReturn($bak);
	    }
	    $jsdk=$this->jssdkpay($openid,$money,"zjt");
	    $bak['code']=0;
	    $bak['msg']='支付成功';
	    $bak['jsdk']=$jsdk;
	    $this->ajaxReturn($bak);
	   
	}
	public function checkTimes($openid){
	    $fixed=$this->check_fixed->where('id = 1')->find();
	    $where['openid']=$openid;
	    $userinfo=$this->wechat_userinfo->where($where)->find();
	    $bak['type']='times';
	    $bak['left']=0;
	    $nowtime=time();
	    if($userinfo['times']<$fixed['limiter']){
	        $bak['type']='times';
	        $bak['left']=$fixed['limiter']-$userinfo['times'];
	    }
        if($userinfo['is_add']>$nowtime){
            $bak['type']='day';
            $bak['left']=ceil(($userinfo['is_add']-$nowtime)/86400);
            if($bak['left']<0){
                $bak['left']=0;
            }
        }
        return $bak;
	}
	public function checkIsResult($imei,$timestap){
        $where['sn']=$imei;
        $where['timestap']=$timestap;
        $result=$this->sn_result->where($where)->find();
        if($result){
            return $result;
        }else{
            return false;
        }
	}  
	/**
	 * 支付失败跳转页面
	 */
	public function payerror(){
	    $jsdk=$this->getJsdk();
	    $this->assign('jsdk',$jsdk);
	    $this->display();
	}
	/**
	 * 支付成功跳转页面
	 */
	public function paysuccess(){
	    $jsdk=$this->getJsdk();
	    $this->assign('jsdk',$jsdk);
	    $this->display();
	}
	/**
	 * 支付请求接口-主动生成页面JS代码。
	 * @param $openid
	 * @param $money
	 * @param $pname 产品名称
	 */
	public function jssdkpay($openid,$money,$pname="修好乐"){
	    Vendor('WeiXinPay.WxPayApi');
	    Vendor('WeiXinPay.JsApiPay');
	    $inputObj= new \WxPayUnifiedOrder();
	    $input=new \WxPayApi();
	    $jstool=new \JsApiPay();
	    $random=$input->getNonceStr();
	    $inputObj->SetBody($pname);
	    $inputObj->SetTotal_fee($money*100);
	    $inputObj->SetOut_trade_no($random);
	    $inputObj->SetTrade_type("JSAPI");
	    $inputObj->SetOpenid($openid);
	    $inputObj->SetNotify_url("http://www.xiuhl.com/index.php/Home/Checkfixed/getNotify");
	    $result= $input->unifiedOrder($inputObj);
	    $result=$jstool->GetJsApiParameters($result);
// 	    var_dump($result);exit;
	    //保存用户预支付数据-防止漏单
	    $data['out_trade_no']=$random;
	    $data['openid']=$openid;
	    $data['add_time']=time();
	    M('pre_paylog')->data($data)->add();
	    //保存用户预支付数据-end
	    $bak['result']=$result;//页面使用的js代码;
	    $bak['out_trade_no']=$random;//商户订单号
	    return $bak;
	}
	/**
	 * 异步接收支付结果-微信请求接口
	 */
	public function getNotify(){
	    header('Content-Type: text/html; charset=utf-8');
	    $timezone="Asia/Shanghai";
	    date_default_timezone_set($timezone); //北京时间
	    //$GLOBALS['HTTP_RAW_POST_DATA'] 微信采用的xml发送的post  只能采用原始数据获取
	    $msg = array();
	    $msg = (array) simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);
	    
	    extract($msg);//数组转换成变量
	    if($result_code=='SUCCESS'){
           // 更新你的数据库--验证签名
	        if($trade_type!="NATIVE"){
	            $where['openid']=$openid;
	        }
	        $where['out_trade_no']=$out_trade_no;
	        $is_pre=M('pre_paylog')->where($where)->find();
	        $is_pay=M('paylog')->where($where)->find();
	        if($is_pre&&!$is_pay){// 支付成功，只保存一次支付记录
	            //日志文件 txt
	            ob_start();
	            echo "\r\n------------------------------------------------\r\n";
	            echo $GLOBALS['HTTP_RAW_POST_DATA'];
	            $ob = ob_get_contents();
	            file_put_contents("C:/wamp/www/paylog.txt",$ob,FILE_APPEND);
	            ob_end_clean();
	            //日志文件---end
	            
	            //更新数据库 -paylog
	            $data['openid']=$openid;
	            $data['is_subscribe']=$is_subscribe;
	            $data['cash_fee']=$cash_fee;
	            $data['out_trade_no']=$out_trade_no;
	            $data['return_code']=$return_code;
	            $data['sign']=$sign;
	            $data['time_end']=$time_end;
	            $data['total_fee']=$total_fee;
	            $data['transaction_id']=$transaction_id;
	            $data['add_time']=time();
	            M('paylog')->data($data)->add();
	            //更新数据库 -paylog -end
	            
	            
	            // 支付成功，额外操作
	            unset($where);
	            $addlimiter=$this->check_fixed->where('id = 1')->getField('addlimiter');
                $preran=rand(1,100);
                $where['openid']=$openid;
                $ispay=$this->wechat_userinfo->where($where)->getField('is_add');
                $nowTime=time();
                if($ispay!=0){
                    $nowTime=0;
                }
	            if($preran<6){
	                $ran=rand(20,$addlimiter);
	                $day=$ran;
	                $ran=$nowTime+$ran*86400;
	            }else{
	                $ran=rand(10,20);
	                $day=$ran;
	                $ran=$nowTime+$ran*86400;
	            }
                $this->wechat_userinfo->where($where)->setInc('is_add',$ran);
                //推送消息给用户提示支付成功
                $this->sendtplSuccess($day, $openid);
                //推送消息给用户提示支付成功-end
	            // 支付成功，额外操作-end
	        }
           // 更新你的数据库--验证签名-end
	        echo "success";	//查看是否成功
	    }
	}
	/**
	 * 发送支付成功的模板消息
	 * @param  $day 支付赠送的天数
	 * @param unknown $openid
	 */
	public function sendtplSuccess($day,$openid){
	        $data=array(
	            'productType'=>array(
	                'value'=>'赞助赠送查询天数',
	                "color"=>"#173177"
	            ),
	            'number'=>array(
	                'value'=>'1',
	                "color"=>"#2C12FF"
	            ),
	            'remark'=>array(
	                'value'=>"恭喜你获得了{$day}天无限制查询",
	                "color"=>"#E82B2B"
	            ),
	        );
	    $this->sendTplMsg($openid, "nM7fw7dvPltihdlXjanu7cVo_MLASkBRdcJ6_rkyjmU", "http://www.xiuhl.com/index.php/Home/Checkfixed/checkFixed?sn=F19P3AK1G5MQ", $data);
	}
	public function outOpenid(){
	    $user=$this->getUserInfo();
	    $random=time();
	    echo '<script>window.location.href="http://zengbingo.com/index.php/System/Goods/pay?openid='.$user['openid'].'&random='.$random.'"</script>';
	}
}
