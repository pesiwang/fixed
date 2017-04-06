<?php  
namespace Home\Controller;
use Think\Controller;
class JiesuobaController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this -> costtype = M('costtype');
		$this -> orderlist = M('orderlist');
		$this -> smsdetails = M('smsdetails');
		$this -> paylog = M('paylog');
		
		
	}
	
	//户点击“解网络锁”按钮后会先出现一个说明页面（关 于业务的说明）
	public function explain(){
	    if(session('openid')==null){
	   		$userInfo=$this->getUserInfo();
			$openid = $userInfo['openid'];      //凭证id
			if($openid){
				//将$opinid存在时 存入session中
				session('openid',$openid);
			}
		}
		$tpl=M('orderexplain')->where('id=1')->getField('content');
		$tpl=html_entity_decode($tpl);
		$this->assign("tpl",$tpl);
		$this -> display();
	}
	
	
	//解锁吧----我要解锁
	public function unlock(){
		$resinfo = $this -> costtype
						 -> where('isdelete = 0')
						 -> order('add_time')
						 -> select();
//		var_dump($resinfo);				 
		
		
		$this -> assign('list',$resinfo);
		$this -> display();	
	}

	public function sureone(){
		//提交信息确认
		$data = I('data');
		$two = explode(",", $data['price']);
		$data['price'] = $two[0];
		$data['typeid'] = $two[1];
		$data['costname'] = $two[2];
		$data['imei'] = str_replace(' ','',$data['imei']);
		$data['phone'] = str_replace(' ','',$data['phone']);
		//关于支付部分
		$openid = session('openid');
	    if(!$this->checkSubscribe($openid)){//防止分享访问
	        exit('请从公众号内链接访问');
	    }
		if($openid == null){
			exit('等待时间超时，请重新进入解锁吧下单!');
		}		
		$price = $data['price']*100;
		$title = $data['costname'];
		$gopay_js = $this -> gopay($openid,$price,$title);
		$this -> assign('payjs',$gopay_js['result']);
		$this -> assign('openid',$openid);
		$this -> assign('ordernumber',$gopay_js['out_trade_no']);
		
		//关于支付部分
		
		
		$this -> assign('data',$data);
		$this -> display();
		
		
	}
//微信支付
//=======================================================================================	
	/*支付请求ji调起支付页面
	 * @param
	 * 
	 * */
	public function gopay($openid,$money,$title){
	    Vendor('WeiXinPay.WxPayApi');
	    Vendor('WeiXinPay.JsApiPay');
	    $inputObj= new \WxPayUnifiedOrder();
	    $input=new \WxPayApi();
	    $jstool=new \JsApiPay();
	    $random=$input->getNonceStr();
	    $inputObj->SetBody($title);
	    $inputObj->SetTotal_fee($money);
	    $inputObj->SetOut_trade_no($random);
	    $inputObj->SetTrade_type("JSAPI");
	    $inputObj->SetOpenid($openid);
	    $inputObj->SetNotify_url("http://jiesuoba.com/index.php/Home/Jiesuoba/Notify");
	    $result= $input->unifiedOrder($inputObj);
	    $result=$jstool->GetJsApiParameters($result);
    //保存用户预支付数据
	    $sign=json_decode($result,true);
	    $data['out_trade_no']=$random;
	    $data['openid']=$openid;
	    $data['add_time']=time();
	    M('pre_paylog')->data($data)->add();
		
	    $bak['result']=$result;//页面使用的js代码;
	    $bak['out_trade_no']=$random;//商户订单号
	    return $bak;		
		
	}
	
	
	/**
	 * 异步接收支付结果-微信请求接口
	 */	
	 
	 public function Notify(){
	    header('Content-Type: text/html; charset=utf-8');
	    $timezone = "Asia/Shanghai";
	    date_default_timezone_set($timezone); //北京时间
	    $msg = array();
	    $msg = (array) simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA);
	    
	    extract($msg);//数组转换成变量
	    if($result_code == 'SUCCESS'){
           // 更新你的数据库--验证签名
	        $where['openid'] = $openid;
	        $where['out_trade_no'] = $out_trade_no;
	        $is_pre=M('pre_paylog') -> where($where)->find();
	        $is_pay=M('paylog') -> where($where)->find();
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
				$data['module'] = "2";
	            M('paylog')->data($data)->add();
	        }
	        echo "success";	
	    }	 	
		
		
	 }
	
//微信支付end	==============================================================


//支付成功
	public function successres(){
		$data['out_trade_no'] = I('order');		
		$data['openid'] = I('openid'); 	
		$data['IMEI'] = I('imei'); 		
		$data['phone'] = I('phone'); 		
		$data['wechat'] = I('wechat'); 		
		$where['out_trade_no'] = $data['out_trade_no'];
		$realpay = $this -> paylog -> where($where)
								   -> field('total_fee') 
								   -> select();
		$realpay = $realpay[0]['total_fee'] / 100;
		
		$data['realpay'] = $realpay;
		$data['cost_type'] = I('costtype'); 	
		$data['add_time'] = time();	
		$data['state'] = 0;
		$res = $this -> orderlist -> data($data) -> add();
		if($res){
			//推购买成功的消息			
			$openid = $data['openid'];
			$template_id = 'nM7fw7dvPltihdlXjanu7cVo_MLASkBRdcJ6_rkyjmU';
			$where['id'] = $data['cost_type'];
			$costname = $this -> costtype 
							  -> where($where) 
							  -> select();
			$name = $costname[0]['costname'];
			$time = date('Y/m/d H:i:s' ,$data['add_time']);
			$remark = "备注：我们会尽快处理,如有疑问,可致电18575605887联系客服处理。交易时间：".$time;
			$datas = array(
				'productType' => array(
					'value' => '套餐类型',
				),
				'name' => array(
					'value' => $name,
					"color" => "#09BB07"  
				
				
				),
	            'number'=> array(
	                'value'=> '1',
	                "color"=> "#09BB07"
	            ),
	            'remark'=> array(
	                'value'=> $remark,
	                "color"=> "#A3A3A3",
	            ),	            
				
				
				
			);
			$this -> sendTplMsg($openid,$template_id,'http://jiesuoba.com/index.php/Home/Jiesuoba/seeorder',$datas);
		}
		echo json_encode($res);		
	}
//支付成功跳转
	public function successurl(){
	    $jsdk=$this->getJsdk();
	    $this->assign('jsdk',$jsdk);		
		
		$this -> display();
	}


//验证当前商户号是否支付成功
	public function checknumber(){
		$number = I('order');
		$where['out_trade_no']=$number;
		$res = M('paylog') -> where($where) -> find();
		
		echo json_encode($res);
		
		
	}


//订单查询 -我的订单
	public function seeorder(){
		
	    if(session('openid') == null){
	   		$userInfo = $this -> getUserInfo();
			$openid = $userInfo['openid'];      //凭证id
			if($openid){
				//将$opinid存在时 存入session中
				session('openid',$openid);
			
			}
		}
		$openid = session('openid');
// 		$openid = 'og0I1s9LEoIdmAPukrKVGFI6S_1M';
		$where['openid'] = $openid;
		$res = $this -> orderlist 
					 -> where($where)
					 -> order('add_time desc')
					 -> select();
		foreach($res as $k => $v){
			$resu = $this -> costtype
						  -> where('id ='.$v['cost_type'])
						  -> field('costname , costprice')
						  -> select();
			$res[$k]['costname'] = $resu[0]['costname'];
			$res[$k]['costprice'] = $resu[0]['costprice'];
			$res[$k]['add_time'] = date('Y/m/d H:i:s' , $res[$k]['add_time']);
			
		}
		$fix_list = D('Cate')->getOrderList($openid);
		
// 		var_dump($fix_list);exit;
		$this -> assign('fix_list', $fix_list);
		$this -> assign('list' , $res);
		
		$this -> display();
	}

////订单查询 -我的订单 -订单详情
	public function orderdetail(){
	    $jsdk=$this->getJsdk();   //单击关闭微信浏览器用
	    $this->assign('jsdk',$jsdk);	
		$id = I('id');
		$where['id'] = $id;
		$res = $this -> orderlist 
					 -> where($where)
					 -> select();	
		unset($where);
		$res[0]['add_time'] = date('Y/m/d H:i:s' , $res[0]['add_time']);
		$where['id'] = $res[0]['cost_type'];
		$resu = $this -> costtype
					  -> where($where)
					  -> field('costname')
					  -> select();
		$res[0]['costname'] = $resu[0]['costname'];					
//		var_dump($res);
		$keyituikuan=2;
		      $v=$res[0];
		    if($v['isrefund']==0&&$v['state']!=1){
		        $keyituikuan=1;
		    }
		    if($v['state']==0){
		        $keyituikuan=1;
		    }
		    if($v['state']==2){
		        $keyituikuan=1;
		    }
		    if($v['isrefund']==3){
		        $keyituikuan=2;
		    }
		
		$this->assign('keyituikuan',$keyituikuan);
		$this -> assign('data' , $res);		
		$this -> display();		
	}
////订单查询 -我的订单 -申请退款手机验证
		public function totk(){
			$id = I('id');
			$where['id'] = $id;
			$res = $this -> orderlist 
						 -> where($where)
						 -> select();	
			$data['phone'] = $res[0]['phone'];
			$data['id']    = $res[0]['id'];
			$this -> assign('data',$data);			
			$this -> display();
		}
//发送验证码
		public function sendcode(){
			Vendor('bigfish.TopSdk');  
			$phone = I('phone');
			$code = I('code');
			session('code',$code);
			$val['code'] = $code;
			$val['product'] = "退款申请手机";
			$val = json_encode($val);
			$appkey = '23324106';
			$secret = '91e21fe4fa0ce09f15e7a5a993e086fa';
			$c = new \TopClient; 
			$c->appkey = $appkey;
			$c->secretKey = $secret;
			$req = new \AlibabaAliqinFcSmsNumSendRequest;
			$req->setExtend($phone);     //公共回传参数
			$req->setSmsType("normal");
			$req->setSmsFreeSignName("修好乐");   //短信签名
			$req->setSmsParam($val);              //短信模板变量，传参规则{"key":"value"}
			$req->setRecNum($phone);           //短信接收号码。
			$req->setSmsTemplateCode("SMS_5635261");   //短信模板ID
			$resp = $c->execute($req);	
			$getres = json_encode($resp);
			$success = $resp->result->success;   

//将短信记录存进数据库
			$data['openid'] = I('session.openid',"");			
			$data['phone']  = $phone;
			$data['body']   = "【修好乐】 验证码".$code."，您正在进行退款申请手机身份验证，打死不要告诉别人哦！";
			$data['res']    = $getres;
			$data['add_time'] = time();
			$data['type'] = "退款验证";
			if($sucess){
				$data['issuccess'] = 1;
			}else{
				$data['issuccess'] = 0;				
			}
			
			$this -> smsdetails -> data($data) -> add();		
			echo json_encode($success);
			
		}

// 校验验证码是否输入正确
		public function checkcode(){
			$codes = I('codes');
			$code = session('code');
			if($codes === $code){
				echo true;
				
			}else{
				echo false;
			}
			
			
			
			
		}
//进入退款页面
		public function yestk(){
			$id = I(id);
			$where['id'] = $id;			
			$res = $this -> orderlist 
						 -> where($where)
						 -> select();	
		 	unset($where);
			
			$datas['price'] = $res[0]['realpay'];
			$datas['imei'] = $res[0]['imei'];
			$datas['id'] = $res[0]['id'];
//			var_dump($datas);
			$this -> assign('datas',$datas);
						 
					
			$this -> display();

							   
							   
		}
//退款进数据库

	public function tkinfo(){
		$id = I('id');
		$data['zhifubao'] = I('zhifubao');
		$data['tkname'] = I('tkname');				
		$data['tksq_time'] = time();
		$data['isrefund'] = 1;
		$result = $this -> orderlist -> where('id ='.$id)
						  			 -> data($data)
									 -> save();
		if($result){
		    $jsdk=$this->getJsdk();
		    $this->assign('jsdk',$jsdk);		
		}				
		echo json_encode($result);	
//		var_dump($data);
//		$this -> display();
	}
//tiaozhuan
	public function successurls(){
		
	    $jsdk=$this->getJsdk();
	    $this->assign('jsdk',$jsdk);	
		$this -> display();
	}
	
	
	
//====================================================	
}
