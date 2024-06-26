<?php

//定义HELPER目录
define("HELPER_DIR", __DIR__);
if(!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
/**
 * redis
 */
function predis($host = '', $port = '', $auth = '')
{
    static $redis;
    if($redis) {
        return $redis;
    }
    $redis = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => $host,
        'port'   => $port,
        'password' => $auth,
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
function predis_add_geo($key, $arr = [])
{
    $redis = predis();
    $redis->multi();
    foreach($arr as $v) {
        if($key && $v['lat'] && $v['lng'] && $v['title']) {
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
function predis_delete_geo($key, $arr = [])
{
    $redis = predis();
    $redis->multi();
    foreach($arr as $v) {
        if($key && $v) {
            $redis->zrem($key, $v);
        }
    }
    $redis->exec();
}

/**
 * 返回附近的地理位置
 * pr(predis_get_pager('places', 116.403958, 39.915049));
 * http://redisdoc.com/geo/georadius.html
 */
function predis_get_pager($key, $lat, $lng, $juli = 2, $sort = 'ASC', $to_fixed = 2)
{
    $redis = predis();
    $arr = $redis->georadius($key, $lat, $lng, $juli, 'km', [
        'withdist' => true,
        'sort' => $sort,
    ]);
    $list =  array_to_pager($arr);
    $new_list = [];
    foreach($list['data'] as $v) {
        $new_list[$v[0]] = bcmul($v[1], 1, $to_fixed);
    }
    $list['data'] = $new_list;
    return $list;
}
/**
 * 取lat lng
 */
function predis_geo_pos($key, $title = [], $to_fixed = 6)
{
    $redis = predis();
    $res = $redis->geoPos($key, $title);
    $list = [];
    foreach($res as $i => $v) {
        $vv = [
            'lng' => bcmul($v[0], 1, $to_fixed),
            'lat' => bcmul($v[1], 1, $to_fixed),
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
    $page = g('page') ?: 1;
    $per_page = g('per_page') ?: 20;
    $total = count($arr);
    $last_page = ceil($total / $per_page);
    if($page > $last_page) {
        $page = $last_page;
    }
    $arr   = array_slice($arr, ($page - 1) * $per_page, $per_page);
    $list  = [
        'current_page' => $page,
        'data' => $arr,
        'last_page' => $last_page,
        'per_page' => $per_page,
        'total' => $total,
        'total_cur' => count($arr),
    ];
    return $list;
}
/**
* 返回URL路径，不含有域名部分
*/
function get_url_remove_http($url)
{
    if(strpos($url, '://') === false) {
        return $url;
    }
    $url = substr($url, strpos($url, '://') + 3);
    $url = substr($url, strpos($url, '/'));
    $url =  trim($url);
    if(strpos($url, '://') !== false) {
        $url = get_url_remove_http($url);
    }
    return $url;
}
/**
* 取后缀
* add_action("get_ext_by_url",function(&$data){
*    $url = $data['url'];
*    $data['ext'] = 'pdf';
* });
*/
function get_ext_by_url($url)
{
    $data['url'] = $url;
    $data['ext'] = '';
    do_action("get_ext_by_url",$data); 
    if($data['ext']){
        return $data['ext'];
    }
    $mime = lib\Mime::load();
    $type = get_mime($url);
    if($type) {
        foreach($mime as $k => $v) {
            if(is_array($v)) {
                if(in_array($type, $v)) {
                    $find = $k;
                    break;
                }
            } else {
                if($v == $type) {
                    $find = $k;
                    break;
                }
            }
        }
    }
    return $find;
}
/**
* 通过URL取mime
* @param $url URL
*/
function get_mime($url)
{
    if(strpos($url, '://') !== false) {
        $type = get_headers($url, true)['Content-Type'];
    } else {
        $type = mime_content_type($url);
    }
    return $type;
}
/**
* 取mime
* @param $content 文件内容，可以是通过file_get_contents取到的
*/
function get_mime_content($content, $just_return_ext = false)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($finfo, $content);
    finfo_close($finfo);
    if($just_return_ext && $mime_type) {
        return substr($mime_type, strpos($mime_type, '/') + 1);
    }
    return $mime_type;
}
/**
* 获取远程URL内容
*/
function get_remote_file($url, $is_json = false)
{
    $client = guzzle_http();
    $res    = $client->request('GET', $url);
    $res =  (string)$res->getBody();
    if($is_json) {
        $res = json_decode($res, true);
    }
    return $res;
}
/**
 * 移除主域名部分
 */
function remove_host($url){
    $url = substr($url,strpos($url,'://')+3);
    $url = substr($url,strpos($url,'/'));
    return $url;
}
/**
* 下载文件
* 建议使用 download_file_safe
*/
function download_file($url, $contain_http = false)
{
    $host = cdn_url();
    if(strpos($url, "://") !== false) {
        global $is_local;
        if($is_local){
            if(strpos($url,get_root_domain(host())) !== false){
                return remove_host($url);
            } 
        }
        $url = download_remote_file($url);
        if($contain_http) {
            return $url;
        }
        $url = str_replace($host, '', $url);
    } elseif(strpos($url, WWW_PATH) !== false) {
        $url = str_replace(WWW_PATH, '', $url);
    }
    if($contain_http) {
        return $host.$url;
    } else {
        return $url;
    }
}
/**
* 下载资源文件到本地
*/
function download_file_safe($url, $mimes = ['image/*','video/*'], $cons = [], $contain_http = false)
{
    $flag = false;
    if($cons) {
        foreach($cons as $v) {
            if(strpos($url, $v) !== false) {
                $flag = true;
                break;
            }
        }
    } else {
        $flag = true;
    }
    if($flag) {
        $content_type = get_mime($url);
        foreach($mimes as $v) {
            $v = str_replace("*", "", $v);
            if(strpos($content_type, $v) !== 'false') {
                $new_url = download_file($url, $contain_http);
                if($new_url) {
                    return $new_url;
                }
            }
        }
    }
}
/**
* 下载远程文件
* global $remote_to_local_path;
* $remote_to_local_path = '/uploads/saved/'.date("Y-m-d");
*/
function download_remote_file($url, $path = '', $name = '')
{
    global $remote_to_local_path;
    $remote_to_local_path = $remote_to_local_path ?: '/uploads/tmp/'.date("Y-m-d");
    $name = $name ?: $remote_to_local_path.'/'.md5($url).'.'.get_ext_by_url($url);
    $path = $path ?: WWW_PATH;
    $file = $path.$name;
    if(!file_exists($file) || (file_exists($file) && filesize($file) < 10)) {
        $context = get_remote_file($url);
        $mime = get_mime($url);
        $arr = ['mime' => $mime,'url' => $url];
        do_action("download", $arr);
        $dir = get_dir($file);
        if(!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, $context);
    }
    return cdn_url().$name;
}
/**
* 调用阿里云
*/
function curl_aliyun($url, $bodys = '', $method = 'POST')
{
    $curl = curl_init();
    $appcode = get_config('aliyun_m_code');
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . trim($appcode));
    array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
    $querys = "";
    if($bodys) {
        if($method == 'POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        } else {
            if(is_array($bodys)) {
                $str = '';
                foreach($bodys as $k => $v) {
                    $str .= $k.'='.$v."&";
                }
                $str = substr($str, 0, -1);
                if(strpos($url, '?') === false) {
                    $url = $url.'?'.$str;
                } else {
                    $url = $url."&".$str;
                }
            } else {
                if(strpos($url, '?') === false) {
                    $url = $url.'?'.$bodys;
                } else {
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
        if(is_json($body)) {
            $body = json_decode($body, true);
            $body['code'] = 0;
        }
        return $body;
    } else {
        if ($http_code == 400 && strpos($header, "Invalid Param Location") !== false) {
            return ['msg' => "参数错误",'code' => 250];
        } elseif ($http_code == 400 && strpos($header, "Invalid AppCode") !== false) {
            return ['msg' => "AppCode错误",'code' => 250];
        } elseif ($http_code == 400 && strpos($header, "Invalid Url") !== false) {
            return ['msg' => "请求的 Method、Path 或者环境错误",'code' => 250];
        } elseif ($http_code == 403 && strpos($header, "Unauthorized") !== false) {
            return ['msg' => "服务未被授权（或URL和Path不正确）",'code' => 250];
        } elseif ($http_code == 403 && strpos($header, "Quota Exhausted") !== false) {
            return ['msg' => "套餐包次数用完",'code' => 250];
        } elseif ($http_code == 403 && strpos($header, "Api Market Subscription quota exhausted") !== false) {
            return ['msg' => "套餐包次数用完，请续购套餐",'code' => 250];
        } elseif ($http_code == 500) {
            return ['msg' => "API网关错误",'code' => 250];
        } elseif ($http_code == 0) {
            return ['msg' => "URL错误",'code' => 250];
        } else {
            $headers = explode("\r\n", $header);
            $headList = array();
            foreach ($headers as $head) {
                $value = explode(':', $head);
                $headList[$value[0]] = $value[1];
            }
            return ['msg' => $headList['x-ca-error-message'],'http_code' => $http_code,'code' => 250];
        }
    }
}

/**
* 返回成功的json信息
*/
function success_data($data, $msg = '')
{
    return ['data' => $data,'code' => 0,'type' => 'success','msg' => $msg,'host' => host(),'time' => now()];
}
/**
* 返回失败的json信息
*/
function error_data($msg)
{
    if(is_string($msg)) {
        return ['msg' => $msg,'code' => 250,'type' => 'error','time' => now()];
    } elseif(is_array($msg)) {
        return array_merge(['code' => 250,'type' => 'error','time' => now()], $msg);
    }
}

/**
* pathinfo
* /index.php/admin/auth/index?code=2
* 返回  admin/auth/index
* 数组时返回 ['admin','auth','index']
*/
function get_path_info($return_array = false)
{
    $script_name = $_SERVER['SCRIPT_NAME'];
    $req_uri = $_SERVER['REQUEST_URI'];
    $path_info = str_replace($script_name, '', $req_uri);
    if(strpos($path_info, '?') !== false) {
        $path_info = substr($path_info, 0, strpos($path_info, '?'));
    }
    if(substr($path_info, 0, 1) == '/') {
        $path_info = substr($path_info, 1);
    }
    if(substr($path_info, -1) == '/') {
        $path_info = substr($path_info, 0, -1);
    }
    if($return_array) {
        $arr = explode("/", $path_info);
        if(!isset($arr[1])) {
            $arr[1] = 'index';
        }
        if(!isset($arr[2])) {
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
function router_pathinfo($ns = 'app', $add_controller = 'controller', $ucfirst_controller = true)
{
    $arr = get_path_info(true);
    $module = $arr[0];
    $controller = $arr[1];
    $action = $arr[2];
    if($ucfirst_controller) {
        $controller = ucfirst($controller);
    }
    if($module) {
        $class = "\\".$ns."\\".$module."\\".$add_controller."\\".$controller;
        if(class_exists($class)) {
            $obj = new $class();
            if(method_exists($obj, $action)) {
                return $obj->$action();
            }
        }
    } else {
        $class = "\\".$ns."\controller\Index";
        if(class_exists($class)) {
            $obj = new $class();
            if(method_exists($obj, 'index')) {
                return $obj->$action();
            }
        }
    }
    if(function_exists("pathinfo_not_find")) {
        pathinfo_not_find();
    }
}
/**
* 生成数字随机数
* 一般用于核销
* 需要表名 rand_code 字段  nid code status默认0
*/
function make_rand_code($node_id)
{
    $code = mt_rand(1000000, 9999999);
    $res =  db_get('rand_code', ['code' => $code,'status' => 0,'LIMIT' => 1]);
    if($res) {
        make_rand_code($node_id);
    } else {
        db_insert('rand_code', [
            'nid' => $node_id,
            'code' => $code,
            'status' => 0,
        ]);
        return $code;
    }
}
/**
* 核销后需要释放核销码
*/
function update_make_rand_code($node_id)
{
    db_update("rand_code", ['status' => 1], ['nid' => $node_id]);
}
/**
*  锁功能已替代
lock_call('k',functon(){

},second);
*/
function set_lock($key, $exp_time = 60)
{
    cache("lock:".$key, 1, $exp_time);
}
/**
* 获取是否锁定
*/
function get_lock($key)
{
    $res = cache("lock:".$key);
    if($res) {
        return true;
    } else {
        return false;
    }
}

/**
* 释放锁定
*/
function del_lock($key)
{
    cache("lock:".$key, null);
}
/**
* json数据替换
* @param $json  json格式数组或数组
* @param $replace  要替换的数组，如$replace = ['appid'=>'new appid'];
* @param $return_json  默认返回JSON格式
*/
function json_replace($json, $replace = [], $return_json = true)
{
    if(is_array($json)) {
        $base = $json;
    } else {
        $base = json_decode($json, true);
    }
    $new = array_replace_recursive($base, $replace);
    if($return_json) {
        return json_encode($new, JSON_UNESCAPED_UNICODE);
    }
    return $new;
}

/**
* 去除PHP代码注释
*/
function remove_php_notes($content)
{
    return preg_replace("/(\/\*(\s|.)*?\*\/)|(\/\/.(\s|.*))|(#(\s*)?(.*))/", '', str_replace(array("\r\n", "\r"), "\n", $content));
}
/**
* 在线查看office文件
*/
function online_view_office($url)
{
    $url = str_replace("https://", "", $url);
    $url = str_replace("http://", "", $url);
    $url = urlencode($url);
    return "https://view.officeapps.live.com/op/view.aspx?src=".$url;
}
/**
 * 格式化输出金额
 * 强制输出数字类型
 */
function printfs(&$v, $keys = [], $dot = 2)
{
    $p = pow(10, $dot);
    foreach($keys as $k) {
        $val   = $v[$k];
        $val   = (int)bcmul($val, $p);
        $v[$k] = bcdiv($val, $p, $dot);
    }
}

/**
* float不进位，如3.145 返回3.14
* 进位的有默认round(3.145) 或sprintf("%.2f",3.145);
*/
function float_noup($float_number, $dot = 2)
{
    $p = pow(10, $dot);
    return floor($float_number * $p) / $p;
}
/**
* 四舍五入
* @param $mid_val 逢几进位
*/
function float_up($float_number, $dot = 2, $mid_val = 5)
{
    $p = pow(10, $dot);
    if(strpos($float_number, '.') !== false) {
        $a = substr($float_number, strpos($float_number, '.') + 1);
        $a = substr($a, $dot, 1) ?: 0;
        if($a >= $mid_val) {
            return bcdiv(bcmul($float_number, $p) + 1, $p, $dot);
        } else {
            return bcdiv(bcmul($float_number, $p), $p, $dot);
        }
    }
    $p = pow(10, $dot);
    return floor($float_number * $p) / $p;
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
function load_xls($new_arr = [])
{
    $xls_file   = $new_arr['file'];
    $config     = $new_arr['config'];
    $title_line = $new_arr['title_line'] ?: 1;
    $call       = $new_arr['call'];
    $is_full    = $new_arr['is_full'] ?: false;
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
            $d = $worksheet->getCellByColumnAndRow($i, $row)->getValue();
            if (is_object($d)) {
                $d = $d->__toString();
            }
            if($call) {
                $call($i, $row, $d);
            }
            $list[] = $d;
        }
        $lists[] = $list;
    }
    $top    = $title_line - 1;
    $titles = $lists[$top];
    $titles = array_flip($titles);
    $new_lists = [];
    foreach($lists as $i => $v) {
        if($i > $top) {
            if($config) {
                $new_list = [];
                foreach($config as $kk => $vv) {
                    $j = $titles[$kk];
                    $new_list[$vv] = $v[$j];
                }
                $new_lists[] = $new_list;
            }
        }
    }
    if($new_lists) {
        $lists = $new_lists;
    }
    $ret =  [
        'data'    => $lists,
        //总行数
        'total_r' => $rows,
        //总列数
        'total_c' => $index,
    ];
    if($is_full) {
        return $ret;
    } else {
        return $ret['data'];
    }
}

/**
* 获取文件行数，不包空行
*/
function get_lines($file, $length = 40960)
{
    $i = 1;
    $handle = @fopen($file, "r");
    if ($handle) {
        while (!feof($handle)) {
            $body = fgets($handle, $length);
            if($body && trim($body)) {
                $i++;
            }
        }
        fclose($handle);
    }
    return $i;
}

/**
* 返回请求中是http还是https
*/
function get_request_top()
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return 'https';
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return 'https';
    } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return 'https';
    }
    return 'http';
}
/**
* 返回请求域名及URL部分，不包含http://
*/
function get_request_host()
{
    $port = $_SERVER['SERVER_PORT'];
    if(in_array($port, [80,443])) {
        $port = '';
    } else {
        $port = ':'.$port;
    }
    return $_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
}
/**
* 自动跳转到https网站
*/
function auto_jump()
{
    $url = get_request_host();
    $top = get_request_top();
    if($top != 'https' && strtolower($_SERVER['REQUEST_METHOD']) == 'get') {
        $new_url = "https://".$url;
        header("Location: " . $new_url);
        exit;
    }
}

//取http的url
function get_http_full_url($url, $fun = 'cdn_url')
{
    if(strpos($url, '://') === false) {
        return $fun().$url;
    } else {
        return $url;
    }
}
/**
* 从数组中搜索
*/
function get_index_array_valule($array, $key, $val)
{
    $i = 0;
    foreach($array as $v) {
        if($v[$key] == $val) {
            break;
        }
        $i++;
    }
    return $i;
}

/**
* GBK字符截取
* 一个中文算2个字符
*/
if(!function_exists("gbk_substr")) {
    function gbk_substr($text, $start, $len, $gbk = 'GBK')
    {
        $str = mb_strcut(mb_convert_encoding($text, $gbk, "UTF-8"), $start, $len, $gbk);
        $str = mb_convert_encoding($str, "UTF-8", $gbk);
        return $str;
    }
}
/**
* GBK长宽
* 2个字符
*/
function get_gbk_len($value, $gbk = 'GBK')
{
    return strlen(iconv("UTF-8", $gbk."//IGNORE", $value));
}
/**
* 文字居中
*/
function get_text_c(string $str, int $len)
{
    $cur_len = get_gbk_len($str);
    $less    = $len - $cur_len;
    $s = (int)($less / 2);
    $e = $less - $s;
    $append = '';
    $end    = '';
    for($i = 0;$i < $s;$i++) {
        $append .= " ";
    }
    for($i = 0;$i < $e;$i++) {
        $end .= " ";
    }
    return $append.$str.$end;
}
/**
* 文字排版
* 左 中 右
* 左    右
*/
function get_text_lr(array $arr, int $length, $return_arr = false)
{
    $count  = count($arr);
    $middle = (int)(bcdiv($length, $count));
    $j = 1;
    foreach($arr as &$v) {
        $cur_len = get_gbk_len($v);
        $less    = $middle - $cur_len;
        $append  = "";
        if($less > 0) {
            for($i = 0;$i < $less;$i++) {
                $append .= " ";
            }
            if($j == $count) {
                $v = $append.$v;
            } else {
                $v = $v.$append;
            }
        } else {
            $v = gbk_substr($v, 0, $middle);
        }
        $j++;
    }
    if($return_arr) {
        return $return_arr;
    } else {
        return implode("", $arr);
    }
}
/**
 *  处理跨域
 */
function allow_cross_origin()
{
    $cross_origin = get_config('cross_origin');
    if(!$cross_origin) {
        $cross_origin = '*';
    }
    header('Access-Control-Allow-Origin: '.$cross_origin);
    header('Access-Control-Allow-Credentials:true');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
    header('X-Powered-By: WAF/2.0');
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit;
    }
}
if(!class_exists('di')) {
    /**
    * global $di;
    * $di = new di();
    * $di->adapter = new adapter();
    */
    class di
    {
        public $instance = [];

        public function __set($name, $value)
        {
            $this->instance[$name] = $value;
        }
    }
}
/**
* 字符或数组 转UTF-8
*/
if(!function_exists("to_utf8")) {
    function to_utf8($str)
    {
        if(!$str || (!is_array($str) && !is_string($str))) {
            return $str;
        }
        if(is_array($str)) {
            $list = [];
            foreach($str as $k => $v) {
                $list[$k] = to_utf8($v);
            }
            return $list;
        } else {
            $encoding = mb_detect_encoding($str, "UTF-8, GBK, ISO-8859-1");
            if($encoding && $encoding != 'UTF-8') {
                $str = iconv($encoding, "UTF-8//IGNORE", $str);
                $str = trim($str);
            }
            return $str;
        }
    }
}
/**
* 读取CSV
*/
if(!function_exists('csv_reader')) {
    function csv_reader($file)
    {
        return helper_v3\Csv::reader($file);
    }
}
/**
* 写入CSV
*/
if(!function_exists('csv_writer')) {
    function csv_writer($file, $header = [], $content = [])
    {
        return helper_v3\Csv::writer($file, $header, $content);
    }
}
/**
* 基于redis锁
*
global $redis_lock;
//锁前缀
global $lock_key;

$redis_lock = [
    'host'=>'',
    'port'=>'',
    'auth'=>'',
];

lock_call('k',functon(){},1);
或
if(lock_start('k')){
    ..
    lock_end();
}
*/
function lock_call($key, $call, $time = 10)
{
    global $lock_key;
    $key = $lock_key.$key;
    return helper_v3\Lock::do($key, $call, $time);
}
/**
* 开始锁
*/
function lock_start($key, $time = 1)
{
    global $lock_key;
    $key = $lock_key.$key;
    return helper_v3\Lock::start($key, $time);
}
/**
* 释放锁
*/
function lock_end()
{
    return helper_v3\Lock::end();
}

/**
* 比较日期
* Y-m-d
* $a>$b?true:false
*/
if(!function_exists('compare_date')) {
    function compare_date($a, $b)
    {
        $a = str_replace("-", "", $a);
        $b = str_replace("-", "", $b);
        if(bcsub($a, $b) > 0) {
            return true;
        } else {
            return false;
        }
    }
}

/**
* 发布消息
redis_pub("demo","welcome man");
redis_pub("demo",['title'=>'yourname']);
*/
function redis_pub($channel, $message)
{
    $redis = predis();
    if(is_array($message)) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);
    }
    $res = $redis->publish($channel, $message);
    if(function_exists('is_cli') && is_cli()) {
        echo "消息已发布给 {$res} 个订阅者。";
    }
}

/**
* 取订阅消息
redis_sub("demo",function($channel,$message){
  echo "channel ".$channel."\n";
  print_r($message);
});
*/
function redis_sub($channel, $call, $unsubscribe = false)
{
    $redis = predis();
    // 创建订阅者对象
    $sub = $redis->pubSubLoop();
    // 订阅指定频道
    $sub->subscribe($channel);
    foreach ($sub as $message) {
        // 当接收到消息时，处理消息内容
        if ($message->kind === 'message') {
            $channel = $message->channel;
            $payload = $message->payload;
            if(function_exists("is_json") && is_json($payload)) {
                $payload = json_decode($payload, true);
            }
            $call($channel, $payload);
            if($unsubscribe) {
                $sub->unsubscribe($channel);
            }
        }
    }
}
if(!function_exists("send_pusher")) {
    function send_pusher($data = [], $channel = 'netteadmin', $event = 'notice')
    {
        return helper_v3\Pusher::send($channel, $event, $data);
    }
}
if(!function_exists("think_check_sign")) {
    function think_check_sign($json_string, $key = '', $sign_key = 'sign')
    {
        $key1 = get_config("sign_secret") ?: md5('abcnetteadmin123456');
        $key  = $key ?: $key1;
        $arr  = json_decode($json_string, true);
        $ori_sign = $arr[$sign_key];
        unset($arr[$sign_key]);
        $sign = sign_by_secret($arr, $key, true);
        return $ori_sign == $sign;
    }
}
if(!function_exists("think_create_sign")) {
    function think_create_sign($arr = [], $key = '')
    {
        $key1 = get_config("sign_secret") ?: md5('abcnetteadmin123456');
        $key  = $key ?: $key1;
        return sign_by_secret($arr, $key, true);
    }
}

/**
* 取字符ascii
*
* @params $is_join. add false
*/
if(!function_exists("get_str_ord")) {
    function get_str_ord($str, $is_join = false)
    {
        $chars = str_split($str);
        $arr   = [];
        $join  = '';
        $join_sum  = 0;
        foreach ($chars as $char) {
            $ascii    = ord($char);
            if($is_join) {
                if($is_join == 'add') {
                    $join_sum = bcadd($ascii, $join_sum);
                } elseif($is_join == '.') {
                    $join .= $ascii;
                }
            } else {
                $arr[$char]    = $ascii;
            }
        }
        if($is_join) {
            return $join ?: $join_sum;
        }
        return $arr;
    }
}
if(!function_exists("gz_encode")) {
    function gz_encode($arr_or_str)
    {
        if(is_array($arr_or_str)) {
            $arr_or_str = json_encode($arr_or_str, JSON_UNESCAPED_UNICODE);
        }
        return gzencode($arr_or_str);
    }
}
if(!function_exists("gz_decode")) {
    function gz_decode($str)
    {
        $str = gzdecode($str);
        if(is_json($str)) {
            return json_decode($str, true);
        } else {
            return $str;
        }
    }
}
if(!function_exists("html_to_pdf")) {
    function html_to_pdf($input_html_file, $output_pdf_file, $return_cmd = false, $exec = false)
    {
        return helper_v3\Pdf::html_to_pdf($input_html_file, $output_pdf_file, $return_cmd, $exec);
    }
}
if(!function_exists("get_barcode")) {
    /**
    * https://github.com/picqer/php-barcode-generator/blob/main/src/BarcodeGenerator.php
    * C128 C128A C128B C128C C93 EAN13 EAN8 EAN2
    */
    function get_barcode($code, $type = 'C128', $widthFactor = 2, $height = 30, $foregroundColor = [0, 0, 0])
    {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        return "data:image/png;base64,".base64_encode($generator->getBarcode($code, $type, $widthFactor, $height, $foregroundColor));
    }
}

if(!function_exists("text_add_br")) {
    function text_add_br($text, $w, $br = '<br>')
    {
        if(!$text) {
            return;
        }
        $len = get_gbk_len($text);
        if($len > $w) {
            $total = ceil($len / $w);
            $new_text = '';
            for($i = 0;$i < $total;$i++) {
                $j = $i * $w;
                $new_text .= gbk_substr($text, $j, $w).$br;
            }
            $text = $new_text;
            if(strpos($text, $br) !== false) {
                $sub = 0 - strlen($br);
                $text = substr($text, 0, $sub);
            }
        }
        return $text;
    }
}

/**
 * 取server headers
 * host connection cache-control sec-ch-ua-platform user-agent accept accept-encoding accept-language cookie
 */
if(!function_exists("get_server_headers")) {
    function get_server_headers($name = '')
    {
        static $header;
        if($header) {
            return $name ? $header[strtolower($name)] : $header;
        }
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = [];
            foreach($result as $k => $v) {
                $header[strtolower($k)] = $v;
            }
        } else {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (str_starts_with($key, 'HTTP_')) {
                    $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }
        return $name ? $header[strtolower($name)] : $header;
    }
}
/**
* 输出js css
*/
if(!function_exists("output_js_css")) {
    function output_js_css($js = '', $css = '')
    {
        if(function_exists("cdn_url")) {
            $cdn_url = cdn_url();
        }
        if($css) {
            if(is_array($css)) {
                foreach($css as $v) {
                    echo "<link rel='stylesheet' href='" . $cdn_url . $v . "'/>";
                }
            }
            if(is_string($css)) {
                echo "<link rel='stylesheet' href='" . $cdn_url . $css . "'/>";
            }
        }
        if($js) {
            if(is_array($js)) {
                foreach($js as $v) {
                    echo "<script type='text/javascript' src='" . $cdn_url . $v . "'></script>";
                }
            }
            if(is_string($js)) {
                echo "<script type='text/javascript' src='" . $cdn_url . $js . "'></script>";
            }
        }
    }
}
/**
 * 解析文件内容
 *
 * 支持 zip pdf xml
 * pdf读取 yum install poppler-utils
 * odf转pdf  pip install mupdf
 *
 * file_parse(__DIR__.'/1.zip',__DIR__."/tmp");
 * file_parse(__DIR__.'/2.xml',__DIR__);
 *
 * @parma $file 文件支持 zip pdf xml ofd
 * @parma $zip_output_dir 主要是用于返回数组的key时把路径替换掉
 */
function file_parse($file, $zip_output_dir = '', $need_remove = false)
{
    $res = [];
    $ext = get_ext($file);
    $key = str_replace($zip_output_dir, '', $file);
    if(substr($key, 0, 1) == '/') {
        $key = substr($key, 1);
    }
    $tmp_path = PATH.'/data/tmp/'.md5($file).'/';
    switch ($ext) {
        case 'zip':
            create_dir_if_not_exists([$tmp_path]);
            if(!$zip_output_dir) {
                $zip_output_dir = $tmp_path;
            }
            $extract_dir = get_dir($zip_output_dir);
            create_dir_if_not_exists([$extract_dir]);
            zip_extract($file, $zip_output_dir);
            $all = get_deep_dir($zip_output_dir);
            foreach($all as $k => $v) {
                if(!is_file($v)) {
                    unset($all[$k]);
                }
                if(in_array(get_ext($v), ['zip','7z','gz'])) {
                    unset($all[$k]);
                }
            }
            foreach($all as $v) {
                $res = array_merge($res, file_parse($v, $zip_output_dir));
            }
            if($need_remove) {
                unlink($file);
            }
            //删除目录
            think_exec("rm -rf $tmp_path");
            break;
        case 'xml':
            $content = xml2array(file_get_contents($file));
            $res[$key] = $content;
            break;
        case 'pdf':
            create_dir_if_not_exists([$tmp_path]);
            $output_txt = $tmp_path.md5($file).'.txt';
            think_exec("pdftotext -layout $file  $output_txt");
            $res[$key] = file_get_contents($output_txt);
            unlink($output_txt);
            break;
        case 'ofd':
            create_dir_if_not_exists([$tmp_path]);
            $zip = $tmp_path.'ofd'.md5($file).'.zip';
            copy($file, $zip);
            $res = array_merge($res, file_parse($zip, $tmp_path, $need_remove = true));
            break;
        default:
            // code...
            break;
    }
    return $res;
}
/**
* 优化数量显示
* 1.10显示为1.1
* 1.05显示为1.05
* 1.00显示为1
*/
function show_number($num)
{
    return rtrim(rtrim($num, '0'), '.');
}
/**
* 取字符中的数字
*/
function get_str_number($input)
{
    $pattern = '/(\d+(\.\d+)?)/';
    preg_match_all($pattern, $input, $matches);
    return $matches[1];
}
/**
* 贝塞尔
* @param $return blob | base64
* 当为blob时 header("Content-Type: image/png");echo $blob;exit;
* 当为base64时 echo "<img src='data:image/png;base64,".$blob."' />";
*/
function line_bezier($opt = [], $return = 'base64')
{
    $front_border = $opt['front_border'] ?? '#000';
    $fill_color = $opt['fill_color'] ?? 'red';
    $background_color = $opt['background_color'] ?? '#fff';
    $stroke_opacity = $opt['stroke_opacity'] ?? '1';
    $stroke_width = $opt['stroke_width'] ?? '1';
    $width = $opt['width'] ?? '600';
    $height = $opt['height'] ?? '500';
    $stroke_width = $opt['stroke_width'] ?? '1';
    $data = $opt['data'];
    $draw = new \ImagickDraw();
    $front_border = new \ImagickPixel($front_border);
    $fill_color = new \ImagickPixel($fill_color);
    $draw->setStrokeOpacity($stroke_opacity);
    $draw->setStrokeColor($front_border);
    $draw->setFillColor($fill_color);
    $draw->setStrokeWidth($stroke_width);
    foreach($data as $k => $v) {
        if(is_string($k)) {
            if(strpos($k, '#') !== false) {
                $k = substr($k, 0, strpos($k, '#'));
            }
            call_user_func_array([$draw, $k], $v);
        } else {
            $draw->bezier($v);
        }
    }
    $imagick = new \Imagick();
    $imagick->newImage($width, $height, $background_color);
    $imagick->setImageFormat("png");
    $imagick->drawImage($draw);
    $blob = $imagick->getImageBlob();
    switch ($return) {
        case 'blob':
            return $blob;
            break;
        case 'base64':
            return base64_encode($blob);
            break;
    }
    return base64_encode($blob);
}
/**
* 数字转中文，非金额读法
*/
function num_to_chinese($num)
{
    $chinese_num_arr = array(
        '零', '一', '二', '三', '四', '五', '六', '七', '八', '九'
    );
    $chinese_num_str = '';
    $num_str = (string) $num;
    $len = strlen($num_str);
    for ($i = 0; $i < $len; $i++) {
        $chinese_num_str .= $chinese_num_arr[$num_str[$i]];
    }
    return $chinese_num_str;
}
/**
* 获取本地音视频时长
* https://github.com/JamesHeinrich/getID3
* composer require james-heinrich/getid3
*/
function get_video_time($video_local_path, $ret_time = true)
{
    if(!file_exists($video_local_path)) {
        return 0;
    }
    $id3 = new \getID3();
    $info = @$id3->analyze($video_local_path);
    $second = $info['playtime_seconds'];
    if($ret_time) {
        return (int)$second;
    } else {
        return $info;
    }
}
/**
* 目录 复制到 另一个目录
*/
function copy_dir($source, $dest)
{
    if(is_dir($dest)) {
        return;
    }
    if (!is_dir($dest)) {
        mkdir($dest);
    }
    $dir = dir($source);
    while (false !== ($entry = $dir->read())) {
        if ($entry == "." || $entry == "..") {
            continue;
        }
        $source_path = $source . '/' . $entry;
        $dest_path = $dest . '/' . $entry;
        if (is_dir($source_path)) {
            copy_dir($source_path, $dest_path);
        } else {
            $new_dir = get_dir($dest_path);
            create_dir_if_not_exists([$new_dir]);
            copy($source_path, $dest_path);
        }
    }
    $dir->close();
}
/**
* 解压zip、7z、gz、tar、bz2包
* yum -y install p7zip unar unzip
*/
function unzip_tar($input,$output_base = ''){
	$output_dir = $output_base.'/uploads/tmp/unzip/'.md5($input);
	$ext = get_ext($input);
	$cmd = "";
	$tar = "tar -xvf ".$input." -C ".$output_dir;
	if(is_dir($output_base.'/uploads/')){
	   if(is_dir($output_dir)){
		think_exec("rm -rf ".$output_dir);
	   }
	}
	create_dir_if_not_exists($output_dir);
	switch($ext){
	    case '7z':
    		$cmd = "7za x ".$input." -o".$output_dir;
    		break;
    	case 'zip':
    		$cmd = "unzip ".$input." -d ".$output_dir;
       		break;
       	case 'rar':
       		$cmd = "unar  ".$input." -o ".$output_dir;
        	break;
        case 'bz2':
        	$cmd = $tar;
        	break;
        case 'gz':
        	$cmd = $tar;
        	break;
        case 'tar':
            $cmd = $tar;
        	break; 
	}
    if($cmd){ 
    	think_exec($cmd);
    	return $output_dir;
    }
}
/**
* exec
*/
function think_exec($cmd,&$output = '',$show_err = false){
    @putenv("LANG=zh_CN.UTF-8");
    exec($cmd, $output, $return_var);
    if ($show_err && $return_var !== 0) {
        return json_error(['msg'=>'操作失败']);
    }     
}
/**
* 文件软链接
*/
function exec_ln($ori_file,$new_file){
   if(file_exists($new_file)){
       return true;
   }
   if(file_exists($ori_file)){
	$cmd = "ln -s ".$ori_file." ".$new_file;
	think_exec($cmd);
	return true;
   }
}

include __DIR__.'/inc/x.php';
include __DIR__.'/inc/sub_pub_js.php';
include __DIR__.'/inc/array.php';
include __DIR__.'/inc/scss.php';
include __DIR__.'/inc/js.php';
include __DIR__.'/inc/ext.php';
