<?php
class rh_galerie_rnd {
	static function show() {
		return '';
		
		$cache = FCache::getInstance('f',86400);
		$ret = $cache->getData('rh_galerie_rnd','lp');
		if($ret===false) {
		
			$fi = new FItems('galery',0);
			$fi->setSelect('itemId');
			$fi->setOrder('rand()');
			$arr = $fi->getContent(0,1);
			
			$itemVO = $arr[0];
		
			$itemVO->thumbInSysRes = true;
			$ret = $itemVO->render();
		
			$cache->setData($ret,'rh_galerie_rnd','lp');
		}
		return $ret;
	}
}