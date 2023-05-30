<?php  
define("HELPER_DIR",__DIR__);
if(!function_exists('rpc_client')){
    function rpc_client($url,$is_remote = false){
        return helper_v3\Rpc::client($url,$is_remote);
    }
}

if(!function_exists('rpc_server')){
    function rpc_server($class){
        helper_v3\Rpc::server($class);
    }
}

if(!function_exists('rpc_token')){
    function rpc_token(){
        return helper_v3\Rpc::get_http_author();
    }
}


/**
* 调用阿里云
*/
function curl_aliyun($url,$bodys = '',$method='POST')
{ 
    $curl = curl_init();
    $appcode = get_config('aliyun_m_code');  
    $headers = array(); 
    array_push($headers, "Authorization:APPCODE " . trim($appcode));
    array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
    $querys = "";  
    if($bodys){
        if($method == 'POST'){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);    
        }else{
            if(is_array($bodys)){
               $str = '';
                foreach($bodys as $k=>$v){
                    $str .=$k.'='.$v."&";
                }
                $str = substr($str,0,-1);
                if(strpos($url,'?') === false){ 
                    $url = $url.'?'.$str;
                }else{
                    $url = $url."&".$str;
                } 
            }else{
                if(strpos($url,'?') === false){ 
                    $url = $url.'?'.$bodys;
                }else{
                    $url = $url."&".$bodys;
                } 
            }
            
        }        
    }  
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$" . $host, "https://")) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    } 
      
    $out_put = curl_exec($curl);  
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE); 
    list($header, $body) = explode("\r\n\r\n", $out_put, 2);
    if ($http_code == 200) { 
        if(is_json($body)){
            $body = json_decode($body,true);   
            $body['code'] = 0;
        }     
        return $body; 
    } else {
        if ($http_code == 400 && strpos($header, "Invalid Param Location") !== false) {
            return ['msg'=>"参数错误",'code'=>250];
        } elseif ($http_code == 400 && strpos($header, "Invalid AppCode") !== false) {
            return ['msg'=>"AppCode错误",'code'=>250];
        } elseif ($http_code == 400 && strpos($header, "Invalid Url") !== false) {
            return ['msg'=>"请求的 Method、Path 或者环境错误",'code'=>250];
        } elseif ($http_code == 403 && strpos($header, "Unauthorized") !== false) {
            return ['msg'=>"服务未被授权（或URL和Path不正确）",'code'=>250];
        } elseif ($http_code == 403 && strpos($header, "Quota Exhausted") !== false) {
            return ['msg'=>"套餐包次数用完",'code'=>250];
        } elseif ($http_code == 403 && strpos($header, "Api Market Subscription quota exhausted") !== false) {
            return ['msg'=>"套餐包次数用完，请续购套餐",'code'=>250];
        } elseif ($http_code == 500) {
            return ['msg'=>"API网关错误",'code'=>250];
        } elseif ($http_code == 0) {
            return ['msg'=>"URL错误",'code'=>250];
        } else {  
            $headers = explode("\r\n", $header);
            $headList = array();
            foreach ($headers as $head) {
                $value = explode(':', $head);
                $headList[$value[0]] = $value[1];
            }
            return ['msg'=>$headList['x-ca-error-message'],'http_code'=>$http_code,'code'=>250];
        }
    } 
}