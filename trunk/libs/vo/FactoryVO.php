<?php
class FactoryVO {

	private static $anonymous;
	private static $data;
	
	public static function &get($class,$id=0,$autoload=false) {
		if(empty($id)) return self::$data[$class][uniqid()] = new $class();
		if(!isset(self::$data[$class][$id])) self::$data[$class][$id] = new $class($id); 
		$vo = &self::$data[$class][$id];
		if(!$vo->loaded && $autoload) $vo->load();
		return $vo;
	}

}