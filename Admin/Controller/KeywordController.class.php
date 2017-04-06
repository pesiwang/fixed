<?php
namespace Admin\Controller;
use Think\Controller;
use Org\WeiXin\WeiXinPay;
class KeywordController extends BaseController 
{
    public function __construct(){
        parent::__construct();
        $this->keyword=M('keyword');
    }
    public function index(){
        if(I('get.handle')=='search'){
            $keyword=I('post.keyword');
            $where['keyword']=array('LIKE','%'.$keyword.'%');
        }
        $klist=$this->keyword->where($where)->order('add_time desc')->select();
        $this->assign('list',$klist);
        $this->display();
    }
    public function addoredit(){
    if(IS_POST){
        if('keyword'==""){//防止提交空关键词
            $this->error('关键词不能为空',U('Admin/keyword/index'));
        }
        if(I('data')['id']==""){//添加
        $data=I('data');
        $this->checkKeyword($data['keyword']);
        $data['add_time']=time();
        $result=$this->keyword->data($data)->add();
        if($result){
            $this->success('添加成功',U('Admin/keyword/add'));
        }else{
            $this->error('添加失败,看看关键词是不是重复啦',U('Admin/keyword/index'));
        }
      }
      if(I('data')['id']!=""){//修改
          $data=I('data');
          $this->checkKeyword($data['keyword'],$data['id']);
          $result=$this->keyword->data($data)->save();
          if($result){
              $this->success('修改成功',U('Admin/keyword/index'));
          }else{
              $this->error('修改失败,看看关键词是不是重复啦',U('Admin/keyword/index'));
          }
      }
    }
     else{
           if(I('id')!=null){
               $where['id']=I('id');
               $keyword=$this->keyword->where($where)->find();
           }
           $this->assign('keyword',$keyword);
        $this->display();
       }
    }
    public function del(){
        $id=I('id');
        $where['id']=$id;
        $data['is_del']=2;
        $result=$this->keyword->where($where)->data($data)->save();
        if($result){
            $this->success('修改成功',U('Admin/keyword/index'));
        }else{
            $this->error('修改失败',U('Admin/keyword/index'));
        }
    }
    public function recover(){
        $id=I('id');
        $where['id']=$id;
        $data['is_del']=1;
        $result=$this->keyword->where($where)->data($data)->save();
        if($result){
            $this->success('修改成功',U('Admin/keyword/index'));
        }else{
            $this->error('修改失败',U('Admin/keyword/index'));
        }
    }
    /**
     * 编辑菜单栏按钮回复消息
     */
    public function answer(){
        $where['id']=1;
        if(IS_POST){
            $data['content']=I('content');
            $result=M('answer')->where($where)->data($data)->save();
            if($result){
                $this->success('修改成功',U('Admin/keyword/answer'));exit();
            }else{
                $this->error('修改失败',U('Admin/keyword/answer'));exit();
            }
        }
        $content=M('answer')->where($where)->find();
        $this->assign('content',$content['content']);
        $this->display();
    }
    /**
     * 检验关键词是否重复
     * @param  $keyword
     * @param  $id 关键词的ID
     */
    public function checkKeyword($keyword,$id='null'){
        $where['keyword']=$keyword;
        if($id!='null'){
            $where['id']=array('neq',$id);
        }
        $result=$this->keyword->where($where)->find();
        if($result){
            $this->success('关键词重复',U('Admin/keyword/index'));exit();
        }
    }
    /**
     * 查保修用户可修改项目
     */
    public function checkimei(){
        $this->check_fixed=M('check_fixed');
        $where['id']=1;
        if(IS_POST){
            $data=I('data');
            $result=$this->check_fixed->where($where)->data($data)->save();
            if($result){
                $this->success('修改成功',U('Admin/keyword/checkimei'));exit();
            }else{
                $this->error('修改失败',U('Admin/keyword/checkimei'));exit();
            }
        }
        $rule=$this->check_fixed->where($where)->find();
        $this->assign('rule',$rule);
        $this->display();
    }
    public function shwoQcode(){
        $wxp=new WeiXinPay();
        $pdata=$wxp->getPayQcode("0.01", "13231");
        $this->assign("pdata",$pdata);
        $this->display("qq");
    }
}
