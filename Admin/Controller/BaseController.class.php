<?php
namespace Admin\Controller;
use Think\Controller;
use Org\WeiXin\Curl;
class BaseController extends Controller 
{
    public $token;  //全局token
	public $thisSiteUrl; //站点URL	    
    public $appid='wxc555f6097f8be8d6';
    public $appSecret='5aa0f01b2c2421e5bdb94578b035d3bf';    	
    public function __construct(){
        parent::__construct();
        $user=session("user_name");
        if(ACTION_NAME!='tgp_post'){
        if(empty($user)&&ACTION_NAME!=login)
        {
            $this->error("似乎遇到了问题，重新登陆吧",U("Admin/Index/login"));
        }
        else
        {
            session("user",$user);
        }
        }
		

		
        $this->curl=new Curl();
        $baseToken=M('token');
        $where="id=1";
        $usingToken=$baseToken->where($where)->find();
        $this->thisSiteUrl='http://'.$_SERVER['HTTP_HOST'];
       if($usingToken['lasttime']<(time()-6500)){
           //如果不是最新的token,就更新数据库的token 
           $newToken=$this->curl->rapid("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->appSecret}");
           $newToken=json_decode($newToken,true);
           $newToken=$newToken['access_token'];
           $baseToken->lasttime=time();
           $baseToken->token=$newToken;
           $baseToken->where($where)->save();
           $this->token=$newToken;
       }else{
           $this->token=$usingToken['token'];
       }		
    }
	
	//推模板消息
    public function sendTplMsg($touser,$template_id,$url,$data){
        $totalDate=array(
            'touser'=>$touser,
            'template_id'=>$template_id,
            'url'=>$url,
            'data'=>$data
        );
        $totalDate=json_encode($totalDate);
        $this->curl->rapid("https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$this->token}",
            'POST',
            $totalDate);
    }	
	
	
}
