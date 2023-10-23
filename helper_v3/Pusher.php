<?php 
/*
* Copyright (c) 2021-2031, All rights reserved.
* MIT LICENSE
*/ 
namespace helper_v3;  
class Pusher{  
  static $_pusher;

  public static function init(){ 
    if(self::$_pusher){
      return self::$_pusher;
    }
    self::$_pusher = new \Pusher\Pusher(
      get_config("PUSHER_APP_KEY"), 
      get_config("PUSHER_APP_SECRET"), 
      get_config("PUSHER_APP_ID"), 
      [
        'cluster' => get_config('PUSHER_APP_CLUSTER')
      ]
    );  
    return self::$_pusher;
  }

  public static function send($channel,$event,$data = [] ){
    $p = self::init();
    return $p->trigger($channel,$event,$data); 
  }

}