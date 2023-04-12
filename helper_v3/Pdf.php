<?php   
namespace helper_v3;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
class Pdf{  
	/**
     * 合并pdf
     * 输入的数组必须是.pdf格式
     */
    public static function merger($data = [], $save_name = null)
    {
        foreach ($data as $k => $file) {
            if (!file_exists($file)) {
                unset($data[$k]);
            }
            if(get_ext($file) !== 'pdf'){
                unset($data[$k]);
            }
        } 
        if(!$data){
            return;
        } 
        if (!$save_name) {
            $save_name = uniqid(true).mt_rand(10000,99999) . '.pdf';
        } else {
            $save_name = $save_name . '.pdf';
        }
        $path = '/uploads/pdfmerger/' . date('Ymd') . '/';
        $dir  = PATH . $path;
        create_dir_if_not_exists([$dir]);
        $new_file = $dir . $save_name;  
        $merger = new Merger;
        $merger->addIterator($data);
        $pdf    = $merger->merge();
        file_put_contents($new_file, $pdf);
        return $path . $save_name;
    }
	
	/**
     * 图片转PDF
     */
    public static function imageToPdf($input, $output)
    {
        if(is_array($input)){
            $arr = $input;
            foreach($arr as $k=>$v){
                if(!file_exists($v)){
                    unset($input[$k]);
                }
            }
            $input = implode(" ",$input);
        }
        if(!$input){
            return;
        }
        $dir = get_dir($output);
        create_dir_if_not_exists([$dir]);
        $cmd = "convert $input $output"; 
        exec($cmd,$exec_output); 
        return $output;
    }
    
    /**
     * 合并PDF,支持图片与pdf文件一起合并
     */
    public static function merger2($files, $output)
    {
        foreach ($files as $k => $v) {
            $ext = strtolower(substr($v, strrpos($v, '.') + 1));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'bmp', 'gif'])) {
                $files[$k] = self::imageToPdf($v, $v . '.2pdf.pdf');
            }
        }
        $in = "";
        foreach ($files as $v) {
            $in .= $v . " ";
        }
        $cmd = "pdftk $in cat output $output";
        exec($cmd);
    }
	/**
     * https://mpdf.github.io/ 
     */
    public static function mpdfInit($font_size = 9,$more_options = [])
    {
        $tempDir = PATH . '/data/runtime';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $defaultConfig = (new  ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new  FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $options_default = [
            'tempDir' => $tempDir,
            'default_font_size' => $font_size,
            'fontDir' => array_merge($fontDirs, [
                PATH . 'data/font',
            ]),
            'fontdata' => $fontData + [
                'simfang' => [
                    'R' => 'simfang.ttf',
                    'I' => 'simfang.ttf',
                ],
                'arial' => [
                    'R' => 'arial.ttf',
                    'I' => 'arial.ttf',
                ],
            ],
            'default_font' => 'simfang'
        ];
        $options = array_merge($options_default,$more_options);
        $pdf = new Mpdf($options);
        return $pdf;
    } 
	/**
     * PDF转图片
	 * 
	 * @param $file  本地PDF文件完整路径 
	 * @param $output_path 导出目录  uploads/pdf_to_image/
	 */
	public static function covertToImage($file,$output_path,$Quality = 100 ){
		$saveToDir = PATH.$output_path;
		create_dir_if_not_exists($saveToDir);
		$pages = self::getPages($file);
		$files = []; 
		$pdf = new Pdf($file); 
		$pdf->setOutputFormat('jpg');
		$pages = $pdf->getNumberOfPages();
		$pdf->setCompressionQuality($Quality);
		$list['page_count'] = $pages;
		$files = [];
		$md5 = md5($file);
		for($i=1;$i<=$pages;$i++){
			$name = '/'.$md5.'-'.$i.'.jpg';  
			$pdf->setPage($i)->saveImage($saveToDir.$name);
			$files[] = $output_path.$name;
		}  
		$list['files'] = $files; 
		return $list;
	}
	 /**
     * 取PDF是横排还是竖排
     Array
    (
        [header] => Array
            (
                [ModDate] => D
                [Creator] => Microsoft® PowerPoint® 2019
                [CreationDate] => D
                [Producer] => Microsoft® PowerPoint® 2019
                [Author] => Microsoft Office User
                [Title] => PowerPoint 演示文稿
            )
        文档长宽
        [dimensions] => Array
            (
                [0] => 960
                [1] => 540
            )
        2是横版，1是竖版
        [dimensions_type] => 2
    )

     */
    public static function getInfo($file){ 
         $cmd = "pdftk ".$file." dump_data ";
         exec($cmd,$out);
         $output  = [];
         $new_arr = [];
         $j = -1;
         foreach($out as  $i=>$v){ 
             if(strpos($v,':') !== false){
                 $arr = explode(':',$v);
                 $a = $arr[0];
                 $b = $arr[1]; 
                 if($a && $b){
                     $a = trim($a);
                     $b = trim($b); 
                     $new_arr[$key][$j][$a] = $b;  
                 }
             }else{
                 $key = trim($v);  
                 $j++;
             }
         }
         $lists = [];
         foreach ($new_arr as $k=>$v){  
             $output[$k] = array_values($v);
         }
         foreach($output['InfoBegin'] as $v){
             $header[$v['InfoKey']] = $v['InfoValue'];
         }
         $PageMediaDimensions = $output['PageMediaBegin'][0]['PageMediaDimensions'];
         $output = [];
         $output['header'] = $header;
         $output['dimensions'] = explode(" ",$PageMediaDimensions);
         //2是横版，1是竖版
         $output['dimensions_type'] = $output['dimensions'][0] > $output['dimensions'][1]?2:1;  
         //页数   
         $output['pages'] = $output['NumberOfPages'];
         return $output;
    } 
    /**
     * 取pdf页数
     */
    public  static function getPages($file){
         $cmd = "pdftk ".$file." dump_data | grep NumberOfPages";
         exec($cmd,$out);
         if($out[0]){
            return trim(str_replace("NumberOfPages:","",$out[0]));   
         }
    }
	/**
     * 设置标题等信息  
     */
    public static function setInfo($file,$output,$arr = []){
    	if(!file_exists($file)){
    		return;
    	}
        $cmd = "pdfjam ";
        $title    = $arr['title'];
        $author   = $arr['author'];
        $keywords = $arr['keywords'];
        $flag = false;
        //设置标题
        if($title){
            $cmd .= " --pdftitle $title ";     
            $flag = true;
        }
        //设置作者
        if($author){
            $cmd .= " --pdfauthor $author ";
            $flag = true;
        }
        //设置关键词
        if($keywords){
            $cmd .= " --pdfkeywords $keywords ";
            $flag = true;
        }
        if(!$flag){
            return;
        } 
        $this->cmd .= " ".$file."  -o  ".$output;
        exec($this->cmd);
    } 


}