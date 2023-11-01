<?php 

if(!function_exists("xml2array")){
	function xml2array($xml){
		return json_decode(json_encode(simplexml_load_string($xml)),true);	
	}
}

if(!function_exists("array2xml")){
	function array2xml($arr){
		 return Spatie\ArrayToXml\ArrayToXml::convert($arr);
	}
}






