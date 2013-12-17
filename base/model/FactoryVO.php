<?php
class FactoryVO {

	private static $data;
	
	public static function &get($class,$id=0,$autoload=false) {
		$cache = FCache::getInstance( 's' );
		$sessionFactory = &$cache->getPointer('factory');
		if(empty($id)) {
			$uid = uniqid();
			self::$data[$class][$uid] = new $class();
			$vo = &self::$data[$class][$uid];
		} else {
			if(!isset(self::$data[$class][$id])) self::$data[$class][$id] = new $class($id); 
			$vo = &self::$data[$class][$id];
			if(!$vo->loaded && $autoload) {
				$vo->load();
			}
			//if(!isset($sessionFactory[$class][$id])) $sessionFactory[$class][$id] = new $class($id); 
			//$vo = &$sessionFactory[$class][$id];
			//if(!$vo->loaded && $autoload) $vo->load();
		}
		return $vo;
	}
	
	public static function invalidate() {
		$cache = FCache::getInstance( 's' );
		$sessionFactory = &$cache->getPointer('factory');
		while($sessionFactory) array_pop($sessionFactory);
	}

}