# 助手工具类或函数

需要定义`PATH`目录
~~~
define("PATH",__DIR__.'/');
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

$mpdf = Pdf::mpdfInit();
$mpdf->WriteHTML('<h1>Hello world!</h1>');
$mpdf->Output();
~~~

### 合并PDF
~~~
$input = [
    PATH.'uploads/1.pdf',
    PATH.'uploads/2.pdf',
];
$new_name = 'test';
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
    $mpdf = Pdf::mpdfInit();
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