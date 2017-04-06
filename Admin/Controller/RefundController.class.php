<?php  
namespace Admin\Controller;
use Think\Controller;
class RefundController extends BaseController{
	public function __construct(){
		parent::__construct();		
		$this -> costtype = M('costtype');
		$this -> orderlist = M('orderlist');
		
	}
	
	//退款订单页面信息
	public function tuikuanlist(){
		
		$id = I('id');
		$resinfo = $this -> orderlist 
						 -> where('id ='.$id)
						 -> select();
		$resinfo[0]['tksq_time'] = date('Y-m-d H:i:s',$resinfo[0]['tksq_time']);							  
		$this -> assign('data',$resinfo);
		$this -> display();
	}
	
	
	//退款订单页面-处理结果
	public function dealtk(){
		$id = I('id');
		$status = I('status');
		$data['isrefund'] = $status;
		$data['deai_time'] = time(); 
		$res = $this -> orderlist
					  -> where('id ='.$id)
					  -> data($data)
					  -> save();
		if($res){
			$res = $this -> orderlist -> where('id ='.$id)
									  -> select();
			//推发货成功消息到个人
			$openid = $res[0]['openid'];   //openid
			$where['id'] = $res[0]['cost_type'];
			$resu = $this -> costtype 
							  -> where($where) 
							  -> select();	
			$first = "业务处理结果通知:";
			$keyword1 = date('Y/m/d H:i:s' ,$data['deai_time']);
			$keyword2 = $resu[0]['costname'];
			$keyword3 = $res[0]['imei']."(IMEI)";
			if($status == 2){
				//拒绝退款
				$keyword4 = "退款申请被驳回!";
				
			}else if($status == 3){
				//同意退款
				$keyword4 = "退款申请处理成功，请查收!";				
			}			
			
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
			
			
			
		}
		
		
		echo json_encode($res);		
		
	}
	
	
}