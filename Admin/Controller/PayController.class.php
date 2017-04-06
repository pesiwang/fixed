<?php
namespace Admin\Controller;
use Think\Controller;
use Think\AdminPage;
class PayController extends BaseController 
{
    public function __construct(){
        parent::__construct();
        $this->paylog=M('paylog');
    }
    public function index(){
        if(IS_POST){
            $keyword=I('keyword');
            if(preg_match_all("/^[\d]*$/i",$keyword)){
                $where['transaction_id']=$keyword;
            }else{
                $where['out_trade_no']=$keyword;
            }
        }
        $join=" we_wechat_userinfo u on p.openid=u.openid";
        $field="p.out_trade_no,p.transaction_id,p.time_end,p.module,p.total_fee,u.nickname,u.headimgurl";
        $p=I('p')==null?1:I('p');
        $pageSize=15;
        $count=$this->paylog->count();
        $page=new AdminPage($count,$pageSize);
        $list=$this->paylog->alias('p')->field($field)->join($join)->page($p.','.$pageSize)->where($where)->order('p.time_end desc')->select();
        $this->assign('list',$list);
        $this->assign('pagelink',$page->show());
        $this->display();
    }
}