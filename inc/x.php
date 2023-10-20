<?php 
/**
* 判断是否是ssl
*/
if(!function_exists("xis_ssl")){
    function xis_ssl(){
        global $config;
        return  strpos($config['host'],'https://') !== false ?true:false;
    }
}

/**
 * 设置、获取cookie 
 */
if(!function_exists('xcookie')){
    function xcookie($name, $value = NULL, $expire = 0)
    {
        global  $config;
        $name   = $config['cookie_prefix'] . $name;
        $path   = $config['cookie_path'] ?: '/';
        $domain = $config['cookie_domain'] ?: '';
        if ($value === NULL) {
            $value = $_COOKIE[$name];
            $value = aes_decode($value); 
            return $value;
        }
        if(is_array($value)){
            $value = json_encode($value);
        }
        $bool = xis_ssl()?true:false; 
        $opt = [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $bool,
            'httponly' => $bool,
            'samesite' => 'None',
        ];
        if(!$bool){
            unset($opt['secure'],$opt['httponly'],$opt['samesite']);
        } 
        $value = aes_encode($value);
        setcookie($name, $value, $opt);  
        $_COOKIE[$name] = $value;
    }
}
/**
 * 删除COOKIE 
 */ 
if(!function_exists('xcookie_delete')){
    function xcookie_delete($name)
    {
        global  $config;
        $name   = $config['cookie_prefix'] . $name;
        $path   = $config['cookie_path'] ?: '/';
        $domain = $config['cookie_domain'] ?: '';  
        $bool = xis_ssl()?true:false; 
        $opt = [
            'expires' => time()-100,
            'path'    => $path,
            'domain'  => $domain,
            'secure'  => $bool,
            'httponly' => $bool,
            'samesite' => 'None',
        ];
        if(!$bool){
            unset($opt['secure'],$opt['httponly'],$opt['samesite']);
        } 
        setcookie($name, '', $opt);  
        $_COOKIE[$name] = ''; 
    } 
} 