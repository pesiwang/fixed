<?php
namespace Home\Controller;
use Think\Controller;
use Com\Wechat;
use Org\WeiXin\Curl;
class MenuController extends BaseController {
    public function createMenu(){
        $curl=new Curl();
        $indexurl=urlencode("http://zengbingo.com/Mobile/Index/index");
        $data=array('button'=>array(
            array(
                'name'=>'我要维修',
                'sub_button'=>array(
                    array(
                        'name'=>'预约上门',
                        'type'=>'view',
                        'url'=>'http://www.xiuhl.com/Home/Menu/tel'
                    ),
                    array(
                        'type'=>'view',
                        'name'=>'全国寄修',
                        'url'=>'http://www.xiuhl.com/Home/Phonefixed/index'
                    ),
                    array(
                        'name'=>'IMEI服务',
                        'type'=>'view',
                        'url'=>'http://www.xiuhl.com/Home/Jiesuoba/explain'
                    ),
                ),
            ),array(
                'name'=>'客户服务',
                'sub_button'=>array(
                    array(
                        'name'=>'人工服务',
                        'type'=>'view',
                        'url'=>'http://wpa.qq.com/msgrd?v=3&uin=2597168833&site=www.xiuhaole.com&menu=yes'
                    ),
                    array(
                        'name'=>'一键呼叫',
                        'type'=>'view',
                        'url'=>'http://www.xiuhl.com/Home/Menu/tel'
                    ),
                    array(
                        'name'=>'常见问题',
                        'type'=>'view',
                        'url'=>'http://www.xiuhl.com/Home/Phonefixed/comprbm'
                    ),
                 ),
            ),array(
                'name'=>'用户中心',
                'sub_button'=>array(
                    array(
                        'type'=>'view',
                        'name'=>'公司简介',
                        'url'=>'https://mp.weixin.qq.com/s/yYZeSxK7E1XWWBR0SYbqkw'
                    ),
                    array(
                        'type'=>'view',
                        'name'=>'关于我们',
                        'url'=>'http://www.xiuhl.com/Home/Phonefixed/about'
                    ),
                    array(
                        'type'=>'view',
                        'name'=>'我的订单',
                        'url'=>'http://www.xiuhl.com/Home/Jiesuoba/seeorder'
                    ),
                    array(
                        'type'=>'view',
                        'name'=>'粉丝社区',
                        'url'=>'http://buluo.qq.com/p/barindex.html?bid=351417'
                    ),
                    array(
                        'type'=>'view',
                        'name'=>'服务条款',
                        'url'=>'http://www.xiuhl.com/Home/Phonefixed/item'
                    ),
                ),
            ),
         ));
        $data=json_encode($data,JSON_UNESCAPED_UNICODE);
		var_dump($this->token);
        echo $data;
        $del=$curl->rapid("https://api.weixin.qq.com/cgi-bin/menu/delete?access_token={$this->token}");
       echo '删除结果：'.$del;
        $result= $curl->rapid("https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$this->token}",'POST',$data);
       echo '创建结果'.$result;
    }
    
	public function get() {
        $curl=new Curl();
		echo $this->token;
		$get = $curl->rapid("https://api.weixin.qq.com/cgi-bin/menu/get?access_token={$this->token}");
		echo $get;
	}

    public function tel()
    {
        $this->display();
    }
    
}
