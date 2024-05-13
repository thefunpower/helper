<?php 
/**
* 是否是图片
*/
function is_image_ext($url){
    $ext = get_ext($url);
    $allow = ['jpg','jpeg','png','webp','gif','avif'];
    if($ext && in_array($allow)){
        return true;
    }else{
        return false;
    }
}
/**
* 是否是视频
*/
function is_video_ext($url){
    $ext = get_ext($url);
    $allow = ['mp4','mkv','avi','mov','rm','rmvb','webp'];
    if($ext && in_array($allow)){
        return true;
    }else{
        return false;
    }
}
/**
* 是否是音频
*/
function is_audio_ext($url){
    $ext = get_ext($url);
    $allow = ['wav','mp3','m4a'];
    if($ext && in_array($allow)){
        return true;
    }else{
        return false;
    }
}