<?php
namespace Home\Model;

use Think\Model;

class TellModel extends Model
{
    public $siteBaseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->siteBaseUrl = 'http://' . $_SERVER['HTTP_HOST'];
    }

    public function CompletionOfPaymentData($total_fee)
    {
        $total_fee = $total_fee * 0.01;
        $ret['template_id'] = '87HVubxfFR8vcA4epiMoStCkuBSGSibB9m-b16h3Lc4';
        $ret['url'] =  $this->siteBaseUrl . U('Home/Jiesuoba/seeorder');
        $ret['data'] = array(
            'first' => array(
                'value' => '您好，您已支付成功!',
                "color" => "#173177"
            ),
            'keyword1' => array(
                'value' => $total_fee . '元',
                "color" => "#2C12FF"
            ),
            'keyword2' => array(
                'value' => '手机维修',
                "color" => "#FF0000"
            ),
            'remark' => array(
                'value' => '我们已收到您的订单，点击订单详情查看邮寄地址及注意事项，并在完成邮寄后填写运单号码',
                "color" => "#173177"
            )
        );
        return $ret;
    }
    
    public function CompletionPostPackage_user($model, $prbm_list)
    {
        $ret['template_id'] = 'SiF_BonPQ11aidseJjq80FPJxBvnnHqu8wBTK_Y2e20';
        $ret['url'] = $this->siteBaseUrl . U('Home/Jiesuoba/seeorder');
        $ret['data'] = array(
            'first' => array(
                'value' => '您的订单有最新动态！',
                "color" => "#173177"
            ),
            'keyword1' => array(
                'value' => $model,
                "color" => "#2C12FF"
            ),
            'keyword2' => array(
                'value' => $prbm_list,
                "color" => "#2C12FF"
            ),
            'keyword3' => array(
                'value' => '已填写寄送单号',
                "color" => "#FF0000"
            ),
            'keyword4' => array(
                'value' => date("Y-m-d H:i:s",time()),
                "color" => "#FF0000"
            ),
            'remark' => array(
                'value' => '点击查看快递进度....',
                "color" => "#173177"
            )
        );
        return $ret;
    }
}