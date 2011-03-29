<?php

//define constants
define("ITEM_UPDATED","actionItemUpdated");

define("ITEM_INSERTED","actionItemInserted");
define("ITEM_DELETED","actionItemDeleted");

define("ITEM_READED","actionItemReaded");
define("PAGE_UPDATED","actionPageUpdated");
define("RSS_UPDATED","actionRSSUpdated");
define("AVATAR_UPDATED","avatarUpdated");
define("POSITION_UPDATED","positionUpdated");
define("CATEGORIES_UPDATED","categoriesUpdated");

//map commands
FCommand::register(ITEM_UPDATED,'itemUpdated');

FCommand::register(ITEM_INSERTED,'itemInserted');
FCommand::register(ITEM_DELETED,'itemDeleted');

FCommand::register(ITEM_UPDATED,'flushCache');
FCommand::register(PAGE_UPDATED,'pageUpdated');
FCommand::register(ITEM_READED,'itemReaded');
FCommand::register(RSS_UPDATED,'rssUpdated');
FCommand::register(AVATAR_UPDATED,'avatarUpdated');
FCommand::register(POSITION_UPDATED,'positionUpdated');
FCommand::register(CATEGORIES_UPDATED,'categoriesUpdated');


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
	public static function avatarUpdated($data) {
		$user = FUser::getInstance();
		$url = FConf::get('galery','targetUrlBase').str_replace("crop","flush",FConf::get('galery','avatar_thumbCut')).'/';
		$url .= strtolower($user->userVO->name).'/profile/';
		file_get_contents($url);
		FSystem::superInvalidate('avatar',$data);
		$cache = FCache::getInstance('d');
		$cache->invalidateData($data,'profileFiles');
	}
	
	public static function itemUpdated($data) {
		$cache = FCache::getInstance( 's' );
		$unreadedList = &$cache->getPointer('unreadedItems');
		$unreadedList=array();
		FSystem::superInvalidate('page/'.$data->get('pageId').'/item',$data->itemId);
		FSystem::superInvalidate('page/'.$data->get('pageId').'/item',$data->itemId.'detail');
	}
	
	public static function itemInserted($data) {
	   //update total my num
		$user = FUser::getInstance();
		if($user->idkontrol === true) {
			$user = FUser::getInstance();
			$user->updateTotalItemsNum(true);
		}
	}
	
	public static function itemDeleted($data) {
	   //update total my num
		$user = FUser::getInstance();
		if($user->idkontrol === true) {
			$user = FUser::getInstance();
			$user->updateTotalItemsNum(true);
		}
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
	}
	
	public static function pageUpdated($data) {
		FSystem::superInvalidate('page/top');
		FSystem::superInvalidate('page/'.$data->pageId);
		
		FSystem::superInvalidate('pages/'.$data->get('typeId'));
		FSystem::superInvalidate('pages/all');
	}
	
	public static function rssUpdated($data=null) {
		FSystem::superInvalidate('page/'.$data->pageId.'/rss');
	}
	
	public static function flushCache($data) {
		FSystem::superInvalidate('page/top');
		FSystem::superInvalidate('page/'.$data->get('pageId').'/list');
		FSystem::superInvalidate('page/'.$data->get('pageId').'/rss');
		
		FSystem::superInvalidate('pages/'.$data->get('typeId'));
		FSystem::superInvalidate('pages/all');
	}
	
	public static function positionUpdated($data) {
	  FSystem::superInvalidate('sidebar/map');
	}
	
	public static function categoriesUpdated($data) {
	  FSystem::superInvalidate('sidebar/categories');
	}
	
}