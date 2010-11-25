<?php
class Sidebar_galeryRand {
	static function show() {
		$cache = FCache::getInstance('f',86400);
		$itemIdList = $cache->getData('itemIdList'.(SITE_STRICT == 1 ? HOME_PAGE : ''),'sidebar');
		if(empty($itemIdList)) {
			$allList = FDBTool::getCol("select itemId from sys_pages_items where typeId='galery' and public='1'".(SITE_STRICT == 1 ? " and pageIdTop='".HOME_PAGE."'" : ''));
			if(empty($allList)) return;
			$keyList = array_rand($allList,500);
			$itemIdList=array();
			while($k = array_pop($keyList)) $itemIdList[]=$allList[$k];
		}
		$itemVO = new ItemVO(array_pop($itemIdList),false);
		$itemVO->options=array('showPage'=>true);
		$cache->setData($itemIdList,'itemIdList','sidebar');
		return $itemVO->render().'<div style="clear:both;"></div>';
	}
}