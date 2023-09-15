<?php 
/*
* Copyright (c) 2021-2031, All rights reserved.
* MIT LICENSE
*/ 
namespace helper_v3;  
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class Lock{
	
	public static $lock;
	public static function init(){
		if(self::$lock){
			return self::$lock; 
		}
		$store = new SemaphoreStore();
		return self::$lock = new LockFactory($store); 
	} 

	public static function do($key,$call,$time = 10){
		$factory = self::init();
		$lock = $factory->createLock($key);  
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
