<?php
class FCommand {

	private static $instance;
	private static $allowInstantiation = false;
	
	function __construct() {
		if(self::$allowInstantiation==true) {
			
		} else {
			throw new Exception('Instantioation denied - SINGLETON - use getinstance.');
		}
	}
	
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$allowInstantiation = true; 
			self::$instance = &new FCommand();
			self::$allowInstantiation = false;
		}
		return self::$instance;
	}
	
	public $commandMap;
	
	public static function run($action) {
		$inst = FCommand::getInstance();
		if(empty($inst->commandMap[$action])) return;
		foreach($inst->commandMap[$action] as $function) {
			call_user_func('FCommand::'.$function);
		}
	}

	public static function register($action,$function) {
	   $inst = FCommand::getInstance();
	   if(!$inst->commandMap) $inst->commandMap = array();
	   if(empty($inst->commandMap[$action])) $inst->commandMap[$action]=array();
	   $inst->commandMap[$action][] = $function;
	}

	public static function flushCache() {
	   $cache = FCache::getInstance('f');
			$cache->invalidateGroup('calendarlefthand');
	}
	
	
}