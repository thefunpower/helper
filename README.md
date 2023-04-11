# helper

## Xls 
~~~
composer require phpoffice/phpspreadsheet
~~~
当前使用 `"phpoffice/phpspreadsheet": "^1.20"`


### 生成xls
~~~ 
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