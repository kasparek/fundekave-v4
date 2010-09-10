<?php
class rh_galerie_rnd {
	static function show() {
		$cache = FCache::getInstance('f',86400);
		$ret = $cache->getData('rh_galerie_rnd','lp');
		if($ret===false) {
		$itemRenderer = new FItemsRenderer();
		$itemRenderer->openPopup = false;
		$itemRenderer->showTag = true;
		$itemRenderer->showText = true;
				
			$fi = new FItems('galery',0);
			$fi->setSelect('itemId');
			$fi->setOrder('rand()');
			$arr = $fi->getContent(0,1);
			
			$itemVO = $arr[0];
		
			$itemVO->thumbInSysRes = true;
			$ret = $itemVO->render($itemRenderer);
		
			$cache->setData($ret,'rh_galerie_rnd','lp');
		}
		return $ret;
	}
}