<?php
class rh_galerie_rnd {
	static function show() {

		$itemRenderer = new FItemsRenderer();
		$itemRenderer->openPopup = false;
		$itemRenderer->showTag = true;
		$itemRenderer->showText = true;
		$itemRenderer->showTooltip = false;

		$cache = FCache::getInstance('d');
		$arr = $cache->getData('rndGaleryItemId','spanel');
		
		$reLoad = true;
		if(is_array($arr)) {
			$len = count($arr); 
			if($len > 0) {
				$item = array_shift($arr);
				//---update in cache
				if($len > 1) {
					$cache->setData($arr);
					$reLoad = false;
				} 
			}
		} 

		if($reLoad === true) {
			$fi = new FItems('galery',FUser::logon());
			$fi->setSelect('itemId');
			$fi->setOrder('rand()');
			$arr = $fi->getContent(0,100);
			$item = array_shift($arr);
			//---update in cache
			$cache->setData($arr);
		}
		
		$itemVO = new ItemVO($item['itemId'],true,array('type'=>'galery'));
		$itemVO->thumbInSysRes = true;
		return $itemVO->render($itemRenderer);
	}
}