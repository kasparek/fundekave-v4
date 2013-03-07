<?php
class sidebar_galeryRand {
	static function show() {
		$cache = FCache::getInstance('f');
		$itemIdList = $cache->getData('fotorand','sidebar/galeryRand');
		if(empty($itemIdList)) {
			$allList = FDBTool::getCol("select itemId from sys_pages_items where typeId='galery' and public='1'".(SITE_STRICT ? " and pageIdTop='".SITE_STRICT."'" : '')." order by itemId desc");
			if(empty($allList)) return;
			$num = 100;
			$len = count($allList);
			if($len<$num) $num=$len;
			$steps = ceil($len/$num);
			for($i=0;$i<$num;$i++){
				$from = $i*$steps;
				$rand = rand($from,$from+$steps > $len ? $len : $from+$steps);
				if(isset($allList[$rand])) $itemIdList[] = $allList[$rand];
			}
			$itemIdList = array_reverse($itemIdList);
		} 
		$itemId = array_pop($itemIdList);
		if(empty($itemId)) return;

		$cache->setData($itemIdList,'fotorand','sidebar/galeryRand');
				
		$itemVO = new ItemVO($itemId,false);
		$itemVO->options=array('showPage'=>true);
		return '<h2><a href="?k=gamas-foto-mix">Foto mix</a></h2>'.$itemVO->render().'<div style="clear:both;"></div>';
	}
}