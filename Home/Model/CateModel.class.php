<?php
namespace Home\Model;

use Think\Model;

class CateModel extends Model
{

    public function __construct()
    {
        parent::__construct();
        $this->cate = M('cate');
        $this->cate_brand = M('cate_brand');
        $this->cate_child = M('cate_child');
        $this->cate_prbm = M('cate_prbm');
        $this->cate_prbm_detail = M('cate_prbm_detail');
        $this->cate_prbm_solution_fee = M('cate_prbm_solution_fee');
        $this->cate_prbm_solution_order = M('cate_prbm_solution_order');
    }

    public function getNameA($a)
    {
        $where = array(
            'id' => $a
        );
        return $this->cate_bran->where($where)->find()['name'];
    }

    public function getNameB($b)
    {
        $where = array(
            'id' => $b
        );
        return $this->cate_child->where($where)->find()['name'];
    }

    /* 检测手机型号是否存在 */
    public function chekc_p_model($name)
    {
        $where = array(
            'name' => $name
        );
        return $this->cate_child->where($where)->find();
    }

    public function phone_type_color($brand)
    {
        $where = array(
            'parent' => $brand
        );
        
        $type_list = $this->cate_child->where($where)->select();
        
        foreach ($type_list as &$v) {
            $v['color'] = explode("|", $v['color']);
        }
        return $type_list;
    }

    public function phone_prbm($color)
    {
        $where = array(
            'parent' => $color
        );
        
        $prbm_list = $this->cate_prbm->where($where)->select();
        
        foreach ($prbm_list as &$v) {
            $v['detail'] = explode("|", $v['detail']);
        }
        
        return $prbm_list;
    }

    public function get_model_by_id($color_id)
    {
        $where = array(
            'id' => $color_id
        );
        
        $model = $this->cate_child->where($where)->find();
        return $model;
    }
    
    /* 检测手机颜色是否存在 */
    public function check_color_model($name, $color)
    {
        $where = array(
            'name' => $name
        );
        $phone = $this->cate_child->where($where)->find();
        
        if (! $phone) {
            return false;
        }
        
        $phone['color'] = explode('|', $phone['color']);
        
        if (in_array($color, $phone['color'])) {
            return true;
        }
        
        return false;
    }

    /* 检测手机问题是否存在 */
    public function check_prbm_model($name, $prbm)
    {
        $where = array(
            'name' => $name
        );
        $phone = $this->cate_child->where($where)->find();
        
        $phone_id = $phone['id'];
        
        unset($where);
        
        $where = array(
            'parent' => $phone_id,
            'detail' => array(
                'like',
                '%' . $prbm . '%'
            )
        );
        
        return $this->cate_prbm->where($where)->select();
    }
    
    public function countFee($model, $prbm_list)
    {
        $where = " model_name = '$model' " ;
        if (!empty($prbm_list)){
            $where .=  " and ( ";
            foreach ($prbm_list as $k => $v)
            {
                if ($k == 0){
                    $where .= "detail = '$v' " ;
                }else{
                    $where .="or detail = '$v' " ;
                }
            }
            $where .=" ) ";
        }
        
        $fee['total'] = $this->cate_prbm_solution_fee->where($where)->sum('fee');
        $fee['list'] = $this->cate_prbm_solution_fee->where($where)->select();
        return $fee;
    }
    
    public function addOrder($data)
    {
        $f = array(
            'order_id' =>'none',
            'user_name' => $data['user_name'],
            'phone' => $data['phone'],
            'address' => $data['address'][0].$data['address'][1].$data['address'][2].$data['address'][3],
            'prbm_list' =>$data['prbmlist'],
            'total_fee' => 0,
            'model' => $data['model'],
            'color' => $data['color'],
            'comments' => $data['comments'],
            'way' => $data['way'],
            'indoor_time' => $data['indoor_time'],
            'discount_number' => $data['discount_number'],
            'stus' => 1,
            'is_opreate' => 2,
            'add_time' => time(),
            'is_del' => 1,
            'openid' => $data['openid'],
        );
        
        $f['order_id'] = $this->getOrderId();
        
        $f['total_fee'] = $this->countFee($f['model'], explode(",", $f['prbm_list']));
        $f['total_fee'] = $f['total_fee']['total'];
        
        $f['total_fee'] = $this->doDiscount($f['discount_number'], $f['total_fee']);
        
        $id = $this->cate_prbm_solution_order->data($f)->add();
        
        if ($id) {
            $order = $this->cate_prbm_solution_order->where('id='.$id)->find();
            return $order['order_id'];
        }
        return $id;
    }
    
    
    public function getOrderList($openid)
    {
        $where = array(
            'openid' => $openid,
            'is_del' => 1
        );
        
        return $this->cate_prbm_solution_order->where($where)->select();
    }
    
    private function doDiscount($discount_number, $total_fee)
    {
        if ($discount_number == 'xiuhaole1234') {
            $total_fee = 0.01;
        }
        return $total_fee;
    }
    
    public function getOrder($order_id, $openid)
    {
        $where['order_id'] = $order_id;
        $where['openid'] = $openid;
        $ret = $this->cate_prbm_solution_order->where($where)->find();
        return $ret;
    }
    
    public function isPayOrder($order_id, $openid, $money)
    {
        $where['order_id'] = $order_id;
        $where['openid'] = $openid;
        $where['stus'] = 1;
        $where['total_fee'] = $money;
        $ret = $this->cate_prbm_solution_order->where($where)->find();
        return $ret;
    }
    
    public function paySuccess($order_id, $openid)
    {
        $where['order_id'] = $order_id;
        $where['openid'] = $openid;
        $data['stus'] = 2;
        $data['pay_time'] = time();
        $this->setWaitOpreate($order_id, $openid);
        
        return $this->cate_prbm_solution_order->where($where)->data($data)->save();
    }
    
    public function setWaitOpreate($order_id, $openid)
    {
        $where['order_id'] = $order_id;
        $where['openid'] = $openid;
        $data['is_opreate'] = 2;
        return $this->cate_prbm_solution_order->where($where)->data($data)->save();
    }
    
    public function setUserPackageNo($order_id, $openid, $package_no,$package_company)
    {
        $where['order_id'] = $order_id;
        $where['openid'] = $openid;
        $data['package_no_user'] = $package_no;
        $data['package_name_user'] = $package_company;
        $result = $this->cate_prbm_solution_order->where($where)->data($data)->save();
        if ($result) {
            $this->changeStatus($order_id, $openid, 3);//3、已寄送
        }
        return  $result;
    }
    
    public function cantSetPackageNo($order_id, $openid)
    {
        $where['order_id'] = $order_id;
        $where['openid'] = $openid;
        $order = $this->cate_prbm_solution_order->where($where)->find();
        if (empty($order['pay_time']) OR !empty($order['package_no_user'])) {
            return false;
        }
        return $order;
    }
    
    public function changeStatus($order_id, $openid, $stus)
    {
        $where['order_id'] = $order_id;
        $where['openid'] = $openid;
        $data['stus'] = $stus;
        return $this->cate_prbm_solution_order->where($where)->data($data)->save();
    }
    
    public function getSolutionList($prbm_list, $model)
    {
        $prbm_list = explode(',', $prbm_list); 
        $solution_list = '';
        foreach ($prbm_list as $v)
        {
            $tmp = $this->getSolution($v, $model);
            $solution_list .= "{$tmp['solution']}({$tmp['fee']}), ";
        }
        return $solution_list;
    }
    
    private function getSolution($prbm, $model)
    {
        $where['detail'] = $prbm;
        $where['model_name'] = $model;
        $name = $this->cate_prbm_solution_fee->field('solution,fee')->where($where)->find();
        return $name;
    }
    
    private function getOrderId()
    {
        $orderid =  date("YmdHis",time());
        $orderid .= rand(10000,99999);
        return $orderid;
    }
}