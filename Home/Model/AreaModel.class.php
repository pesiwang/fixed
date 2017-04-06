<?php
namespace Home\Model;

use Think\Model;

class AreaModel extends Model
{

    public function __construct()
    {
        parent::__construct();
        $this->area = M('area');
    }

    public function province()
    {
        $where = " code like '%0000'  ";
        $res =  $this->area->where($where)->select();
        return $res;
    }
    
    public function city($city_code)
    {
        $where = " code  like '$city_code%'  and code like '%00'  ";
        $res =  $this->area->where($where)->select();
        return $res;
    }
    
    public function county($county_code)
    {
        $where = "code like '$county_code%' "  ;
        $res =  $this->area->where($where)->select();
        return $res;
    }
    
    public function getNameBycode($code)
    {
        $where['code'] =  $code;
        $res =  $this->area->where($where)->find();
        return $res['name'];
    }
}