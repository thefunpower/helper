<?php  
namespace helper_v3;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use iio\libmergepdf\Merger; 
class Pdf{  
    /**
    * HTML转PDF 
    */
    public static function html_to_pdf($input_html_file,$output_pdf_file,$return_cmd = false,$exec = true){
        $cmd = "xvfb-run --server-args='-screen 0, 1024x768x24' wkhtmltopdf --encoding utf-8 ".$input_html_file." ".$output_pdf_file;
        if($exec){
            exec($cmd);
        }
        if($return_cmd){
            return $cmd;
        }
    }
	/**
     * 合并pdf
     * 输入的数组必须是.pdf格式
     * @param $data   本地pdf文件绝对路径
     * @param $new_file 合并后的文件名 
     */
    public static function merger($data = [],$new_file)
    {
        $str = '';
        foreach ($data as $k => $file) {
            if (!file_exists($file)) {
                unset($data[$k]);
            }
            if(get_ext($file) !== 'pdf'){
                unset($data[$k]);
            }
            $str .= $file." ";
        } 
        if(!$data){
            return;
        }   
        $dir = get_dir($new_file);
        create_dir_if_not_exists([$dir]);
        exec("pdftk --version",$out);
        if($out){
            $cmd = "pdftk $str cat output ".$new_file;
            exec($cmd); 
        } else{
            $merger = new Merger;
            $merger->addIterator($data);
            $pdf    = $merger->merge();
            file_put_contents($new_file, $pdf);
        } 
        if(!file_exists($new_file)){
            throw new \Exception("合并PDF失败，建议使用pdftk，安装 yum install pdftk   pdftk-java  poppler-utils perl-Image-ExifTool.noarch  ImageMagick ImageMagick-devel  ghostscript -y ");            
        }
        return $new_file;
    }
    /**
     * 合并PDF,支持图片与pdf文件一起合并
     *
     * @param  $files   PDF绝对路径 
     * @param  $output  PDF绝对路径 
     */
    public static function merger_with_image($files = [], $output)
    {
        $new_files = [];
        foreach ($files as $k => $v) {
            $ext = strtolower(substr($v, strrpos($v, '.') + 1));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'bmp', 'gif'])) {
                $new_file = self::image_to_pdf($v, $v . '.2pdf.pdf',true);
                $files[$k] = $new_file;
                $new_files[] = $new_file;
            }
        }
        self::merger($files,$output);
        if($new_files){
            foreach($new_files as $v){
                unlink($v);
            }
        }
    }
    public static function merger2($files = [], $output)
    {
        return self::merger_with_image($files, $output);
    }
	
	/**
     * 图片转PDF
     * @param  $input  PDF绝对路径 
     * @param  $output  PDF绝对路径 
     */
    public static function image_to_pdf($input, $output,$is_wait = false)
    {
        global $covert_limit_memory;
        $limit = $covert_limit_memory?:512;
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
        $cmd = "convert -limit memory ".$limit."MB $input $output "; 
        if(!$is_wait){
            $cmd = $cmd." &";
        }
        exec($cmd,$exec_output); 
        return $output;
    }
    

	/**
     * https://mpdf.github.io/ 
     */
    public static function init($option = []){
        return self::mpdfInit('',$option);
    }
    public static function mpdfInit($font_size = 9,$more_options = [])
    { 
        $tempDir = $more_options['tempDir']?:PATH . '/data/runtime';
        unset($more_options['tempDir']);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $defaultConfig = (new  ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new  FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $options_default = [
            'tempDir' => $tempDir,
            'default_font_size' => $font_size?:9,
            'fontDir' => array_merge($fontDirs, [
                HELPER_DIR . '/font',
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
	 * @param $output_path 导出目录 
	 */
	public static function pdf_to_image($file,$saveToDir){ 
        create_dir_if_not_exists($saveToDir);
        $pages = self::get_pages($file);
        $files = [];  
        $list['page_count'] = $pages; 
        $md5 = md5($file);
        for($i=1;$i<=$pages;$i++){
            $name = '/'.$md5.'-'.$i.'.jpg';  
            $cmd = "gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=png16m -r300 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -sOutputFile=".$saveToDir.$name." -dFirstPage=".$i." -dLastPage=".$i." ".$file." &";
            exec($cmd);
            $files[] = $name;
        }   
        return $files;
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
    public static function get_info($file){ 
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
         $output['header'] = $header;
         $output['dimensions'] = explode(" ",$PageMediaDimensions);
         //2是横版，1是竖版
         $output['dimensions_type'] = $output['dimensions'][0] > $output['dimensions'][1]?2:1;   
         return $output;
    } 
    /**
     * 取pdf页数
     */
    public  static function get_pages($file){
         $cmd = "pdftk ".$file." dump_data | grep NumberOfPages";
         exec($cmd,$out);
         if($out[0]){
            return trim(str_replace("NumberOfPages:","",$out[0]));   
         }else{
            return 1;
         }
    }
	/**
     * 设置标题等信息  
     */
    public static function set_info($file,$output,$arr = []){
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
        $cmd .= " ".$file."  -o  ".$output." &";
        exec($cmd);
    } 

}