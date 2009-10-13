<?php
class rh_galerie_rnd {
	static function show() {
		return '';
		
		$itemRenderer = new FItemsRenderer();
		$itemRenderer->openPopup = true;
		$itemRenderer->showPageLabel = true;
		$itemRenderer->showTooltip = true;
		$itemRenderer->showTag = true;
		$itemRenderer->showText = true;

		$cache = FCache::getInstance('d');
		$arr = $cache->getData('rndGaleryItemId','spanel');
		
		$reLoad = true;
		if(is_array($arr)) {
			$len = count($arr); 
			if($len > 0) {
				$itemId = array_shift($arr);
				//---update in cache
				if($len > 1) {
					$cache->setData($arr);
					$reLoad = false;
				} 
			}
		} 
		if($reLoad===true) {
			$q = "select itemId from sys_pages_items where typeId='galery' order by rand() limit 0,300";
			$arr = FDBTool::getCol($q);
			$itemId = array_shift($arr);
			//---update in cache
			$cache->setData($arr);
		}
		
		$itemVO = new ItemVO($itemId,true,array('type'=>'galery'));
		$itemVO->thumbInSysRes = true;
		return $itemVO->render($itemRenderer);
	}
}