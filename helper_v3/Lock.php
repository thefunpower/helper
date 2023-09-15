<?php 
/*
* Copyright (c) 2021-2031, All rights reserved.
* MIT LICENSE
*/ 
namespace helper_v3;  
use Symfony\Component\Lock\LockFactory; 
use Symfony\Component\Lock\Store\RedisStore;
/**
https://symfony.com/doc/current/components/lock.html
global $redis_lock; 
$redis_lock = [
	'host'=>'',
	'port'=>'',
	'auth'=>'',
];
*/

class Lock{

	public static $lock;  
	public static $factory;  
	public static $key;  

	public static function init(){
		global $redis_lock; 
		if(self::$factory){
			return self::$factory; 
		} 
		$redis = new \Redis();
		$redis->connect($redis_lock['host'],$redis_lock['port']); 
		$redis->auth($redis_lock['auth']);
		$store = new RedisStore($redis); 
		self::$factory = new LockFactory($store); 
		return self::$factory;
	} 

	public static function start($key,$time = 10){
		self::$key = $key;
		$lock = $factory->createLock($key,$time,false);  
		self::$lock = $lock;
		if (!$lock->acquire()) {
		    return;
		}
		return true;
	}

	public static function end()
	{
		self::$lock->release();
	}

	public static function do($key,$call,$time = 10){
		$factory   = self::init(); 
		self::$key = $key;
		$lock = $factory->createLock($key,$time,false);  
		if (!$lock->acquire()) {
		    return;
		}
		try {
		    $call();
		} finally {
		    $lock->release();
		}
	} 
}
