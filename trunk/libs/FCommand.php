<?php

//define constants
define("ITEM_UPDATED","actionItemUpdated");
define("ITEM_READED","actionItemReaded");
define("PAGE_UPDATED","actionPageUpdated");
define("RSS_UPDATED","actionRSSUpdated");

//map commands
FCommand::register(ITEM_UPDATED,'itemUpdated');
FCommand::register(ITEM_UPDATED,'flushCache');
FCommand::register(PAGE_UPDATED,'pageUpdated');
FCommand::register(PAGE_UPDATED,'flushCache');
FCommand::register(ITEM_READED,'itemReaded');
FCommand::register(RSS_UPDATED,'rssUpdated');

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
		$cache = FCache::getInstance( 's' );
		$unreadedList = &$cache->getPointer('unreadedItems');
		$unreadedList=array();
		$cache = FCache::getInstance('f');
		$cache->invalidateData($data->itemId,'renderedItem');
		$cache->invalidateData($data->itemId.'detail','renderedItem');
	}
	
	public static function itemReaded($data) {
		if(empty($data)) return;
		if(!is_array($data)) $data = array($data); 
	
		$cache = FCache::getInstance( 's' );
		$unreadedList = &$cache->getPointer('unreadedItems');
		
	  if(!empty($unreadedList)) {
			  $newList = array();
				foreach($unreadedList as $itemIdUnreaded) {
					if(!in_array($itemIdUnreaded,$data)) {
						$newList[] = $itemIdUnreaded; 
					}
				}
 				$unreadedList = $newList;
		}
		
		$user= FUser::getInstance();
		$cache = FCache::getInstance('f');
		$cache->invalidateGroup('itemlist'.$user->pageVO->pageId);
	}
	
	public static function pageUpdated($data) {
		
	}
	
	public static function rssUpdated($data) {
	  $cache = FCache::getInstance('f');
	  $cache->invalidateGroup('rsslist'.$data->pageId);
	}
	
	public static function flushCache($data) {
		$cache = FCache::getInstance('f');
		$cache->invalidateGroup('calendarlefthand');
		$cache->invalidateGroup('rsslist');
		$cache->invalidateGroup('itemlist');
		$cache->invalidateGroup('itemlist'.$data->get('pageId'));
	}
	
	
}