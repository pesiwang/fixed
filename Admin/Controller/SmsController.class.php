<?php   
namespace Admin\Controller;
use Think\Controller;
class SmsController extends  BaseController{
	public function __construct(){
		parent::__construct();		
		$this -> smsdetails = M('smsdetails');
		
	}	
	
//短信记录列表
	public function details(){
		if(I('get.type') == 'search'){
			//搜索
			$condition = I('post.condition');
			$where['phone'] = $condition;
			
		}else if(I('get.type') == 'success'){
			//已发货
			$where['issuccess'] = 1;
		}else if(I('get.type') == 'error'){
			//未发货
			$where['issuccess'] = 0;
		}
		//该条件下的订单个数
		$number =  $this -> smsdetails
						 -> where($where)
						 -> count();
		//实例化分页类
		$size = 10;
		$page = new \Think\Page($number , $size);
		$page -> rollPage = 4;
		$page -> setConfig('first' ,'【首页】');
		$page -> setConfig('last' ,'【尾页】');
		$page -> setConfig('prev' ,'【上一页】');
		$page -> setConfig('next' ,'【下一页】');
		$start = $page -> firstRow;
		$pagesize = $page -> listRows;
		$resinfo = $this -> smsdetails
						 -> where($where)
						 -> limit("$start , $pagesize")
						 -> order('add_time desc') 
						 -> select();
		
		foreach ($resinfo as $k => $v) {
			$resinfo[$k]['add_time']  = date('Y/m/d H:i' , $resinfo[$k]['add_time']);
		}
		$this -> assign('list' , $resinfo);	
		$pagestr = $page -> show();  //组装分页字符串
		$this -> assign('pagestr',$pagestr);	
		$pagesm['pagenumber'] = ceil($number/$size);
		$pagesm['number'] = $number;
		$pagesm['nowpage'] = ceil($start/$size)+1;
		$this -> assign('pagesm',$pagesm);
		
		$this -> display();
	}
	
	
	
}
