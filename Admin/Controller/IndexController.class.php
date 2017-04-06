<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends BaseController 
{
    public function __construct(){
        parent::__construct();
        $this->user_info=M('user_info');
    }
    public function index(){
        if (IS_POST) {
            $buget = I('data');
            M('youkuvip')->where('id = 1')->data($buget)->save();
        }
        $buget = M('youkuvip')->where('id = 1')->find();
        $this->assign('buget', $buget);
        $this->display();
    }
    public function login(){
        if(IS_POST){
            if(I('user')!=null){
                $user=I('user');
                $where['user_name']=$user['user_name'];
                $where['pass_word']=$user['pass_word'];
                $result=$this->user_info->where($where)->find();
               if($result){
                   session("user_name",$result["user_name"]);
                   session("user_id",$result["id"]);
                   $this->success('登陆成功',U('Admin/Index/index'));
               }else{
                   $this->error('登陆失败',U('Admin/Index/login'));
               }
            }
        }else{
            $user=session("user_name");
            if(!empty($user))
            {
                $this->error("你已经登陆了，不需要重复登陆",U("Admin/Index/index"));
            }
        } 
        $this->display();
        
    }
    public function loginOut(){
        session("user_name",null);
        $this->success('再见。。。思密达！！！',U('Admin/Index/login'));
    }
}