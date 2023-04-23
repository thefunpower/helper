<?php 

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
function curl_aliyun($url,$bodys = [],$method='POST')
{ 
    $appcode = get_config('aliyun_market_AppCode');  
    $headers = array(); 
    array_push($headers, "Authorization:APPCODE " . trim($appcode));
    array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
    $querys = "";  
    $curl   = curl_init();
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method); 
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    if($bodys){
        if($method == 'POST'){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);    
        }else{
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
        }        
    } 
    curl_setopt($curl, CURLOPT_URL, $url);
    $data = curl_exec($curl);   
    if(is_json($data)){
        $data = json_decode($data,true);      
    }     
    return $data; 
}