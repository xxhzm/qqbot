<?php

include('Base.php');

class App extends Base
{
    // API地址
    protected $apiUrl = 'http://122.228.216.223:5700';
    protected $footerMsg = '%0a小小孩子们的blog：www.xxhzm.cn';

    public function __construct()
    {
        // 接收json格式字符串
        $qqMsg = json_decode(file_get_contents('php://input'), TRUE);

        if (!$qqMsg) {
            exit;
        }

        if ($qqMsg['post_type'] === 'meta_event') {
            exit;
        }

        // 判断是否用户私聊
        if ($qqMsg['message_type'] === 'private') {
            $this->sendPrivateMsg($qqMsg['user_id'], '请您在群聊中使用');
            exit;
        }

        switch ($qqMsg['raw_message']) {
            case '一言':
                $this->yiyan($qqMsg);
                break;
            case '古诗词':
                $this->poetry($qqMsg);
                break;
            case '菜单':
                $this->menu($qqMsg);
                break;
            case preg_match('/二维码生成 .*/', $qqMsg['raw_message']) === 1:
                $this->QRcode($qqMsg);
                break;
            case preg_match('/备案 .*/', $qqMsg['raw_message']) === 1:
                $this->icp($qqMsg);
                break;
            case preg_match('/whois .*/', $qqMsg['raw_message']) === 1:
                $this->whois($qqMsg);
                break;
            case preg_match('/ip .*/', $qqMsg['raw_message']) === 1:
                $this->ip($qqMsg);
                break;
        }
    }

    // 菜单
    protected function menu($qqMsg)
    {
        $this->sendGroupMsg($qqMsg['group_id'], "%0a一言%0a古诗词%0a二维码生成%0a备案%0awhois%0aip", $qqMsg['user_id']);
    }

    // 一言
    protected function yiyan($qqMsg)
    {
        $data = $this->geturl('https://v1.api-m.com/text/yiyan?type=hitokoto')['data'];
        $this->sendGroupMsg($qqMsg['group_id'], $data, $qqMsg['user_id']);
    }

    // 古诗词
    protected function poetry($qqMsg)
    {
        $data = $this->geturl('https://v1.api-m.com/text/yiyan?type=poetry')['data'];
        $this->sendGroupMsg($qqMsg['group_id'], str_replace(' ', '', $data), $qqMsg['user_id']);
    }

    // 二维码生成
    protected function QRcode($qqMsg)
    {
        $data = $this->geturl('https://v1.api-m.com/pic/QRcode?size=5&text=' . ltrim($qqMsg['raw_message'], '二维码生成 '))['data'];
        $this->sendGroupMsg($qqMsg['group_id'], '生成成功您的二维码为：%0a[CQ:image,file=' . $data . ']', $qqMsg['user_id']);
    }

    // icp备案
    protected function icp($qqMsg)
    {
        $data = $this->geturl('https://v1.api-m.com/domain/icp?domain=' . ltrim($qqMsg['raw_message'], '备案 '));

        // 判断是否请求成功
        if ($data['code'] === '-2') {
            $this->sendGroupMsg($qqMsg['group_id'], $data['msg'], $qqMsg['user_id']);
            return false;
        }

        $unitName = $data['data']['unitName'];
        $natureName = $data['data']['natureName'];
        $serviceLicence = $data['data']['serviceLicence'];
        $updateRecordTime = substr($data['data']['updateRecordTime'], 0, -9);

        $this->sendGroupMsg($qqMsg['group_id'], "查询成功%0a%0a主办单位名称：$unitName%0a主办单位性质：$natureName%0a网站备案/许可证号：$serviceLicence%0a审核时间：$updateRecordTime%0a", $qqMsg['user_id']);
    }

    // whois
    protected function whois($qqMsg)
    {
        $data = $this->geturl('https://v1.api-m.com/domain/whois?domain=' . ltrim($qqMsg['raw_message'], 'whois '));

        // 判断是否请求成功
        if ($data['code'] === '-2') {
            $this->sendGroupMsg($qqMsg['group_id'], $data['msg'], $qqMsg['user_id']);
            return false;
        }

        $DomainName = $data['data']['Domain Name']; // 域名
        $SponsoringRegistrar = $data['data']['Sponsoring Registrar']; // 注册商
        $RegistrarURL = $data['data']['Registrar URL']; // 注册商URL
        $Registrant = $data['data']['Registrant']; // 注册人
        $RegistrantContactEmail = $data['data']['Registrant Contact Email']; // 注册邮箱
        $RegistrationTime = substr($data['data']['Registration Time'], 0, -9); // 注册时间
        $ExpirationTime = substr($data['data']['Expiration Time'], 0, -9); // 到期时间

        $this->sendGroupMsg($qqMsg['group_id'], "查询成功%0a%0a域名：$DomainName%0a注册商：$SponsoringRegistrar%0a注册商URL：$RegistrarURL%0a注册邮箱：$RegistrantContactEmail%0a注册人：$Registrant%0a注册时间：$RegistrationTime%0a到期时间：$ExpirationTime", $qqMsg['user_id']);
    }

    // ip
    protected function ip($qqMsg)
    {
        $data = $this->geturl('https://v1.api-m.com/network/ip?ip=' . ltrim($qqMsg['raw_message'], 'ip '))['data'];

        $begin = $data['begin'];
        $end = $data['end'];
        $address = $data['address'];

        $this->sendGroupMsg($qqMsg['group_id'], "查询成功%0aIP段起始：{$begin}%0aIP段结束：{$end}%0a归属地：{$address}", $qqMsg['user_id']);
    }

    // 在私聊中发送信息
    protected function sendPrivateMsg($userId, $msg)
    {
        $this->geturl($this->apiUrl . '/send_private_msg?user_id=' . $userId . '&message=' . $msg);
    }

    // 在群聊中发送信息
    protected function sendGroupMsg($groupId, $msg, $userId)
    {
        $this->geturl($this->apiUrl . '/send_group_msg?group_id=' . $groupId . '&message=' . '[CQ:at,qq=' . $userId . ']' . $msg . $this->footerMsg);
    }
}

$app = new App();
