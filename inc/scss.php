<?php 

function set_scss_path($path){ 
	return SCSS::init()->set_path($path);
} 

function scss($css,$is_cached = false){ 
	if(strpos($css,chr(13))!==false){
		$code =  SCSS::init()->css($css);
	}else {
		$code =  SCSS::init()->import($css);		
	}	
	if(!$is_cached){
		return $code;
	}
	$url = '/dist/scss/'.md5($code).'.css';
	if(defined('WWW_PATH')){
		$file = WWW_PATH.$url;
	}else {
		$file = PATH.$url;
	}
	if(file_exists($file)){
		return $url;
	}
	$dir = get_dir($file);
	create_dir_if_not_exists([$dir]);
	file_put_contents($file);
	return $url;
} 
 
class SCSS{
	protected $compiler;
	static $obj;
	public function __construct(){
		$this->compiler = new ScssPhp\ScssPhp\Compiler(); 
	}
	public static function init(){
		if(!self::$obj){
			self::$obj = new Self;	
		}		
		return self::$obj;
	}

	public function css($css){ 
		return $this->compiler->compileString($css)->getCss();
	}

	public function set_path($path){
		$this->compiler->setImportPaths($path);
	}

	public function import($scss_file){ 
		if(is_array($scss_file)){
			$css = '';
			foreach($scss_file as $v){
				$css .= $this->compiler->compileString('@import "'.$v.'";')->getCss();
			}
			return $css;
		}
		return $this->compiler->compileString('@import "'.$scss_file.'";')->getCss();
	} 
} 