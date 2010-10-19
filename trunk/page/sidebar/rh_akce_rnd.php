<?php
class rh_akce_rnd {

	static function show() {
		return '';
		
		$cache = FCache::getInstance('f', 86400);
		$ret=$cache->getData('rh_akce_rnd','lp');
		if($ret===false) {
				
		$itemRenderer = new FItemsRenderer();
		$itemRenderer->setCustomTemplate('sidebar.event.tpl.html');
		$fItems = new FItems('event',false,$itemRenderer);
		$fItems->addWhere('dateStart >= NOW() or (dateEnd is not null and dateEnd >= NOW())');
		$fItems->setOrder('rand()');
		$fItems->getList(0,1);
		$data = '';
		if(!empty($fItems->data)) {
			$ret = $fItems->render();
		}
		$cache = FCache::getInstance('f', 86400);
		$cache->setData($ret,'rh_akce_rnd','lp');
		}
		return $ret;
	}
}