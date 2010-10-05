<?php
class FMenu {
	static function topmenu(){
		$q = "SELECT pageId,text FROM sys_menu where pageIdTop='".HOME_PAGE."'".((FUser::logon()>0)?(""):(' and public=1'))." ORDER BY ord";
		$arrmenu = FDBTool::getAll($q);
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
	 *  before ,$opposite='0',$buttonId='',$buttonClass='',$listItemClass='',$title='') {
	 *  options [ id, class, parentClass, title ]	 
	 **/	 	
	static function secondaryMenuAddItem($link,$text,$options) {
		$button = array('LINK'=>$link,'TEXT'=>$text,'options'=>$options);
		$cache = FCache::getInstance('l');
		$secMenuCustom = $cache->getData('secondarymenu');
		$secMenuCustom[] = $button;
		$cache->setData($secMenuCustom);
	}
}