<?php
include_once('iPage.php');

class page_fotoMashup implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {
		
	}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
		$cache = FCache::getInstance('f',86400);
		$itemIdList = $cache->getData('fotomashup','sidebar');
		
		if(empty($itemIdList)) {
			$allList = FDBTool::getCol("select itemId from sys_pages_items where typeId='galery' and public='1'".(SITE_STRICT == 1 ? " and pageIdTop='".HOME_PAGE."'" : ''));
			$keyList = array_rand($allList,500);
			$itemIdList=array();
			while($k = array_pop($keyList)) $itemIdList[]=$allList[$k];
		} 
		
		$data='';
		
		for($i=0;$i<100;$i++){
			$itemVO = new ItemVO(array_pop($itemIdList),true);
			$data .= '<a href="'.FSystem::getUri('i='.$itemVO->itemId,'','').'"><img src="'.$itemVO->thumbUrl.'" class="leftbox" /></a>';
		}
				
		FBuildPage::addTab(array("MAINDATA"=>$data,"MAINID"=>'fotoMashup'));
		
		$cache->setData($itemIdList,'itemIdList','sidebar');
	}
}