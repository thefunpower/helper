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
 * redis
 */
function predis($host='',$port='',$auth=''){
    static $redis;
    if($redis){
        return $redis;
    } 
    $redis = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => $host,
        'port'   => $port,
        'password'=> $auth,
    ]);
    return $redis;
}
/**
 * 添加位置信息
predis_add_geo('places',[
    [ 
        'lat'=>'116.397128',
        'lng'=>'39.916527',
        'title'=>'北京天安门'
    ], 
]); 
 */
function predis_add_geo($key,$arr = []){
    $redis = predis();
    $redis->multi();
    foreach($arr as $v){
        if($key && $v['lat'] && $v['lng'] && $v['title']){
            $redis->geoadd($key, $v['lat'], $v['lng'], $v['title']);
        }  
    } 
    $redis->exec();
}
/**
 * 删除位置信息
 * 
predis_delete_geo('places',[
    [
        '北京天安门'
    ], 
]);
 */
function predis_delete_geo($key,$arr = []){
    $redis = predis();
    $redis->multi();
    foreach($arr as $v){
        if($key && $v){
           $redis->zrem($key,  $v);
        } 
    }  
    $redis->exec();
}

/**
 * 返回附近的地理位置
 * pr(predis_get_pager('places', 116.403958, 39.915049));
 * http://redisdoc.com/geo/georadius.html
 */
function predis_get_pager($key,$lat,$lng,$juli = 1000,$sort = 'ASC',$to_fixed=2){
    $redis = predis();
    $juli = bcmul($juli,1000,2);   
    $arr = $redis->georadius($key,$lat,$lng, $juli, 'km', [
        'withdist' => true, 
        'sort' =>$sort, 
    ]); 
    $list =  array_to_pager($arr); 
    $new_list = [];
    foreach($list['data'] as $v){
        $new_list[$v[0]] = bcmul($v[1],1,$to_fixed);
    }
    $list['data'] = $new_list;
    return $list;
}
/**
 * 取lat lng
 */
function predis_geo_pos($key,$title = [],$to_fixed = 6){
     $redis = predis(); 
     $res = $redis->geoPos($key, $title);
     $list = [];
     foreach($res as $i=>$v){
        $vv = [
            'lat'=>bcmul($v[0],1,$to_fixed),
            'lng'=>bcmul($v[1],1,$to_fixed),
        ];
        $list[$title[$i]] = $vv;
     }
     return $list;
}
/** 
 * 分组分页
 */
function array_to_pager($arr)
{
    $page = g('page')?:1;
    $per_page = g('per_page')?:20;
    $total = count($arr);
    $last_page = ceil($total/$per_page);
    if($page>$last_page){
        $page = $last_page;
    }
    $arr   = array_slice($arr, ($page - 1) * $per_page, $per_page);
    $list  = [
        'current_page'=>$page,
        'data'=>$arr,
        'last_page'=>$last_page,
        'per_page'=>$per_page,
        'total'=>$total,
        'total_cur'=>count($arr),
    ];
    return $list;
}
/**
* 返回URL路径，不含有域名部分
*/
function get_url_remove_http($url){
    if(strpos($url,'://')===false){
        return $url;
    }
    $url = substr($url,strpos($url,'://')+3);
    $url = substr($url,strpos($url,'/'));
    return $url;
}
/**
* 取后缀
*/
function get_ext_by_url($url){
    $mime = lib\Mime::load();  
    $type = get_mime($url);
    if($type){
        foreach($mime as $k=>$v){
            if(is_array($v)){
                if(in_array($type,$v)){
                    $find = $k;
                    break;
                }
            }else{
                if($v == $type){
                    $find = $k;
                    break;
                }
            }
        }
    }    
    return $find;
}
/**
* 取mime
*/
function get_mime($url)
{ 
    if(strpos($url,'://')!==false){
        $type = get_headers($url,true)['Content-Type'];  
    }else{
        $type = mime_content_type($url); 
    }
    return $type;
}
/**
* 获取远程URL内容
*/
function get_remote_file($url,$is_json = false){
    $client = guzzle_http();
    $res    = $client->request('GET', $url);
    $res =  (string)$res->getBody(); 
    if($is_json){
        $res = json_decode($res,true);
    } 
    return $res;
}
/**
* 下载文件
*/
function download_file($url,$contain_http = false){
    $host = cdn_url();
    if(strpos($url,"://") !== false){
        $url = download_remote_file($url);
        if($contain_http){
            return $url;
        }
        $url = str_replace($host,'',$url);
    }else if(strpos($url,WWW_PATH)!==false){
        $url = str_replace(WWW_PATH,'',$url);  
    } 
    if($contain_http){
        return $host.$url;
    }else{
        return $url;    
    }    
}
/**
* 下载远程文件
* global $remote_to_local_path;
* $remote_to_local_path = '/uploads/saved/'.date("Y-m-d");
*/
function download_remote_file($url,$path='',$name = ''){
    global $remote_to_local_path;
    $remote_to_local_path = $remote_to_local_path?:'/uploads/tmp/'.date("Y-m-d");
    $name = $name?:$remote_to_local_path.'/'.md5($url).'.'.get_ext_by_url($url);
    $path = $path?:WWW_PATH;
    $file = $path.$name;  
    if(!file_exists($file)){ 
        $context = get_remote_file($url);
        $dir = get_dir($file); 
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        file_put_contents($file,$context);    
    } 
    return cdn_url().$name; 
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