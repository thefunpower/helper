<?php  
/*
*   图片复制
<img id='myImage' src='data:image/png;base64, />
copy_base64_data(data); 
*/
function  copy_base64_data(){
	global $vue;
	$str = " 
	    location.origin.includes(`https://`) || Message.error(`图片复制功能不可用`);
	    data = data.split(';base64,'); let type = data[0].split('data:')[1]; data = data[1]; 
	    let bytes = atob(data), ab = new ArrayBuffer(bytes.length), ua = new Uint8Array(ab);
	    [...Array(bytes.length)].forEach((v, i) => ua[i] = bytes.charCodeAt(i));
	    let blob = new Blob([ab], { type }); 
	    navigator.clipboard.write([new ClipboardItem({ [type]: blob })]); 
	";
	if($vue){
		$vue->methd("copy_base64_data(data)",$str);
		return;
	}else{
		return $str;	
	}    
}