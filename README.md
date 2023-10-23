# 安装

在composer.json中添加
~~~
"thefunpower/helper": "dev-main" 
~~~

# 助手工具类或函数

需要定义`PATH`目录，项目的根目录
~~~
define("PATH",__DIR__.'/');
~~~

需要定义`WWW_PATH`目录，网站访问的目录，有时PATH与WWW_PATH是一样的
~~~
define("WWW_PATH",__DIR__.'/');
~~~

确保有`data`  `uploads`两个目录且可写.

data在根目录 uploads在网站访问的目录

## Predis Publish Subscribe 
连接 
~~~
predis($host,$port,$auth);
~~~

发布消息

~~~
redis_pub("demo","welcome man");
redis_pub("demo",['title'=>'yourname']);
~~~

取订阅消息

~~~ 
redis_sub("demo",function($channel,$message){
  echo "channel ".$channel."\n";
  print_r($message);
}); 
~~~


## Predis GEO
连接 
~~~
predis($host,$port,$auth);
~~~

获取
~~~ 
$s = predis_geo_pos('places',[
    '上海外滩','北京天安门' 
]); 
pr($s) ; 
~~~
添加
~~~
predis_add_geo('places',[
    [ 
        'lat'=>'116.397128',
        'lng'=>'39.916527',
        'title'=>'北京天安门'
    ],
    [ 
        'lat'=>'121.473701',
        'lng'=>'31.230416',
        'title'=>'上海外滩'
    ],
    [ 
        'lat'=>'121.45668',
        'lng'=>'31.21706',
        'title'=>'襄阳公园'
    ], 
]);
~~~

附近分页 
~~~
pr(predis_get_pager('places', 121.45668, 31.21706));
~~~

## RPC

服务端
~~~
class ServerGetUser{
    public function getInfo($name = 'abc'){
        return ['welcome'=>$name,'token'=>rpc_token()];
    }
}
rpc_server("ServerGetUser");
~~~

客户端
~~~
$client = rpc_client("http://127.0.0.1:5000/rpc.php");
$info = $client->getInfo("test");
print_r($info);
~~~ 

## Ftp
php.ini中开启`ftp`扩展

把本地文件同步到FTP上。

如果FTP上目录文件已存在，将会被替换。

~~~
use helper_v3\Ftp;
$ftp = Ftp::start([
    'host' =>'IP地址',
    'user' =>'帐号',
    'pwd'  =>'密码',
    'port' =>'端口，默认21', 
]);  
//上传到根目录
Ftp::put_all(__DIR__.'/uploads');
//或上传到指定目录
//Ftp::put_all(__DIR__.'/uploads','uploads');
Ftp::end();
~~~



更多方法 https://github.com/Nicolab/php-ftp-client

## PDF
~~~ 
yum install pdftk   pdftk-java  poppler-utils perl-Image-ExifTool.noarch  ImageMagick ImageMagick-devel  ghostscript -y
~~~

### 生成PDF

https://mpdf.github.io/installation-setup/installation-v7-x.html
~~~
use helper_v3\Pdf;

$mpdf = Pdf::init();
$mpdf->WriteHTML('<h1>Hello world!</h1>');
$mpdf->Output();
~~~

### 合并PDF
~~~
$input = [
    PATH.'uploads/1.pdf',
    PATH.'uploads/2.pdf',
];
$new_file = '/完整路径/1.pdf';
echo Pdf::merger($input,$new_name);
exit;
~~~

### 生成PDF table
~~~
$html = '
<style> 
table{
    width: 100%;
    text-align:left;
    margin: 0 auto;
    border: 1px solid #000000;
    border-collapse: collapse;
} 
th,td {
    border: 1px solid #000000;
    text-align: center;
}
</style>
<table   cellspacing="0" cellpadding="0" border="0"   >
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">First</th>
      <th scope="col">Last</th>
      <th scope="col">Handle</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">1</th>
      <td>Mark</td>
      <td>Otto</td>
      <td>@mdo</td>
    </tr>
    <tr>
      <th scope="row">2</th>
      <td>Jacob</td>
      <td>Thornton</td>
      <td>@fat</td>
    </tr>
    <tr>
      <th scope="row">3</th>
      <td colspan="2">Larry the Bird</td>
      <td>@twitter</td>
    </tr>
  </tbody>
</table>';
    $mpdf = Pdf::init();
    $mpdf->shrink_tables_to_fit = 1;
    $mpdf->WriteHTML($html);
    $mpdf->Output();
~~~

## Xls 
~~~
composer require phpoffice/phpspreadsheet
~~~
当前使用 `"phpoffice/phpspreadsheet": "^1.20"`


### 生成xls
~~~ 
use helper_v3\Xls;

$all = db_get("catalog_product",'*');

foreach($all as $v){
    $title = $v['title'];
    $desc = $v['desc'];
    $values[] = [
        'title'=>$title,
        'desc'=>$desc,
    ];
}
~~~

~~~
Xls::create([
    'title'=>'编号',
    'desc'=>'规格',
], $values, 'product', FALSE);
~~~

第一个worksheet
~~~
Xls::$label = $txt_month.'专票';
Xls::$sheet_width = [
    'A' => "15",
    'B' => "36",
    'C' => "30",
    'D' => "10",
    'E' => "10",
    'F' => "10",
];
~~~
更多worksheet
~~~
Xls::$works = [
    [
        'title' => $title,
        'label' => $txt_month.'普票',
        'data'  => $new_data,
        'width' => Xls::$sheet_width,
    ]
];
~~~
合并 
~~~
Xls::$merge = [
    'A18:E22' 
];
Xls::create($title, $values, $name, FALSE);
~~~


## 消息订阅

依赖 
~~~
yarn add ioredis 
yarn add ws 
~~~


1.生成server.js 
~~~
echo create_node_ws_server($ws_port=3006,$topic=['demo'],$redis_host='127.0.0.1',$port='6379',$auth='');
~~~
复制代码至`server.js`中

启动server
~~~
node server.js
~~~

2.HTML添加监听

依赖 `reconnecting-websocket.js`

https://github.com/joewalnes/reconnecting-websocket

~~~ 
<script>
<?php 
$func = " 
    data = JSON.parse(data);
    console.log(data);
";
echo get_ws_js($func,'ws://127.0.0.1:3006');
?>
</script>
~~~
其中`ws://127.0.0.1:3006` 如果是 wss 则`wss://yourdomain/wss`

3.php发送消息
~~~
redis_pub("demo",['title'=>'yourname']);
~~~

如使用wss则需配置Nginx转发
~~~
location /wss {
    proxy_pass http://127.0.0.1:3006;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    rewrite /wss/(.*) /$1 break;
    proxy_redirect off;
}
~~~

测试
~~~
redis_sub("demo",function($channel,$message){
  echo "channel ".$channel."\n";
  print_r($message);
});
~~~ 

###  pusher

https://pusher.com/

~~~
PUSHER_APP_KEY = 
PUSHER_APP_SECRET = 
PUSHER_APP_ID = 
PUSHER_APP_CLUSTER =  
~~~
 
前端需要加载JS

~~~
<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
~~~

发送消息
~~~
helper_v3\Pusher::sender($channel,$event,$data = []);
或使用
send_pusher($data = [],$channel='netteadmin',$event='notice');
~~~

### xcookie 加密

~~~
//设置
xcookie("ss",1);
xcookie("ss",['title'=>'tt']);
//读取
pr(xcookie("ss"));
//删除
xcookie_delete("ss");  
~~~


### 开源协议 

The [MIT](LICENSE) License (MIT)