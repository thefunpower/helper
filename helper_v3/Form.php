<?php

namespace helper_v3;

use lib\Session;

/**
* 表单token
* $token = helper_v3\Form::create_token();
* 在vue form中添加<?= $token['input']?>
* 提交数据前 this.form.__TOKEN__ = '" . $token['data'] . "';
* 服务端检测token， helper_v3\Form::check_token();
* 判断value值，需要在配置文件中配置 form_token_value 的值 
* 表单的token名称也可以通过配置 form_token_name 来修改，默认为__TOKEN__
*/
class Form
{
    /**
     * 对游客添加cookie
     */
    public static function guest_cookie()
    {
        if(!xcookie('gid')) {
            xcookie('gid', md5(uniqid()), time() + 86400 * 365 * 3);
        }
        return xcookie('gid');
    }
    /**
     * token名称
     */
    public static function token_name()
    {
        return get_config("form_token_name") ?: "__TOKEN__";
    }
    /**
     * 创建token值
     */
    public static function create_token_value()
    {
        $value = get_config("form_token_value") ?: "hv3value";
        $name = self::token_name();
        $token = [
            'gid' => self::guest_cookie(),
            'time' => time(),
            'rand' => mt_rand(0, 99999),
            'value' => $value
        ];
        $token = aes_encode($token);
        Session::set($name, $token);
        return $token;
    }
    /**
     * 创建token需要的input meta data 数组
     */
    public static function create_token($form_name = "form")
    {
        global $vue; 
        $token = self::create_token_value();
        $data['data'] = $token;
        $data['input'] = '<input type="hidden" id="__TOKEN__" name="__TOKEN__" value="' . $token . '" />';
        $data['meta'] = '<meta name="csrf-token" content="' . $token . '">';
        return $data;
    }
    /**
    * 检测token
    */
    public static function check_token($throw = true)
    {
        $value = get_config("form_token_value") ?: "hv3value";
        $name = self::token_name();
        $token = $_POST[$name];
        $server_token = Session::get($name);
        if($server_token && $token) {
            $arr = aes_decode($server_token);
            if($arr && is_array($arr) && $arr['value'] == $value) {
                return true;
            }
        }
        if($throw) {
            throw new \Exception("令牌错误，建议刷新页面！");
        }
        return false;
    }
}
