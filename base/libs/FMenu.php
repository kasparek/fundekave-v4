<?php
class FMenu {
	static function topmenu(){
		$arrmenu = FDBTool::getAll("SELECT pageId,text FROM sys_menu where pageIdTop='".MENU_SET."'".((FUser::logon()>0)?(""):(' and public=1'))." ORDER BY ord");
		if(empty($arrmenu)) return array();
		foreach ($arrmenu as $ro) {
			$menuItems[] = array("LINK"=>FSystem::getUri('',$ro[0],''), "pageId"=>$ro[0], "TEXT"=>$ro[1]);
		}
		return $menuItems;
	}

	static function secondaryMenu() {
		$cache = FCache::getInstance('l');
		return $cache->getData('secondarymenu');
	}
	
	/**
	 *  before ,$buttonId='',$buttonClass='',$listItemClass='',$title='') {
	 *  options [ id, class, parentClass, title ]	 
	 **/	 	
	static function secondaryMenuAddItem($link,$text,$options=array()) {
		$button = array('LINK'=>$link,'TEXT'=>$text,'options'=>$options);
		$cache = FCache::getInstance('l');
		$secMenuCustom = $cache->getData('secondarymenu');
		$secMenuCustom[] = $button;
		$cache->setData($secMenuCustom);
	}
}