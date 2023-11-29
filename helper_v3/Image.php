<?php 
namespace helper_v3;  
class Image{
	/**
	* 合并多个图片
	*/
	public static function merger($image = [],$output){ 
		$flag = false;
		$i = '';
		foreach ($image as $v){
			if(file_exists($v)){
				$i .= " ".$v." ";
				$flag = true;	
			}		    
		}
		if(!$flag){
			throw new \Exception("需要合并的图片不存在");			
		}
		$dir = get_dir($output);
        create_dir_if_not_exists([$dir]);  
		exec("convert $i -append $output ");
	}
}

