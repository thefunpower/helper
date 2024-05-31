<?php
namespace helper_v3;
/*
* 短信
* 接口文档：http://china-message.cn:7002/docs/index.html#!shufu-plain.md 
* 此短信接口为直连三大运营商接口。属于直连通道。 
*/

class Sms
{
    /**
     * 剩余条数
     */
    public static function less()
    {
        $sms_user = get_config('sms_user');
        $sms_pwd  = get_config('sms_pwd');
        $sms_ip   = get_config('sms_ip');
        $url = "http://" . $sms_ip . "/plain/qryBal.php?acctno=" . $sms_user . "&passwd=" . $sms_pwd . "&product=C000001";
        $res   = file_get_contents($url);
        $res   = json_decode($res, true);
        $list  = $res['Lists'][0];
        //可发送短信条数
        $out['sms_less']  = $list['ValidCnt'];
        //已发送条数
        $out['sms_used']  = $list['UsedCnt'];
        return $out;
    }
    /**
     * 发送短信
     */
    public static function send($phone, $content, $sign = null)
    {
        do_action("sms.before");
        $sms_user = get_config('sms_user');
        $sms_pwd  = get_config('sms_pwd');
        $sms_ip   = get_config('sms_ip');
        $sign     = $sign ?: get_config('sms_sign');
        $url = "http://" . $sms_ip . "/plain/SmsMt.php?acctno=" . $sms_user . "&passwd=" . $sms_pwd . "&mobile=" . $phone . "&msg=【" . $sign . "】" . $content;
        $res = $json = file_get_contents($url); 
        $res = json_decode($res, true);
        $msg = self::code()[$res['RetCode']];
        do_action("sms.after",$res);
        if ($res['RetCode'] == 0) { 
            $ret = [
                'code' => 0,
                'type' => 'success',
                'msg'  => '发送成功',
                'data' => [ 
                    'title' => $phone,
                    'body'  => $content . "【" . $sign . "】", 
                    'status' => 1,
                    'created_at' => now(),
                ]
            ];
        } else {  
            $ret = [
                'code' => 250,
                'type' => 'error',
                'msg'  => $msg,
                'data' => [ 
                    'title' => $phone,
                    'body'  => $content . "【" . $sign . "】".$msg, 
                    'status' => -1,
                    'created_at' => now(),
                ]
            ];
        }
        return $ret;
    }
    protected static function code()
    {
        return [
            0 => '成功返回',
            100 => '系统忙（因平台侧原因，暂时无法处理提交的短信）',
            101 => '无此短信账号/短信账号未登陆',
            102 => '密码错',
            103 => '提交过快（提交速度超过流速限制）',
            104 => '未知错误（参数配置错）',
            105 => '敏感短信（短信内容包含敏感词）',
            106 => '消息长度错（>系统设定 或 <=0）',
            107 => '无合法手机号码',
            108 => '手机号码个数错',
            109 => '无发送额度（该短信账号可用短信数已使用完）',
            110 => '未定',
            111 => '短信账号自定义扩展号超长',
            112 => '无此产品，短信账号没有订购该产品',
            113 => '模板不存在，或模板检查失败',
            114 => '签名在黑名单',
            115 => '签名不合法，未带签名（短信账号必须带签名的前提下）',
            116 => 'IP 地址在黑名单内',
            117 => 'IP地址认证错,请求调用的IP地址不是系统登记的IP地址',
            118 => '短信账号没有相应的发送权限 / 状态不符',
            119 => '短信账号已过期',
            120 => '未定',
            121 => '手机号码在黑名单',
            122 => '手机号码不在白名单',
            123 => '号码所属运营商，不在短信账号支持范围',
            124 => '手机号码未找到对应运营商',
            125 => '手机号码格式错误',
            126 => '1分钟号码发送频率超限',
            127 => '1小时号码发送频率超限',
            128 => '24小时号码频率发送超限',
            141 => '不在短信账号有效时段',
            199 => '无此类型接口权限',
        ];
    }
}
