<?php
class Sidebar_galeryRand {
	static function show() {
		$cache = FCache::getInstance('f');
		$itemIdList = $cache->getData(HOME_PAGE.'fotorand','sidebar');
				
		if(empty($itemIdList)) {
			$allList = FDBTool::getCol("select itemId from sys_pages_items where typeId='galery' and public='1'".(SITE_STRICT == 1 ? " and pageIdTop='".HOME_PAGE."'" : '')." order by itemId desc");
			
			$num = 100;
			$len = count($allList);
			$steps = ceil($len/$num);
			for($i=0;$i<$num;$i++){
				$from = $i*$steps;
				$rand = rand($from,$from+$steps>$len?$len:$from+$steps);
				$itemIdList[]=$allList[$rand];
			}
			$itemIdList = array_reverse($itemIdList);
		} 
		
		$itemId = array_pop($itemIdList);

		$cache->setData($itemIdList,HOME_PAGE.'fotorand','sidebar');
				
		$itemVO = new ItemVO($itemId,false);
		$itemVO->options=array('showPage'=>true);
		$cache->setData($itemIdList,'itemIdList','sidebar');
		return $itemVO->render().'<div style="clear:both;"></div>';
	}
}