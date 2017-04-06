<?php
namespace Admin\Controller;
use Think\Controller;
use Org\WeiXin\Curl;
class MyController extends Controller
{
    public function youkuvip(){
        set_time_limit(0);
		$curl =new Curl();
		$list=M('youkuvip')->where('(last_time+86400) < unix_timestamp(now())')->limit(100)->select();
        foreach($list as $v){
             $res=$curl->rapid($v['url']);
             $data['log']=$res;
             $data['last_time']=date(time());
             $res=json_decode($res,true);
             if($res['meta']['msg']=="ok"){
                 $data['count']=$v['count']+1;
             }
             M('youkuvip')->where('id='.$v['id'])->data($data)->save();
        } 
    }
    public function syn(){
	    $curl=new Curl();
	    $res=$curl->rapid("http://zengbingo.applinzi.com/Home/Index/syn");
	    $res=json_decode($res,true);
	    foreach($res as $v){
		    unset($v['syn']);unset($v['id']);
		    M('youkuvip')->data($v)->add();
	    }
    }

}
