<?php
namespace Home\Controller;

use Think\Controller;
use Com\Wechat;
use Org\WeiXin\TuRing;
use Home;
use Org\WeiXin\Curl;

class AutoreturnController extends BaseController
{

    public $siteUrl = 'http://www.xiuhl.com/';

    public function __construct()
    {
        parent::__construct();
        $this->keyword = M('keyword');
        $this->wechat_userinfo = M('wechat_userinfo');
    }

    public function index()
    {
        ob_clean();
        $token = 'xiuhaole';
        $wechat = new Wechat($token);
        $data = $wechat->request();
        $type = 'text';
        if ($data && is_array($data)) {
            $this->saveopenid($data['FromUserName']); // 保存用户信息
            if ($data['Event'] == 'subscribe') {
                $content = $this->subReturnContent();
                $wechat->response($content, $type);
            }
            if ($data['Event'] == 'unsubscribe') {
                $this->unsubscribe($data['FromUserName']);
                exit();
            }
            if ($data['Event'] == 'user_consume_card') {
                $this->sendVerify($data);
                exit();
            }
            if ($data['Event'] == 'user_get_card') {
                $wechat->response("你领取的那么快啊。。", $type);
            }
            // 菜单点击事件-
            if ($data['Event'] == 'CLICK') {
                if ($data['EventKey'] == 'CJC') {
                    $wechat->response($this->cjcAnswer(), $type);
                }
                if ($data['EventKey'] == 'CBX') {
                    $content = ' 欢迎使用修好乐查保修系统，请直接在对话框输入您要查询的手机序列号或IMEI（在设置-通用-关于本机里可以查看）';
                    $wechat->response($content, $type);
                }
                if ($data['EventKey'] == 'LXKF') {
                    $content = '修好乐:回复"联系客服"可联系在线客服,其他联系方式:
微信:2597168833
Q  Q:2597168866
邮件:2597168833@qq.com
商务合作:18575605887 联系客服处理.
PS:修好乐客服工作时间: 周一~周六,早9:00到晚9:00;周日休息.';
                    $wechat->response($content, $type);
                }
            }
            // 菜单点击事件--
            // 添加关键词推送模板消息- **************************************
            $content = $data['Content'];
            $where['keyword'] = $content;
            $where['is_del'] = 1;
            $tpl = $this->keyword->where($where)->find();
            if ($tpl) {
                $this->keyword->where($where)->setInc('counter');
                $tpl['picurl'] = $this->siteUrl . $tpl['picurl'];
                $wechat->replyNewsOnce($tpl['tittle'], $tpl['discription'], $tpl['baseurl'], $tpl['picurl']);
            }
            // 添加关键词推送模板消息--end **************************************
            // 客服消息
            if ($content == '联系客服') {
                $this->serviceReturn($data['FromUserName'], '客服人员接入中...请稍等');
                $wechat->transmitToService();
            }
            if ($content == '悄悄的把那东西发给我') {
                $this->sendWxCard($data['FromUserName'], "pg0I1s-9C3iXpyBfqh5mowctbXPU");
                exit();
            }
            // 客服消息-end
            // 处理用户发送的sn码
            if (preg_match_all("/^[\w]{8,}$/i", $content)) {
                $tpl = M('check_fixed')->where('id=1')->find();
                $tpl['picurl'] = $this->siteUrl . $tpl['picurl'];
                $url = $this->siteUrl . "Home/Checkfixed/checkFixed?sn=$content&timestap=" . time();
                $wechat->replyNewsOnce($tpl['tittle'], $tpl['discription'], $url, $tpl['picurl']);
            }
            // 处理用户发送的sn码-end
            // 图灵处理====
            $turing = new TuRing();
            $content = $turing->getanswer($content);
            $content = json_decode($content, true);
            $content = $content['text'] . '
' . $content['url'];
            $wechat->response($content, $type);
            // 图灵处理====end
        }
    }

    /**
     * 编辑菜单栏按钮回复消息
     */
    public function cjcAnswer()
    {
        $where['id'] = 1;
        $content = M('answer')->where($where)->getField('content');
        return $content;
    }

    /**
     * 保存用户信息
     * 
     * @param unknown $openid            
     */
    public function saveopenid($openid)
    {
        $curl = new Curl();
        $isset = $this->wechat_userinfo->where(array(
            'openid' => $openid
        ))->find();
        if (! $isset) {
            $userinfo = $curl->rapid("https://api.weixin.qq.com/cgi-bin/user/info?access_token={$this->token}&openid={$openid}&lang=zh_CN");
            $userinfo = json_decode($userinfo, true);
            $this->wechat_userinfo->data($userinfo)->add();
        }
        $unsubscribe = $this->wechat_userinfo->where(array(
            'openid' => $openid,
            'subscribe' => 2
        ))->find();
        if ($unsubscribe) {
            $data['subscribe'] = 1;
            $this->wechat_userinfo->where(array(
                'openid' => $openid,
                'subscribe' => 2
            ))
                ->data($data)
                ->save();
        }
    }

    public function unsubscribe($openid)
    {
        $where['openid'] = $openid;
        $data['subscribe'] = 2;
        $this->wechat_userinfo->where($where)
            ->data($data)
            ->save();
    }

    /**
     * 卡劵核销结果
     * 
     * @param unknown $data            
     */
    public function sendVerify($data)
    {
        $dataNew = array(
            'first' => array(
                'value' => '卡券核销结果通知',
                "color" => "#173177"
            ),
            'keyword1' => array(
                'value' => "刚刚",
                "color" => "#2C12FF"
            ),
            'keyword2' => array(
                'value' => "你猜猜",
                "color" => "#FF0000"
            ),
            'keyword3' => array(
                'value' => "就不告诉你",
                "color" => "#FF0000"
            ),
            'keyword4' => array(
                'value' => "核销成功！！",
                "color" => "#FF0000"
            ),
            'remark' => array(
                'value' => "你的号卡劵，已经核销。感谢您的使用",
                "color" => "#173177"
            )
        );
        $this->sendTplMsg($data['FromUserName'], "5zrNstgDfLS3u7sZ8b8ooseDfR2Av67i5FSNOMv_kPI", "", $dataNew);
    }
    
    
    private function subReturnContent()
    {
        $buget = M('youkuvip')->where('id = 1')->find();
        
        $data = "嘿，终于等到你！/:hug\n\n修好乐是国内最专业的手机维修品牌服务商，您的任何维修需求在这里都能得到解决。/:heart客服在线时间为09:00-21:00，欢迎您的咨询/:beer";
        $data .= "\n\n/:heart珠海地区已正式开通上门维修业务，赶紧联系吧";
        $data .= "\n\n/:v<a href='{$buget['buget_url']}'>{$buget['buget']}</a>";
        $data .= "\n\n/:v<a href='http://jiqiao.xhaole.com/'>点击查看更多精选苹果技巧</a>";
        
        return $data;
    }
}      