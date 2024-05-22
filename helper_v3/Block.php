<?php

namespace helper_v3;

class Block
{
    public static $blocks;
    public static $block_name;

    public static function start($name)
    {
        ob_start();
        self::$block_name = $name;
    }

    public static function end()
    {
        $content = ob_get_contents();
        ob_end_clean();
        self::$blocks[self::$block_name][] = $content;
    }

    public static function output()
    {
        $list = [];
        foreach(self::$blocks as $name => $arr) {
            foreach($arr as $content) {
                $list[$name] .= $content;
            }
        }
        foreach($list as $name => $content) {
            $file = "/dist/".$name.'/'.md5($content);
            switch($name) {
                case 'css':
                    $file_name = $file.'.css';
                    self::write($file_name, $content);
                    echo '<link rel="stylesheet" href="'.$file_name.'">';
                    break;
                case 'js':
                    $file_name = $file.'.js';
                    self::write($file_name, $content);
                    echo '<script src="'.$file_name.'"></script>';
                    break;
            }
        }
    }

    public static function write($file_name, $content)
    {
        $path = WWW_PATH.$file_name;
        if(!file_exists($path)) {
            $dir = get_dir($path);
            create_dir_if_not_exists([$dir]);
            do_action("output_css_js",$content);
            file_put_contents($path, $content);
        }
    }
}