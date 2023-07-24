<?php  
//定义HELPER目录
define("HELPER_DIR",__DIR__);   
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
        'lng'=>'116.397128',
        'lat'=>'39.916527',
        'title'=>'北京天安门'
    ], 
]); 
 */
function predis_add_geo($key,$arr = []){
    $redis = predis();
    $redis->multi();
    foreach($arr as $v){
        if($key && $v['lat'] && $v['lng'] && $v['title']){
            $redis->geoadd($key, $v['lng'], $v['lat'], $v['title']);
        }  
    } 
    $redis->exec();
}
/**
 * 删除位置信息
 * 
predis_delete_geo('places',[ 
  '北京天安门',
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
function predis_get_pager($key,$lat,$lng,$juli = 2,$sort = 'ASC',$to_fixed=2){
    $redis = predis();   
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
            'lng'=>bcmul($v[0],1,$to_fixed),
            'lat'=>bcmul($v[1],1,$to_fixed),
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
    if(!file_exists($file) || (file_exists($file) && filesize($file) < 10)){ 
        $context = get_remote_file($url);
        $mime = get_mime($url);
        $arr = ['mime'=>$mime,'url'=>$url];
        do_action("download",$arr);
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

/**
* 返回成功的json信息
*/
function success_data($data,$msg = ''){
    return ['data'=>$data,'code'=>0,'type'=>'success','msg'=>$msg,'host'=>host(),'time'=>now()];
}
/**
* 返回失败的json信息
*/
function error_data($msg){
    if(is_string($msg)){
        return ['msg'=>$msg,'code'=>250,'type'=>'error','time'=>now()];    
    }else if(is_array($msg)){
        return array_merge(['code'=>250,'type'=>'error','time'=>now()],$msg);
    } 
}

/**
* pathinfo 
* /index.php/admin/auth/index?code=2 
* 返回  admin/auth/index
* 数组时返回 ['admin','auth','index']
*/
function get_path_info($return_array = false){
    $script_name = $_SERVER['SCRIPT_NAME'];
    $req_uri = $_SERVER['REQUEST_URI'];
    $path_info = str_replace($script_name,'',$req_uri);
    if(strpos($path_info,'?')!==false){
        $path_info = substr($path_info,0,strpos($path_info,'?'));
    }
    if(substr($path_info,0,1)=='/'){
        $path_info = substr($path_info,1);
    }
    if(substr($path_info,-1)=='/'){
        $path_info = substr($path_info,0,-1);
    }
    if($return_array){
        $arr = explode("/",$path_info);
        if(!isset($arr[1])){
            $arr[1] = 'index';
        }
        if(!isset($arr[2])){
            $arr[2] = 'index';
        }
        return $arr;
    }
    return $path_info;
}

/**
*  支持pathinfo路由
*  未找到请用 pathinfo_not_find 函数
*/
function router_pathinfo($ns = 'app',$add_controller = 'controller',$ucfirst_controller = true){
    $arr = get_path_info(true);
    $module = $arr[0];
    $controller = $arr[1];
    $action = $arr[2];
    if($ucfirst_controller){
        $controller = ucfirst($controller);
    } 
    if($module){ 
        $class = "\\".$ns."\\".$module."\\".$add_controller."\\".$controller; 
        if(class_exists($class)){
            $obj = new $class();
            if(method_exists($obj,$action)){ 
                return $obj->$action();
            }
        }
    }else{
       $class = "\\".$ns."\controller\Index";
        if(class_exists($class)){
            $obj = new $class();
            if(method_exists($obj,'index')){ 
                return $obj->$action();
            }
        } 
    } 
    if(function_exists("pathinfo_not_find")){
        pathinfo_not_find();
    }
}
/**
* 生成数字随机数
* 一般用于核销
* 需要表名 rand_code 字段  nid code status默认0
*/
function make_rand_code($node_id){
    $code = mt_rand(1000000,9999999);
    $res =  db_get('rand_code',['code'=>$code,'status'=>0,'LIMIT'=>1]);      
    if($res){
        make_rand_code($node_id);
    }else{
        db_insert('rand_code',[
            'nid'=>$node_id,
            'code'=>$code,
            'status'=>0,
        ]);
        return $code;
    }
}
/**
* 核销后需要释放核销码
*/
function update_make_rand_code($node_id){
    db_update("rand_code",['status'=>1],['nid'=>$node_id]);
}
/**
* 创建锁定操作
* 默认锁定1分钟
*/
function set_lock($key,$exp_time = 60){
    cache("lock:".$key,1,$exp_time);
}
/**
* 获取是否锁定
*/
function get_lock($key){
    $res = cache("lock:".$key);
    if($res){
        return true;
    }else{
        return false;
    }
}

/**
* 释放锁定
*/
function del_lock($key){
    cache("lock:".$key,null);
}
/**
* json数据替换
* @param $json  json格式数组或数组
* @param $replace  要替换的数组，如$replace = ['appid'=>'new appid'];
* @param $return_json  默认返回JSON格式
*/
function json_replace($json,$replace = [],$return_json = true){
    if(is_array($json)){
        $base = $json;
    }else{
        $base = json_decode($json,true);    
    }  
    $new = array_replace_recursive($base,$replace);
    if($return_json){
        return json_encode($new,JSON_UNESCAPED_UNICODE);
    }
    return $new;
}

/**
* 去除PHP代码注释  
*/
function remove_php_notes($content){
    return preg_replace("/(\/\*(\s|.)*?\*\/)|(\/\/.(\s|.*))|(#(\s*)?(.*))/", '', str_replace(array("\r\n", "\r"), "\n", $content)); 
}
/**
* 在线查看office文件
*/
function online_view_office($url){
    $url = str_replace("https://","",$url);
    $url = str_replace("http://","",$url);
    $url = urlencode($url);
    return "https://view.officeapps.live.com/op/view.aspx?src=".$url;
}
/**
 * 格式化输出金额
 * 强制输出数字类型
 */
function printfs(&$v,$keys = [],$dot = 2){ 
    $p = pow(10,$dot);
    foreach($keys as $k){
        $val   = $v[$k];
        $val   = (int)bcmul($val,$p);
        $v[$k] = bcdiv($val,$p,$dot); 
    } 
}

/**
* float不进位，如3.145 返回3.14
* 进位的有默认round(3.145) 或sprintf("%.2f",3.145);
*/
function float_noup($float_number,$dot = 2){ 
   $p = pow(10,$dot);
   return floor($float_number*$p)/$p;  
}

/**
* 加载xlsx 
load_xls([
    'file'  => $xls,
    'config'=>[
        '序号'  =>'index', 
    ],
    'title_line'=>1,
    'call'=>function($i,$row,&$d){}
]);
*/
function load_xls($new_arr = []){
    $xls_file   = $new_arr['file'];
    $config     = $new_arr['config'];
    $title_line = $new_arr['title_line']?:1;
    $call       = $new_arr['call'];
    $is_full    = $new_arr['is_full']?:false; 
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($xls_file);
    $worksheet = $spreadsheet->getActiveSheet();
    //总行数
    $rows      = $worksheet->getHighestRow();
    //总列数 A-F
    $columns   = $worksheet->getHighestColumn();
    $index     = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columns);
    $lists = []; 
    for ($row = 1; $row <= $rows; $row++) {
        $list = []; 
        for ($i = 1; $i <= $index; $i++) {  
            $d = $worksheet->getCellByColumnAndRow($i,$row)->getValue(); 
            if (is_object($d)) {
                $d = $d->__toString();
            }   
            if($call){
                $call($i,$row,$d);
            } 
            $list[] = $d;  
        }
        $lists[] = $list;
    }
    $top    = $title_line-1;
    $titles = $lists[$top];
    $titles = array_flip($titles); 
    $new_lists = [];
    foreach($lists as $i=>$v){
        if($i > $top){
            if($config){
                $new_list = [];
                foreach($config as $kk=>$vv){
                    $j = $titles[$kk];
                    $new_list[$vv] = $v[$j];
                }
                $new_lists[] = $new_list;
            }
        }
    }
    if($new_lists){
        $lists = $new_lists;
    } 
    $ret =  [
        'data'    => $lists,
        //总行数
        'total_r' => $rows,
        //总列数
        'total_c' => $index, 
    ];
    if($is_full){
        return $ret;
    }else{
        return $ret['data'];
    } 
}
