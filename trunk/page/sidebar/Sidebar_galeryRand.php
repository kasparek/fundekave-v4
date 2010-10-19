<?php
class Sidebar_galeryRand {
	static function show() {
		$cache = FCache::getInstance('f',86400);
		$itemIdList = $cache->getData('itemIdList','sidebar');
		if(empty($itemIdList)) {
			$allList = FDBTool::getCol("select itemId from sys_pages_items where typeId='galery' and public='1'");
			$keyList = array_rand($allList,500);
			$itemIdList=array();
			while($k = array_pop($keyList)) $itemIdList[]=$allList[$k];
		}
		$itemVO = new ItemVO(array_pop($itemIdList),false);
		$itemVO->options=array('showPage'=>true);
		$cache->setData($itemIdList,'itemIdList','sidebar');
		//$itemVO->thumbInSysRes = true;
		return $itemVO->render().'<div style="clear:both;"></div>';
	}
}