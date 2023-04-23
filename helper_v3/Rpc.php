<?php  
/*
    Copyright (c) 2021-2031, All rights reserved.
    This is NOT a freeware
    LICENSE: https://github.com/thefunpower/core/blob/main/LICENSE.md 
    Connect Email: sunkangchina@163.com
*/
namespace helper_v3;
class Rpc{  
	/**
	* 取TOKEN
	*/
	public static function get_http_author(){
		return trim(substr($_SERVER['HTTP_AUTHORIZATION'],strlen('Bearer')));
	}
	/**
	* 客户端请求
	*/
	public static function client($remote_url,$is_remote = false){
		$client = new \Yar_Client($remote_url);   
		$token = '';
		$opt = [
        	"Authorization: Bearer ".$token,  
    	]; 
	    $client->SetOpt(YAR_OPT_HEADER, $opt); 
	    if(!$is_remote){
	    	$host = $remote_url;
	    	$host = str_replace("https://","",$host);
	    	$host = str_replace("http://","",$host);
	    	$host = substr($host,0,strpos($host,'/'));  
	    	if(strpos($host,'127.0.0.1') === false){
	    		$port = '80';
	    		$client->SetOpt(YAR_OPT_RESOLVE, array("$host:$port:127.0.0.1"));    		
	    	}	    	
	    }	    
	    if($token && YAR_VERSION >= '2.3.0'){
	        $client->setOpt(YAR_OPT_PROVIDER, "provider");
	        $client->setOpt(YAR_OPT_TOKEN, $token);   
	    } 
	    $client->SetOpt(YAR_OPT_CONNECT_TIMEOUT, 6000);  
	    return $client;
	}
	/**
	* 服务端
	*/
	public static function server($class){ 
		$service = new \Yar_Server(new $class());
		$service->handle();
	}
}