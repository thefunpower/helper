<?php

namespace helper_v3;

/**
* 天地图
* http://lbs.tianditu.gov.cn/server/search2.html
*/
class Map
{
    /**
    * 服务器端
    */
    public static function get_tk()
    {
        return get_config("tianditu");
    }
    /**
    * 浏览器端
    */
    public static function get_tk_sever()
    {
        return get_config("tianditu_js");
    }

    public static function get($url, $data = '')
    {
        $client = guzzle_http();
        $res    = $client->request('GET', $url);
        $body = (string)$res->getBody();
        return json_decode($body, true);
    }
    /**
    * 根据lat lng取地址
    */
    public static function get_address($lat, $lng)
    {
        $url = "http://api.tianditu.gov.cn/geocoder?postStr={'lon':".$lng.",'lat':".$lat.",'ver':1}&type=geocode&tk=".self::get_tk();
        $data = self::get($url);
        if($data['status'] == 0) {
            $res = $data['result'];
            $list = [];
            $list['address'] = $res['formatted_address'];
            $a = $res['addressComponent'];
            $list['parse'] = [
                'nation' => $a['nation'],
                'province' => $a['province'],
                'county' => $a['county'],
                'address' => $a['address'],
            ];
            return $list;
        }

    }
    /**
    * 根据地址取lat lng
    */
    public static function get_lat($address, $convert = 'wgs84_gcj02')
    {
        $url = 'http://api.tianditu.gov.cn/geocoder?ds={"keyWord":"'.$address.'"}&tk='.self::get_tk();
        $data = self::get($url);
        if($data['status'] == 0) {
            $lat = $data['location']['lat'];
            $lng = $data['location']['lon'];
            return MapConvert::$convert($lat, $lng);
        }
    }
}
