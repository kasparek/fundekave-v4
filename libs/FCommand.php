<?php

define("ITEM_UPDATED","actionItemUpdated");

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
		if (empty(self::$instance)) {
			self::$allowInstantiation = true; 
			self::$instance = new FCommand();
			self::$allowInstantiation = false;
		}
		return self::$instance;
	}
	
	public $commandMap;
	
	public static function run($action,$data=null) {
		$inst = FCommand::getInstance();
		if(empty($inst->commandMap[$action])) return;
		foreach($inst->commandMap[$action] as $function) {
			call_user_func('FCommand::'.$function,$data);
		}
	}

	public static function register($action,$function) {
	   $inst = FCommand::getInstance();
	   if(!$inst->commandMap) $inst->commandMap = array();
	   if(empty($inst->commandMap[$action])) $inst->commandMap[$action]=array();
	   $inst->commandMap[$action][] = $function;
	}

	//COMMANDS
	public static function itemUpdated($data) {
		$cache = FCache::getInstance('f');
		$cache->invalidateData($data->itemId,'renderedItem');
		$cache->invalidateData($data->itemId.'detail','renderedItem');
	}
	
	public static function flushCache() {
		$cache = FCache::getInstance('f');
		$cache->invalidateGroup('calendarlefthand');
		$cache = FCache::getInstance('f');
		$cache->invalidateGroup('pagelist');
	}
	
	
}