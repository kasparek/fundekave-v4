<?php
class FMenu {
	static function topmenu(){

		$cache = FCache::getInstance('s');
		if(false === ($menuItems=$cache->getData('top','menu'))) {
			$userId = FUser::logon();
			$q = "SELECT pageId,text FROM sys_menu where pageIdTop='".HOME_PAGE."'".(($userId>0)?(""):(' and public=1'))." ORDER BY ord";
			$arrmenu = FDBTool::getAll($q); //,'tMenu','default','s',0);
			foreach ($arrmenu as $ro) {
				$menuItems[] = array("LINK"=>FSystem::getUri('',$ro[0],''), "pageId"=>$ro[0], "TEXT"=>$ro[1]);
			}
			$cache->setData($menuItems);
		}
		return $menuItems;
	}

	static function secondaryMenu($menu) {
		$cache = FCache::getInstance('s');
		$user = FUser::getInstance();
		if(false === ($menuItems = $cache->getData('second-'.$user->pageId,'menu'))) {
			$user = FUser::getInstance();
			$q = "SELECT s.pageId, s.name
      	FROM sys_menu_secondary as s 
      	INNER JOIN sys_pages as p ON p.menuSecondaryGroup=s.menuSecondaryGroup 
      	WHERE ".(($user->idkontrol)?(''):("s.public=1 AND "))." p.pageId='".$user->pageVO->pageId."' ORDER BY s.ord,s.name";
			$arrmnuTmp = FDBTool::getAll($q);//,$user->pageVO->pageId.'sMenu','default','s',0);

			if(!empty($arrmnuTmp)) {
				foreach ($arrmnuTmp as $row) {
					$menuItems[]=array('LINK'=>FSystem::getUri('',$row[0]),'TEXT'=>$row[1]);
				}
			} else $menuItems = array();
			
			$cache->setData($menuItems,'second-'.$user->pageId,'menu');
		}
	
		$cache = FCache::getInstance('l');
		if(false !== ($secMenuCustom = $cache->getData('user-'.$user->pageId,'menu')) ) {
			
			$menuItems = array_merge($secMenuCustom,$menuItems);
			
		}
			
		return($menuItems);
	}
	
	static function secondaryMenuAddItem($link,$text,$opposite='0',$buttonId='',$buttonClass='',$listItemClass='') {
		$button = array('LINK'=>$link,'TEXT'=>$text);
		if( $opposite != 0 ) $button['OPPOSITE'] = 1;
		if( $buttonId != "" ) $button['ID'] = $buttonId;
		if( $buttonClass != "" ) $button['CLASS'] = $buttonClass;
		if( $listItemClass != "" ) $button['LISTCLASS'] = $listItemClass;
		$user = FUser::getInstance();
		$cache = FCache::getInstance('l');
		$secMenuCustom = $cache->getData('user-'.$user->pageId,'menu');
		$secMenuCustom[] = $button;
		$cache->setData($secMenuCustom);
	}
}