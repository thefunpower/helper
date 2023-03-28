<?php 
/**
* 调用阿里云
*/
function curl_aliyun($url,$bodys = '',$method='POST')
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
            if(strpos($url,'?') === false){
                $url = $url.'?'.build_query($bodys);
            }
        }        
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    $exec = $data = curl_exec($curl);   
    if(is_json($data)){
        $data = json_decode($data,true);      
    }    
    return $data; 
}