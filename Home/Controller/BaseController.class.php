<?php
namespace Home\Controller;

use Think\Controller;
use Org\WeiXin\Curl;

/**
 * 微信常用方法鸡肋
 * 
 * @author zengbin
 * @since 2016-3-5 更新模板消息接口
 *        2016-3-10 更新发送卡券接口/主动发送消息给用户接口
 */
class BaseController extends Controller
{

    public $token;
 // 全局token
    public $thisSiteUrl;
 // 站点URL
    public $appid = 'wxc555f6097f8be8d6';

    public $appSecret = '203a327b3f9a5df322896c42bdccf096';

    public function __construct()
    {
        parent::__construct();
        $this->curl = new Curl();
        $baseToken = M('token');
        $where = "id=1";
        $usingToken = $baseToken->where($where)->find();
		\Think\Log::write(json_encode($usingToken), 'ERR');
        $this->thisSiteUrl = 'http://' . $_SERVER['HTTP_HOST'];
        if ($usingToken['lasttime'] < (time() - 6500)) {
            // 如果不是最新的token,就更新数据库的token
            $newToken = $this->curl->rapid("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->appSecret}");
			\Think\Log::write('toke return-------------------' . $newToken, 'ERR');
            $newToken = json_decode($newToken, true);
            $newToken = $newToken['access_token'];
            $baseToken->lasttime = time();
            $baseToken->token = $newToken;
            $baseToken->where($where)->save();
            $this->token = $newToken;
        } else {
            $this->token = $usingToken['token'];
        }
    }

    /**
     * 从微信服务器获取用户信息，授权模式
     * 
     * @param 可选 $extraInfo传入【额外】变量储存在session中            
     * @return array 原样返回微信的的用户信息
     */
    public function getUserInfo($extraInfo = '')
    {
        $code = I('code');
        $state = 1; // I('state');
        session('extraInfo', $extraInfo);
        if ($code == null) {
            $url = $this->thisSiteUrl . $_SERVER['REQUEST_URI']; // 当前连接地址
            $url = urlencode($url);
            $redirectUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri={$url}&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";
            header("Location:" . $redirectUrl);
            exit();
        }
        if ($code != null && $state != null) {
            $result = $this->curl->rapid("https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appid}&secret={$this->appSecret}&code={$code}&grant_type=authorization_code");
            $result = json_decode($result, true);
            $token = $result['access_token'];
            $openid = $result['openid'];
            $userinfo = $this->curl->rapid("https://api.weixin.qq.com/sns/userinfo?access_token={$token}&openid={$openid}&lang=zh_CN");
            $userinfo = json_decode($userinfo, true);
        }
        return $userinfo;
    }

    /**
     * 从微信服务器获取用户信息，授权模式
     * 
     * @param 可选 $extraInfo传入【额外】变量储存在session中            
     * @return array 原样返回微信的的用户信息
     */
    public function getUserInfobase($extraInfo = '')
    {
        $code = I('code');
        $state = 1; // I('state');
        session('extraInfo', $extraInfo);
        if ($code == null) {
            $url = $this->thisSiteUrl . $_SERVER['REQUEST_URI']; // 当前连接地址
            $url = urlencode($url);
            $redirectUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri={$url}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";
            header("Location:" . $redirectUrl);
            exit();
        }
        if ($code != null && $state != null) {
            $result = $this->curl->rapid("https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appid}&secret={$this->appSecret}&code={$code}&grant_type=authorization_code");
            $result = json_decode($result, true);
            // var_dump($result);exit;
            // $token=$result['access_token'];
            // $openid=$result['openid'];
            // $userinfo=$this->curl->rapid("https://api.weixin.qq.com/sns/userinfo?access_token={$token}&openid={$openid}&lang=zh_CN");
            // $userinfo=json_decode($userinfo,true);
        }
        return $result;
    }

    /**
     *
     * @param 必填 $openid
     *            检查用户是否订阅，请保证 wechat_userinfo已经创建;
     */
    public function checkSubscribe($openid)
    {
        $this->wechat_userinfo = M('wechat_userinfo');
        $where['openid'] = $openid;
        $where['subscribe'] = 1;
        $result = $this->wechat_userinfo->where($where)->find();
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param 可选 $debug
     *            返回 jsdk 初始化wx.config的 js字符串
     */
    public function getJsdk($debug = "false")
    {
        Vendor('wechatsdk.jssdk');
        $jssdk = new \JSSDK($this->appid, $this->appSecret);
        $signPackage = $jssdk->GetSignPackage();
        $weConf = <<<EOF
<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
wx.config({
    debug: {$debug},
    appId: '{$signPackage["appId"]}',
    timestamp: {$signPackage["timestamp"]},
    nonceStr: '{$signPackage["nonceStr"]}',
    signature: '{$signPackage["signature"]}',
    jsApiList: [
		'onMenuShareAppMessage',
		'closeWindow',
		'onMenuShareTimeline',
    ]  });
</script>
EOF;
        return $weConf;
    }

    /**
     * 主动使用客服接口推送text消息给用户
     * 
     * @param 必填 $openid            
     * @param 必填 $content            
     *
     */
    public function serviceReturn($openid, $content)
    {
        $data['touser'] = $openid;
        $data['text'] = array(
            'content' => $content
        );
        $data['msgtype'] = "text";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $result = $this->curl->rapid("https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$this->token}", "POST", $data);
        $result = json_decode($result, true);
        if ($result['errcode'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 发送卡劵给指定用户
     * 
     * @param 必填 $openids            
     * @param 必填 $cardid
     *            卡劵ID
     */
    public function sendWxCard($openid, $cardid)
    {
        $data['touser'] = $openid;
        $data['wxcard'] = array(
            'card_id' => $cardid
        );
        $data['msgtype'] = "wxcard";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $result = $this->curl->rapid("https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$this->token}", "POST", $data);
        $result = json_decode($result, true);
        if ($result['errcode'] == 0) {
           return true;
        }
        
        return $result['errcode'];
    }

    /**
     * 发送模板消息
     * 
     * @param 必填 $touser
     *            接收方openid
     * @param 必填 $template_id
     *            模板id
     * @param 必填 $url
     *            用户点击跳转地址
     * @param 必填 $data
     *            发送的消息（具体内容查看微信开发文档）
     *            http://mp.weixin.qq.com/wiki/17/304c1885ea66dbedf7dc170d84999a9d.html
     */
    public function sendTplMsg($touser, $template_id, $url, $data)
    {
        $totalDate = array(
            'touser' => $touser,
            'template_id' => $template_id,
            'url' => $url,
            'data' => $data
        );
        $totalDate = json_encode($totalDate);
        $this->curl->rapid("https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$this->token}", 'POST', $totalDate);
    }

    public function wx_init()
    {
        // session('openid','og0I1s9LEoIdmAPukrKVGFI6S_1M') ; //$userInfo['openid']);
        if (session('openid') == null) { // 查询是否最近访问了-----防止多次授权【烦人】
            $userInfo = $this->getUserInfo();
        } else {
            $where['openid'] = session('openid');
            $userInfo = $this->wechat_userinfo->where($where)->find();
        }
        session('openid', $userInfo['openid']);
        return $userInfo;
    }
    //     模版消息样本
    //     $touser='op4OXwDlekNiCgVbBn-JEnOK-fe8';
    //     $template_id='XjXPRTuaaHfdEDJtBmhffrVrlZ4gyITwgmgc_cdmja8';
    //     $url='http://zengbingo.com/';
    //     $data=array(
    //         'first'=>array(
    //             'value'=>'恭喜你中奖啦！',
    //             "color"=>"#173177"
    //         ),
    //         'keyword1'=>array(
    //             'value'=>'手气摇一摇！',
    //             "color"=>"#2C12FF"
    //         ),
    //         'keyword2'=>array(
    //             'value'=>'中了一个iphoe6s',
    //             "color"=>"#FF0000"
    //         ),
    //         'remark'=>array(
    //             'value'=>'奖品按中奖顺序依次发放！',
    //             "color"=>"#173177"
    //         ),
    //     );
}
