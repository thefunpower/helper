<?php   
/*
* Copyright (c) 2021-2031, All rights reserved.
* MIT LICENSE
*/ 
namespace helper_v3;
use League\Csv\Reader; 
use League\Csv\Statement;
use League\Csv\Writer;
class Csv{

	public static function init(){
		static $_csv_init;
		if($_csv_init){
			return;
		}
		$_csv_init = true;
		if (!ini_get('auto_detect_line_endings')) {
		    ini_set('auto_detect_line_endings', '1');
		}
	}
	/**
	* 读csv文件
	*/
	public static function reader($file){
		self::init();
		if(!file_exists($file)){
			throw new \Exception("CSV文件不存在"); 
		}
		$stream = fopen($file, 'r');
		$csv    = Reader::createFromStream($stream); 
		$all    = [];
		foreach ($csv as $v) {
		   $list = [];
		   if(is_array($v)){
		   	foreach($v as $vv){
		   		if($vv){
		   			$vv = to_utf8($vv);
		   		}
		   		$list[] = $vv;
		   	}
		   }
		   $all[] = $list;
		}
		return $all;
	}
	/** 
	* 写CSV文件
	$header  = ['first name', 'last name', 'email'];
	$content = [
	    [1, 2, 3],
	    ['foo', 'bar', 'baz'],
	    ['john', 'doe', 'john.doe@example.com'],
	];
	*/
	public static function writer($file,$header = [],$content = []){  
		self::init();
		if(!$file || !$header || !$content || !is_array($header) || !is_array($content)){
			throw new \Exception("写入CSV文件参数异常"); 
		}
		$csv = Writer::createFromString(); 
		$csv->insertOne($header); 
		$csv->insertAll($content);
		$str = $csv->toString();
		if(function_exists('get_dir')){
			$dir = get_dir($file);
			if(!is_dir($dir)){
				mkdir($dir,0777,true);
			}
		} 
		return file_put_contents($file,$str);
	} 
}