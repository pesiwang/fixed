<?php
namespace Admin\Controller;
use Think\Controller;
use Think\AdminPage;
class OrderfixedController extends BaseController 
{
	public function __construct(){
		parent::__construct();
		$this->cate=M('cate');
		$this->cate_brand=M('cate_brand');
		$this->cate_child=M('cate_child');
		$this->cate_prbm=M('cate_prbm');
		$this->cate_prbm_detail=M('cate_prbm_detail');
	    $this->cate_prbm_solution = M('cate_prbm_solution');
	    $this->cate_prbm_solution_fee = M('cate_prbm_solution_fee');
	    $this->cate_prbm_solution_order = M('cate_prbm_solution_order');
	    $this->wechat_userinfo = M('wechat_userinfo');
	    $this->p = D('Home/Cate');
	}
	
	public function orderList()
	{
	    
	    
	    $where = ' is_del = 1';
	    
	    $stus = I('stus');
	    if (!empty($stus) AND is_numeric($stus)) {
	        $where .= " and stus = {$stus} ";
	    }
	    
	    $search = I('search');
	    if (!empty($search)) {
	        $where .=" and ( phone like '%$search%' or order_id like '%$search%' or user_name like '%$search%') ";
	    }
	    
	    $p=I('p')==null?1:I('p');
	    
	    $pageSize=15;
	    $count=$this->cate_prbm_solution_order->where($where)->count();
	   
	    $page=new AdminPage($count,$pageSize);
	    
	    $list = $this->cate_prbm_solution_order->page($p.','.$pageSize)->where($where)->field('id, user_name, phone, total_fee, model, prbm_list, stus, add_time, is_opreate')->select();
	    
	    $this->assign('list',$list);
	    $this->assign('pagelink',$page->show());
	    $this->display();
	}
	
	public function orderdetail()
	{
	    $id = I('id');
	    $order_detail = $this->cate_prbm_solution_order->where('id ='.$id)->find();
	    
	    $profile = $this->wechat_userinfo->where("openid = '{$order_detail['openid']}'")->find();
	  
	    $fee_detail = $this->p->countFee($order_detail['model'], explode(",",$order_detail['prbm_list']));
	    
	    $this->assign('fee_detail', $fee_detail['list']);
	    $this->assign('order_detail', $order_detail);
	    $this->assign('profile', $profile);
	    $this->display();
	}
	
	public function updateorder()
	{
	    $data = I('post.');
	    $data['is_opreate'] = 1;
	    $result = $this->cate_prbm_solution_order->data($data)->save();
	    
	    $ret['code'] = 1;$ret['msg'] = '修改失败';
	    if ($result) {
	        $ret['code'] = 0;$ret['msg'] = '修改成功';
	    }
	    
	    $order = $this->cate_prbm_solution_order->where('id='.$data['id'])->find();
	    
	    if ($data['stus'] == 4){
	        $this->tellArrived($order['openid'], $order['order_id']);
	    }
	    
	    if ($data['stus'] == 5){
	        $this->tellFinish($order['openid'], $order['order_id'], $order['package_name_mg'], $order['package_no_mg'], $order['model']);
	    }
	    
	    if ($data['stus'] == 999){
	        $this->tellClose($order['openid'], $order['order_id']);
	    }
	        
	    
	    $this->ajaxReturn($ret);
	}
	
	
	
	private function tellArrived($openid, $order_id)
	{
	        $touser = $openid;
	        $template_id = 'ZlnN1zefARD2I_QijlgWATe_QUncfTZKtC5wCutGUR8';
	        $url =  $this->thisSiteUrl . U('Home/Jiesuoba/seeorder');
	        $data = array(
	            'first' => array(
	                'value' => '我们已经收到了您的手机！',
	                "color" => "#173177"
	            ),
	            'keyword1' => array(
	                'value' => $order_id,
	                "color" => "#2C12FF"
	            ),
	            'keyword2' => array(
	                'value' => date("Y-m-d H:i:s",time()),
	                "color" => "#173177"
	            ),
	            'keyword3' => array(
	                'value' => '珠海维修中心',
	                "color" => "#173177"
	            ),
	            'remark' => array(
	                'value' => '24小时内将会进行全面检测并联系您确认维修方案，请保持电话畅通！',
	                "color" => "#173177"
	            ),
	        );
	        $this->sendTplMsg($openid, $template_id, $url, $data);
	}
	
	private function tellFinish($openid, $order_id, $package_name_mg, $package_no_mg, $model)
	{
	    $touser = $openid;
	    $template_id = '_JoIEf5Tzk5HILJFMnnK2NwTQmyPZnVG2bhLIy2bAMs';
	    $url = $this->thisSiteUrl . U('Home/Jiesuoba/seeorder');
	    $data = array(
	        'first' => array(
	            'value' => "您的手机已完成维修，满血复活！",
	            "color" => "#173177"
	        ),
	        'keyword1' => array(
	            'value' => $package_name_mg,
	            "color" => "#2C12FF"
	        ),
	        'keyword2' => array(
	            'value' => $package_no_mg,
	            "color" => "#173177"
	        ),
	        'keyword3' => array(
	            'value' => $model,
	            "color" => "#173177"
	        ),
	        'keyword4' => array(
	            'value' => '1',
	            "color" => "#173177"
	        ),
	        'remark' => array(
	            'value' => "我们安排了{$package_name_mg}镖局护送它回到您身边，请查看运单号，并耐心等待！",
	            "color" => "#173177"
	        ),
	    );
	    $this->sendTplMsg($openid, $template_id, $url, $data);
	}
	
	private function tellClose($openid, $order_id)
	{
	    $touser = $openid;
	    $template_id = 'Z-dY1T9AUn1ID7M5VArLvxBzjKzvu1DlNZZBoBLJMQc';
	    $url =  $this->thisSiteUrl . U('Home/Jiesuoba/seeorder');
	    $data = array(
	        'first' => array(
	            'value' => "您的交易已取消，订单已关闭！",
	            "color" => "#173177"
	                ),
	                'keyword1' => array(
	                    'value' => '您提交的订单我们暂时无法给您处理',
	                    "color" => "#2C12FF"
	                ),
	                'keyword2' => array(
	                    'value' => '用户填写的信息不合法或问题不存在',
	                    "color" => "#173177"
	                ),
	                'keyword3' => array(
	                    'value' => date("Y-m-d H:i:s"),
	                    "color" => "#173177"
	                ),
	                'remark' => array(
	                    'value' => '如需维修，请重新下单，感谢您的使用',
	                    "color" => "#173177"
	                ),
	        );
	    $this->sendTplMsg($openid, $template_id, $url, $data);
	}
	
	/**
	 * 类目列表
	 */
	public function item(){
		$list=$this->cate->order('id desc')->select();
		$this->assign('list',$list); 
		$this->display(); 
	} 
	/**
	 * 编辑类目
	 */
	public function edit(){
		if(IS_POST)
		{
			$data=I('data');
			if(isset($data['id'])&&$data['id']!=''){
				$res=$this->cate->data($data)->save();
			}else{
				$res=$this->cate->data($data)->add();
			}
			if($res){
				$this->success('操作成功',U('Admin/Orderfixed/item'));
			}else{
				$this->error('操作失败',U('Admin/Orderfixed/item'));
			}
			return;
		}
		$where['id']=I('id');
		$item=$this->cate->where($where)->find();
		$this->assign('item',$item);
		$this->display();
	}
	/**
	 * 删除类目
	 */
	public function del(){
		$where['id']=I('id');
		$item=$this->cate->where($where)->delete();
		$bak['code']=1;
		if($item){
			$bak['code']=0;
		}
		$this->ajaxReturn($bak);
	}
	/**
	 * 编辑品牌
	 */
	public function editbrand(){
		if(IS_POST)
		{
			$data=I('data');
			if(isset($data['id'])&&$data['id']!=''){
				$res=$this->cate_brand->data($data)->save();
			}else{
				$res=$this->cate_brand->data($data)->add();
			}
			if($res){
				$this->success('操作成功',U('Admin/Orderfixed/listbrand',I('get.')));
			}else{
				$this->error('操作失败',U('Admin/Orderfixed/listbrand',I('get.')));
			}
			return;
		}
		$where['id']=I('nav2_id');
		$item=$this->cate_brand->where($where)->find();
		unset($where);$where['parent']=I('nav2_id');
		$list=$this->cate_child->where($where)->select();

		$this->assign('item',$item);
		$this->assign('list',$list);
		$this->display();
	}
	/**
	 * 品牌列表
	 */
	public function listbrand(){
		$where['parent']=I('nav1_id');
		$list=$this->cate_brand->where($where)->order('id desc')->select();
		$this->assign('list',$list);
		$this->display();
	}
	/**
	 * 删除品牌及其子项
	 */
	public function delbrand(){
		$where['id']=I('id');
		$this->cate_brand->where($where)->delete();
		unset($where);$where['parent']=I('id');
		$this->cate_child->where($where)->delete();
		$bak['code']=0;$bak['msg']='完成';
		$this->ajaxReturn($bak);
	}
	
	/*删除手机型号*/
	public function delchild()
	{
	    $where['id']=I('id');
	    $this->cate_child->where($where)->delete();
	    $bak['code']=0;$bak['msg']='删除完成';
	    $this->ajaxReturn($bak);
	}
	
	/**
	 * 添加/修改手机-型号
	 */
	public function editchild_ajax(){
		$data=I('data');
		$data['color']=implode('|',$data['color']);
		//保存
		if(isset($data['id'])&&$data['id']!=''){
			$res=$this->cate_child->data($data)->save();
		}else{
			$res=$this->cate_child->data($data)->add();
			$field=array('name','color');
		}
		//添加        
		$jbk['code']=1;$jbk['msg']='失败';
		if($res){
			$jbk['code']=0;$jbk['msg']='操作成功';
		}
		$this->ajaxReturn($jbk);exit;
	}
	/**
	 * 拉取型号-颜色
	 */
	public function echochild_ajax(){
		if(I('id')!=null){
			$where['id']=I('id');
			$type=$this->cate_child->where($where)->find();
			if($type){$type['code']=0;$type['color']=explode('|',$type['color']);}
			$this->ajaxReturn($type);
		}
	}
	/**
	 * 大概故障-列表
	 */
	public function editprbm(){
		$where['parent']=I('nav3_id');
		$list=$this->cate_prbm->where($where)->select();
		$arr=$this->cate_prbm->where($where)->getField('prbm',true);
// 		        var_dump($arr);exit;
		$this->assign('list',$list);
		$this->assign('arr',$arr);
		$this->display();
	}
	
	/**
	 * 大概故障-编辑
	 */
	public function editprbm_ajax(){
		$data=I('data');
		$field=array('parent','prbm');
		$arr=$this->cate_prbm->where('parent='.$data['parent'])->getField('prbm',true);

		foreach ($data['prbm'] as $v){
			$d['prbm']=$v;$d['parent']=$data['parent'];
			if(!in_array($v,$arr))$this->cate_prbm->data($d)->add();
		}
		$arr=$this->cate_prbm->where('parent='.$data['parent'])->getField('prbm',true);
		$diff=array_diff($arr,$data['prbm']);
		foreach ($diff as $v){
			$where['parent']=$data['parent'];
			$where['prbm']=$v;
			$this->cate_prbm->where($where)->delete();
		}

		$jbk['code']=0;$jbk['msg']='操作成功';
		$this->ajaxReturn($jbk);exit;

	}
	
	public function echoprbmdetail_ajax()
	{
	    $where['parent'] = I('prbm');
	    $detail= $this->cate_prbm_detail->where($where)->find()['detail'];
	    $detail = explode("|",$detail);
	    $list = array();
	    
	    //添加默认解决方案
	    foreach ($detail as $k => $v)
	    {
	        $list[$k]['detail'] = $v;
	        $list[$k]['solution'] = $this->cate_prbm_solution->where("parent ='{$v}'")->find()['name'];
	    }
	    
	    $list = $this->get_fee_by_model_detail(I('parent'), I('prbm'), $list);
	    
	    if (!$list) {
	        $res['code'] = 1; $res['msg'] = "信息不存在";
	        $this->ajaxReturn($res);
	    }
	    
	    $res['list'] = $list;
	    $res['code'] = 0;
	    $this->ajaxReturn($res);
	}
	
	/*通过型号id和故障父类获取费用详情*/
	private function get_fee_by_model_detail($model, $detail_parent, $list)
	{
	   $where['model'] = $model;
	   $where['detail_parent'] = $detail_parent;
	   
	   foreach ($list as &$v){
	       $where['detail'] = $v['detail'];
	       $where['solution'] = $v['solution'];
	       $fee = $this->cate_prbm_solution_fee->where($where)->find();
	       if (!empty($fee['fee'])) {
	           $v['fee'] = $fee['fee'];
	       }else{
	           $v['fee'] = 0;
	       }
	   }
	   
	   return $list;
	}
	
	public function edit_soulution()
	{
	    $where = I('where');
	    $list = I('list');
	    
	    if (empty($where['model']) OR !is_numeric($where['model'])) {
	        $ret['code'] = 1;$ret['msg'] = '缺少型号ID';
	        $this->ajaxReturn($ret);
	    }
	    
	    if (empty($where['model_name'])) {
	        $ret['code'] = 2;$ret['msg'] = '缺少型号名称';
	        $this->ajaxReturn($ret);
	    }
	    
	    if (empty($where['detail_parent'])) {
	        $ret['code'] = 3;$ret['msg'] = '缺少故障父类';
	        $this->ajaxReturn($ret);
	    }
	    
	    $result = $this->cate_prbm_solution_fee->where($where)->delete();
	    
	    $detail = "";
	    foreach ($list as &$v)
	    {
	        $v['model'] = $where['model'];
	        $v['model_name'] = $where['model_name'];
	        $v['detail_parent'] = $where['detail_parent'];
	        $result = $this->cate_prbm_solution_fee->data($v)->add();
	        if (!$result) {
	            $ret['code'] = 4;$ret['msg'] = $v['detail'].':保存失败';
	            $this->ajaxReturn($ret);
	        }
	        $detail[] =  $v['detail'];
	    }
	    
	    $detail_data['detail'] = implode("|", $detail);
	    $where_prbm = array(
	        'parent' => $where['model'],
	        'prbm' => $where['detail_parent']
	    );
	    $this->cate_prbm->where($where_prbm)->save($detail_data);
	    $ret['code'] = 0;$ret['msg'] = '保存成功';
	    $this->ajaxReturn($ret);
	}
	
	public function  editprbmdetail_ajax(){
		$data=I('data');
		$where['prbm']=I('prbm');
		$where['parent']=I('parent');
		$data['detail']=implode('|',$data);
		$res=$this->cate_prbm->where($where)->data($data)->save();
		if($res){$jbk['code']=0;$jbk['msg']=操作成功;}else{$jbk['code']=1;$jbk['msg']=操作失败;}
		$this->ajaxReturn($jbk);
	}
	public function tgp_post(){
		$this->ajaxReturn(I('post.'));
	}
}
