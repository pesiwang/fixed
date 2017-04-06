<?php  
namespace Admin\Controller;
use Think\Controller;
class OrderController extends BaseController{
	public function __construct(){
		parent::__construct();		
		$this -> costtype = M('costtype');
		$this -> orderlist = M('orderlist');
		$this -> smsdetails = M('smsdetails');		
	}
	
	//订单设置页面
	public function index(){
		$resinfo = $this -> costtype
						 -> where('isdelete = 0') 
						 -> order('id desc')
						 -> select();
		foreach ($resinfo as $k => $v) {
			
			$resinfo[$k]['add_time'] = date('Y/m/d H:i' , $resinfo[$k]['add_time']);
			
		}
		$this -> assign('list',$resinfo);		
		$this -> display();
		
	}
	
	//订单设置页面---添加费用类型/修改费用类型
	public function edit(){
		if(IS_POST){
	        if(I('data')['costname'] == "" || I('data')['costprice'] == ""){
	        	//提交空数据
	            $this -> error('费用类型名称或费用类型价格不能为空' , U('Admin/Order/edit'));
	        }				
			$id = I('data')['id'];
			if($id){
				//修改费用类型
				$data = I('data');
				$data['add_time'] = time();
				$res = $this -> costtype -> data($data)
										 -> save();	
	            if($res){
	              $this -> success('修改成功!' , U('Admin/Order/index'));
	            }else{
	              $this -> error('修改失败,检查费用的名字是否重复!' , U('Admin/Order/edit'));
	            }										 			
				
			}else{
				//添加费用类型
				$data = I('data');
				$this -> checkcostname($data['costname']); 
				$data['add_time'] = time(); 
				$res = $this -> costtype -> data($data) -> add();
				if($res){
					
					$this -> success('添加成功!', U('Admin/Order/index'));
				}else{
					
					$this -> error('添加失败，检查费用的名字是否重复!' , U('Admin/Order/index'));
				}
				
			}
			
			
			
		}else{
			
			if(I('id') != null){
				//修改费用类型原始数据
				$id = I('id');
				$resinfo = $this -> costtype 
								 -> where('id ='.$id)
								 -> select();
			}
			if($resinfo){
				
				$this -> assign('old',$resinfo);
			}
			$this -> display();	
		}
	}
	
	//订单设置页面---删除费用类型
	public function delCT(){
		if(I('id')){
			
			$id = I('id');
			$data['isdelete'] = 1;
			$data['add_time'] = time();
			$res = $this -> costtype
						 -> where('id ='.$id)
						 -> data($data)
						 -> save();
			echo json_encode($res);			  
			
		}
		
		
		
		
	}
	
	
    //校验费用类型名是否存在
    public function checkcostname($name){
        $where['costname'] = $name;
        $result = $this -> costtype -> where($where) -> find();
        if($result){
            $this -> success('添加失败,费用的名字重复!' , U('Admin/Order/index'));
            exit();
        }
    }
	
	//订单列表页面
	public function detail(){
		if(I('get.type') == 'search'){
			//搜索
			$condition = I('post.condition');
			$where['phone'] = $condition;
			$where['IMEI']	= $condition;
			$where['out_trade_no'] = $condition;
			$where['_logic']= 'OR';	
			
		}else if(I('get.type') == 'yfahuo'){
			//已发货
			$where['state'] = 1;
		}else if(I('get.type') == 'nfahuo'){
			//未发货
			$where['state'] = 0;
		}else if(I('get.type') == 'tksq'){
			//申请退款
			$where['isrefund'] = 1;
		}else if(I('get.type') == 'tkcg'){
			//退款成功
			$where['isrefund'] = 3;
		}else if(I('get.type') == 'jjtk'){
			//拒绝退款
			$where['isrefund'] = 2;
		}
		//该条件下的订单个数
		if($where['_logic']){
			$map['_complex'] = $where;
			$map['isdelete'] = 0;
			
		}else{
			$where['isdelete'] = 0; 
		}
		$number =  $this -> orderlist
						 -> where($where)
						 -> count();
		//实例化分页类
		$size = 5;
		$page = new \Think\Page($number , $size);
		$page -> rollPage = 4;
		$page -> setConfig('first' ,'【首页】');
		$page -> setConfig('last' ,'【尾页】');
		$page -> setConfig('prev' ,'【上一页】');
		$page -> setConfig('next' ,'【下一页】');
		$start = $page -> firstRow;
		$pagesize = $page -> listRows;
		$resinfo = $this -> orderlist
						 -> where($where)
						 -> limit("$start , $pagesize")
						 -> order('add_time desc') 
						 -> select();
		
		foreach ($resinfo as $k => $v) {
			$res = $this -> costtype
						 -> where('id ='.$v['cost_type'])
						 -> select();
			$resinfo[$k]['add_time']  = date('Y/m/d H:i' , $resinfo[$k]['add_time']);
			$resinfo[$k]['costname']  = $res[0]['costname'];
			$resinfo[$k]['costprice'] = $res[0]['costprice'];
		}
//		var_dump($resinfo);
		$this -> assign('list' , $resinfo);	
		$pagestr = $page -> show();  //组装分页字符串
		$this -> assign('pagestr',$pagestr);	
		$pagesm['pagenumber'] = ceil($number/$size);
		$pagesm['number'] = $number;
		$pagesm['nowpage'] = ceil($start/$size)+1;
		$this -> assign('pagesm',$pagesm);
		
		$this -> display();		
		
	}
	
	
	//发货
	public function send(){
		$id = I('id');
		$datafh['deal_time'] = time();
		$datafh['state'] = 1;
		$fhres = $this -> orderlist 
					   -> where('id ='.$id)
					   -> data($datafh)
					   -> save();
		if($fhres){
			$res = $this -> orderlist -> where('id ='.$id)
									  -> select();
			$phone = $res[0]['phone'];     //该订单客户手机号
			
//推发货成功消息到个人
			$openid = $res[0]['openid'];   //openid
			$where['id'] = $res[0]['cost_type'];
			$resu = $this -> costtype 
							  -> where($where) 
							  -> select();	
			$first = "业务处理结果通知:";
			$keyword1 = date('Y/m/d H:i:s' ,time());
			$keyword2 = $resu[0]['costname'];
			$keyword3 = $res[0]['imei']."(IMEI)";
			$keyword4 = "处理成功";
			$remark = "如有疑问,可致电18575605887联系客服处理。";
			$template_id = '5zrNstgDfLS3u7sZ8b8ooseDfR2Av67i5FSNOMv_kPI';
			$data = array(
				'first' => array(
					'value' => $first,
					"color" => '#2C12FF',
				),
				'keyword1' => array(
					'value' => $keyword1,
					"color" => '#a3a3a3',
				),	
				'keyword2' => array(
					'value' => $keyword2,
	                "color"=> "#A3A3A3",				
				),
				'keyword3' => array(
					'value' => $keyword3,
	                "color"=> "#A3A3A3",				
				),
				'keyword4' => array(
					'value' => $keyword4,
					"color" => '#09BB07',
				),
				'remark' => array(
					'value' => $remark,
	                "color"=> "#A3A3A3",
				),								
				
			
			);
			
			
			$this -> sendTplMsg($openid,$template_id,'http://jiesuoba.com/index.php/Home/Jiesuoba/seeorder',$data);
			unset($where);
//将结果通知发短信给相应用户
			Vendor('bigfish.TopSdk'); 
			$where['openid'] = $openid;
			$nickname = M('wechat_userinfo') -> where($where)
											 -> field('nickname')
								 			 -> select();
			$nickname = $nickname[0]['nickname'];
			$val['name'] = $nickname;
			$val['product'] = $keyword2;
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
			$req->setSmsTemplateCode("SMS_5895449");   //短信模板ID
			$resp = $c->execute($req);	
			$getres = json_encode($resp);
			$success = $resp->result->success;  
			
//将短信记录存进数据库
			$data['openid'] = $openid;			
			$data['phone']  = $phone;
			$data['body']   = "【修好乐】 亲爱的".$nickname."，您购买的".$keyword2."服务已成功完成，请在24小时之内确认并激活使用！如有疑问请联系微信客服，祝您生活愉快！";
			$data['res']    = $getres;
			$data['add_time'] = time();
			$data['type'] = "发货通知";
			if($sucess){
				$data['issuccess'] = 1;
			}else{
				$data['issuccess'] = 0;				
			}
			
			$this -> smsdetails -> data($data) -> add();				 
		}
		echo $fhres;
		
		
		
	}
	public function delorder(){
		
		$id = I('id');
		$data['isdelete'] = 1;
		$res = $this -> orderlist -> where('id ='.$id)
								  -> data($data)
								  -> save();
		echo json_encode($res);
		
	}
//解说吧-业务说明编辑	
	public function explain(){
	    $this->orderexplain=M('orderexplain');
	    $where['id']=1;
		if(IS_POST){
		    $data['content']=I('content');
		    $res=$this->orderexplain->where($where)->data($data)->save();
		    if($res){
		        $this -> success('修改成功!', U('Admin/Order/explain'));
		    }else{
		        	
		        $this -> error('修改失败!' , U('Admin/Order/explain'));
		    }
		}
		$tpl=$this->orderexplain->where($where)->getField("content");
		$tpl=html_entity_decode($tpl);
		$this->assign('tpl',$tpl);
		$this -> display();
	}
	
	
//--------------------------------------------------	
	//发货
	public function jujue(){
		$id = I('id');
		$datafh['deal_time'] = time();
		$datafh['state'] = 2;
		$fhres = $this -> orderlist 
					   -> where('id ='.$id)
					   -> data($datafh)
					   -> save();
		if($fhres){
			$res = $this -> orderlist -> where('id ='.$id)
									  -> select();
			
//推发货成功消息到个人
			$openid = $res[0]['openid'];   //openid
			$where['id'] = $res[0]['cost_type'];
			$resu = $this -> costtype 
							  -> where($where) 
							  -> select();	
			$first = "业务处理结果通知:";
			$keyword1 = date('Y/m/d H:i:s' ,time());
			$keyword2 = $resu[0]['costname'];
			$keyword3 = $res[0]['imei']."(IMEI)";
			$keyword4 = "处理失败，请去申请退款！";
			$remark = "如有疑问,可致电18575605887联系客服处理。";
			$template_id = '5zrNstgDfLS3u7sZ8b8ooseDfR2Av67i5FSNOMv_kPI';
			$data = array(
				'first' => array(
					'value' => $first,
					"color" => '#2C12FF',
				),
				'keyword1' => array(
					'value' => $keyword1,
					"color" => '#a3a3a3',
				),	
				'keyword2' => array(
					'value' => $keyword2,
	                "color"=> "#A3A3A3",				
				),
				'keyword3' => array(
					'value' => $keyword3,
	                "color"=> "#A3A3A3",				
				),
				'keyword4' => array(
					'value' => $keyword4,
					"color" => '#09BB07',
				),
				'remark' => array(
					'value' => $remark,
	                "color"=> "#A3A3A3",
				),								
				
			
			);
			
			
			$this -> sendTplMsg($openid,$template_id,'http://jiesuoba.com/index.php/Home/Jiesuoba/seeorder',$data);
		echo $fhres;
		
		
		
	}

}



//=======================
}
