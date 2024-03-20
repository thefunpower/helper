<?php
/**
 * 图片相关
 */

namespace helper_v3;

class Image
{
    /**
    * 合并多个图片
    */
    public static function merger($image = [], $output, $quality = 75, $run = true)
    {
        $flag = false;
        $i = '';
        foreach ($image as $v) {
            if(file_exists($v)) {
                $i .= " ".$v." ";
                $flag = true;
            }
        }
        if(!$flag) {
            throw new \Exception("需要合并的图片不存在");
        }
        $dir = get_dir($output);
        create_dir_if_not_exists([$dir]);
        $cmd = "convert $i -append -quality ".$quality."% $output ";
        if($run) {
            exec($cmd);
        }
    }
    //取长宽
    public static function get_wh($file)
    {
        $info = getimagesize($file);
        list($width, $height, $type) = $info;
        $data = [
            'width'  => $width,
            'height' => $height,
            'type'   => $type,
            'bits'   => $info['bits'],
            'mime'   => $info['mime'],
        ];
        return $data;
    }
    //2是横版，1是竖版
    public static function get_type($file)
    {
        $info = self::get_wh($file);
        if($info['width'] > $info['height']) {
            return 2;
        } else {
            return 1;
        }
    }
    /**
     * 从内容中取本地图片
     */
    public static function get_local_img_tag($content, $all = true)
    {
        $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
        preg_match_all($preg, $content, $out);
        $img = $out[2];
        if($img) {
            $num = count($img);
            for($j = 0;$j < $num;$j++) {
                $i = $img[$j];
                if((strpos($i, "http://") !== false || strpos($i, "https://") !== false) && strpos($i, base_url()) === false) {
                    unset($img[$j]);
                }
            }
        }
        if($all === true) {
            return array_unique($img);
        }
        return $img[0];
    }
    /**
     * 从内容中取所有图片
     */
    public static function get_img_tag($content, $all = true)
    {
        $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
        preg_match_all($preg, $content, $out);
        $img = $out[2];
        if($all === true) {
            return $img;
        }
        return $img[0];
    }
    /**
     * 从内容中删除图片
     */
    public static function remove_img_tag($content, $all = false)
    {
        $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
        $out = preg_replace($preg, "", $content);
        return $out;
    }

}
