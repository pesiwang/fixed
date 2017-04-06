<?php
namespace Home\Controller;

class PhonefixedController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->wechat_userinfo=M('wechat_userinfo');
        
        $this->cate = M('cate');
        $this->cate_brand = M('cate_brand');
        $this->cate_child = M('cate_child');
        $this->cate_prbm = M('cate_prbm');
        $this->cate_prbm_detail = M('cate_prbm_detail');
        $this->p = D('Cate');
        $this->area = D('Area');
        
        $this->assign('color_id',I('color_id'));
        $this->assign('color',I('color'));
        $this->assign('prbm',I('prbm'));
        $this->assign("model_color", I('model_color'));
        $this->assign("prbm_list", I('prbm_list'));
        $this->assign('comments', I('comments'));
    }

    public function test()
    {
        $openid = 'og0I1s9LEoIdmAPukrKVGFI6S_1M';
        $canSet['model'] = 'iphone6';
        $canSet['prbm_list']  = '大大说的';
            $tmpl = D('Tell')->CompletionPostPackage_user($canSet['model'], $canSet['prbm_list']);
            $this->sendTplMsg($openid, $tmpl['template_id'], $tmpl['url'], $tmpl['data']);
        
    }
    public function index()
    {
       // $this->wx_init();
        $this->display();
    }

   public function t() {
	var_dump($_SERVER);
}
    public function about()
    {
        $this->display();
    }
    
    public function comprbm()
    {
        $this->display();
    }
    
    public function trace()
    {
        $this->display();
    }
    
    public function item()
    {
        $this->display();
    }
    
    public function child()
    {
        $this->wx_init();
        $this->display();
    }
    
    /*提交订单前*/
    public function color()
    {
        $this->wx_init();
        $brand_id = I('brand_id');
        $this->assign('brand_id', $brand_id);
        $this->display();
    }
    
    /*提交订单前*/
    public function prbm()
    {
        $this->wx_init();
        $color_id = I('color_id');
        $this->assign('color_id',$color_id);
        $this->display();
    }
    
    public function detail()
    {
        $this->wx_init();
        $order_id = I('order_id');
        $this->assign('order_id', $order_id);
        $this->display();
    }
    
    public function waitpay()
    {
        $this->wx_init();
        $order_id = I('order_id');
        
        $openid = session('openid');//'og0I1s9LEoIdmAPukrKVGFI6S_1M';//
        
        $order = $this->p->getOrder($order_id, $openid);
        
        if (!$order) {
            exit('订单不存在');
        }
        
        $order['solution_list'] = $this->p->getSolutionList($order['prbm_list'], $order['model']);

        $this->assign('order', $order);
        $this->assign('openid', $openid);
        $this->assign('order_id', $order_id);
        $this->display();
    }
    
    public function order()
    {
        $this->wx_init();
        $data = I('post.');
        
        foreach ($data['address'] as  $k => &$v)
        {
            if (  $k < 3  AND (empty($v) OR  ($v < 10000))) {
                exit('不合法的收获地址');
            } else if(empty($v)){
                exit('不合法的详细收获地址');
            }
            $v = trim($v);
            if (is_numeric($v)) {
                $v = $this->area->getNameBycode($v);
            }
        }
        
        if  (empty($data['user_name'])) {
            exit('用户名不能为空');
        }
        
        if (empty($data['phone'])  OR !is_numeric($data['phone']))
        {
            exit('手机号码不合法');
        }
        
        if (!empty($data['way'])  
            AND $data['way'] == "indoor" 
            AND empty($data['indoor_time']))
        {
            exit('错误的上门时间');
        }
        
        if(empty($data['way']) 
            OR  ($data['way'] != 'post'  AND $data['way'] != "indoor" ))
        {
            exit('错误的维修方式');
        }
        
        $model_color = explode("--", $data['model']);
        
        $data['model'] = $model_color[0];
        $data['color'] = $model_color[1];
        $data['openid'] = session('openid');
        
        $ret = $this->p->addOrder($data);
        
        if ($ret) {
            $href = U('waitpay' ,array('order_id' => $ret));
            echo "<script>window.location.href='$href';</script>";
        }else{
            echo '提交失败';
        }
    }
    
    public function after()
    {
        $this->wx_init();
        $this->display();
    }
    
    public function before()
    {
        $this->wx_init();
        $this->display();
    }
    
    public function set_package_no()
    {
        $order_id = I('order_id');
        $package_no = I('package_no');
        $package_company = I('package_company');
        
        if (!isset($order_id) OR empty($order_id) OR !is_numeric($order_id)) {
            $ret['code'] = 100;$ret['msg'] = '缺少订单id';
            $this->ajaxReturn($ret);
        }
        
        $openid = session('openid');//'og0I1s9LEoIdmAPukrKVGFI6S_1M';//
        
        if (empty($openid)) {
            $ret['code'] = 110; $ret['msg'] = '请刷新页面后重试！';
            $this->ajaxReturn($ret);
        }
        
        $order = $this->p->getOrder($order_id, $openid);
        
        if (!$order) {
            $ret['code'] = 120; $ret['msg'] = '订单信息不存在';
            $this->ajaxReturn($ret);
        }
        
        $canSet = $this->p->cantSetPackageNo($order_id, $openid);
        
        if (!$canSet) {
            $ret['code'] = 121; $ret['msg'] = '未支付，或该订单的运单号已经填写过了';
            $this->ajaxReturn($ret);
        }
        
        $result = $this->p->setUserPackageNo($order_id, $openid, $package_no, $package_company);
        
        if ($result) {
            $tmpl = D('Tell')->CompletionPostPackage_user($canSet['model'], $canSet['prbm_list']);
            $this->sendTplMsg($openid, $tmpl['template_id'], $tmpl['url'], $tmpl['data']);
            
            $ret['code'] = 0; $ret['msg'] = '设置成功';$ret['package_no'] = $package_no;
            $this->ajaxReturn($ret);
        }
        
        $ret['code'] = 130; $ret['msg'] = '保存失败';
        $this->ajaxReturn($ret);
    }
    
    public function order_detail_by_id()
    {
        $order_id = I('order_id');
        
        if (!isset($order_id) OR empty($order_id) OR !is_numeric($order_id)) {
            $ret['code'] = 100;$ret['msg'] = '缺少订单id'; 
            $this->ajaxReturn($ret);
        }
        
        $openid = session('openid');//'og0I1s9LEoIdmAPukrKVGFI6S_1M';//
        
        if (empty($openid)) {
            $ret['code'] = 110; $ret['msg'] = '请刷新页面后重试！';
            $this->ajaxReturn($ret);
        }
        
        $order = $this->p->getOrder($order_id, $openid);
        
        if (!$order) {
            $ret['code'] = 120; $ret['msg'] = '订单信息不存在';
            $this->ajaxReturn($ret);
        }
        
        $order['add_time'] = date('Y/m/d H:i:s',$order['add_time']);
        unset($order['is_del']);
        unset($order['is_opreate']);
        unset($order['mg_comments']);
        unset($order['openid']);
        
        $ret['code'] = 0;
        $ret['data'] = $order;
        $this->ajaxReturn($ret);
    }
    
    public function count_fee()
    {
        $data['model_color'] = I('model_color');
        $data['prbm_list'] = I('prbm_list');
        
        $data['model'] = explode("--", $data['model_color']);
        $data['prbm_list'] = explode(",", $data['prbm_list']);
        
        $ret = $this->p->countFee($data['model'][0], $data['prbm_list']);
        $this->ajaxReturn($ret);
    }
    
    public function phone_prbm()
    {
        $color_id = I('color_id');
        
        if (empty($color_id) OR !is_numeric($color_id)){
            $ret['code'] = 1; $ret['msg'] = "id不合法" ;
            $this->ajaxReturn($ret);
        }
        
        $result = $this->p->phone_prbm($color_id);
        
        if (empty($result)) {
            $ret['code'] = 2; $ret['msg'] = "数据为空" ; $ret['list'] = array();
            $this->ajaxReturn($ret);
        }
        
        $model_name = $this->p->get_model_by_id($color_id);
        
        if (empty($model_name)) {
            $ret['code'] = 2; $ret['msg'] = "数据为空" ; $ret['list'] = array();
            $this->ajaxReturn($ret);
        }
        
        
        $ret['code'] = 0; $ret['msg'] = "success" ;
        $ret['list'] = $result;
        $ret['model_name'] = $model_name['name'];
        
        $this->ajaxReturn($ret);
    }
    
    public function phone_type_color()
    {
        $brand_id = I('brand_id');
        
        if (empty($brand_id) OR !is_numeric($brand_id)) {
            $ret['code'] = 1; $ret['msg'] = "id不合法" ;
            $this->ajaxReturn($ret);
        }
        
        $result = $this->p->phone_type_color($brand_id);
        
        if (empty($result)) {
            $ret['code'] = 2; $ret['msg'] = "数据为空" ; $ret['list'] = array();
            $this->ajaxReturn($ret);
        }
        
        $ret['code'] = 0; $ret['msg'] = "success" ; 
        $ret['list'] = $result;
        
        $this->ajaxReturn($ret);
    }
    
    /**
     * 拉取详细故障
     */
    public function echoprbmdetail_ajax()
    {
        $where['parent'] = I('prbm');
        $res1 = $this->cate_prbm_detail->where($where)->find()['detail'];
        $res = explode("|", $res1);
        unset($where);
        $where['parent'] = I('parent');
        $where['prbm'] = I('prbm');
        $prbm1 = $this->cate_prbm->where($where)->find()['detail'];
        $prbm = explode("|", $prbm1);
        $prbm_detail = array();
        foreach ($res as $k => $v) {
            if (! in_array($v, $prbm)) {
                array_push($prbm_detail, $v);
            }
        }
        if ($prbm1 && $res1) {
            $jbk['data'] = $prbm_detail;
            $jbk['hdata'] = $prbm;
            $jbk['code'] = 0;
        } else {
            $jbk['msg'] = '该数据为空，请联系开发人员添加';
        }
        $this->ajaxReturn($jbk);
    }

    /**
     * 返回品牌列表
     */
    public function getBrandList()
    {
        $cate = $this->cate->select();
        $bak['code'] = 1;
        $bak['msg'] = '数据链接失败';
        if (! $cate)
            $this->ajaxReturn($bak);
        foreach ($cate as &$v) {
            $where['parent'] = $v['id'];
            $brand = $this->cate_brand->where($where)->select();
            $v['brand'] = $brand;
        }
        $bak['code'] = 0;
        $bak['msg'] = 'success';
        $bak['data'] = $cate;
        $this->ajaxReturn($bak);
    }

    /**
     * 获取型号列表
     */
    public function getChildLList()
    {
        $where['parent'] = I('id');
        $child = $this->cate_child->where($where)->select();
        $bak['code'] = 1;
        $bak['msg'] = "该品牌下数据为空";
        if ($child) {
            foreach ($child as &$v) {
                $v['color'] = explode('|', $v['color']);
            }
            $bak['code'] = 0;
            $bak['msg'] = "success";
            $bak['data'] = $child;
        }
        $this->ajaxReturn($bak);
    }

    /**
     * 获取故障列表
     */
    public function getPrbmList()
    {
        $where['parent'] = I('id');
        $prbm = $this->cate_prbm->where($where)->select();
        $bak['code'] = 1;
        $bak['msg'] = '该型号下数据为空';
        if ($prbm) {
            foreach ($prbm as &$v) {
                $v['detail'] = explode("|", $v['detail']);
            }
            $bak['code'] = 0;
            $bak['msg'] = "success";
            $bak['data'] = $prbm;
        }
        $this->ajaxReturn($bak);
    }

    /**
     * 检查字段完整性-ajax
     */
    public function check_field($arr, $field)
    {
        foreach ($field as $v) {
            if (! isset($arr[$v]) || $arr[$v] == '') {
                $jbk['code'] = 2;
                $jbk['msg'] = '缺少:' . $v;
                $this->ajaxReturn($jbk);
                exit();
            }
        }
    }
    
    public function get_pronvince()
    {
        $list = $this->area->province();
        $ret['list'] = $list;
        $ret['code'] = 0;
        $this->ajaxReturn($ret);
    }
    
    public function get_city()
    {
        $city_code = I('city_code');
        $city_code = trim($city_code);
        $city_code = substr($city_code, 0 , 2);
        if (empty($city_code) OR !is_numeric($city_code)){
        $ret['code'] = 1; $ret['msg'] = '变量不合法';     
        $this->ajaxReturn($ret);
        }
        
        $list = $this->area->city($city_code);
        
        $ret['code'] = 0;
        $ret['list'] = $list;
        $this->ajaxReturn($ret);
    }
    
    public function get_county()
    {
        $county_code = I('county_code');
        $county_code = trim($county_code);
        $county_code = substr($county_code, 0 , 4);
        if (empty($county_code) OR !is_numeric($county_code)){
            $ret['code'] = 1; $ret['msg'] = '变量不合法';
            $this->ajaxReturn($ret);
        }
        
        $list = $this->area->county($county_code);
        
        $ret['code'] = 0;
        $ret['list'] = $list;
        $this->ajaxReturn($ret);
    }
}
