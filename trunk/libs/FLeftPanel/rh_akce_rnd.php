<?php
class rh_akce_rnd {

static function show() {
		$cache = FCache::getInstance('f',86400);
		if(false === ($data = $cache->getData(((FUser::logon()>0)?('1'):('0')).'-logged','eventtip')) ) {
		  $itemRenderer = new FItemsRenderer();
		  $itemRenderer->setCustomTemplate('sidebar.event.tpl.html');
			$fItems = new FItems('event',false,$itemRenderer);
			$fItems->addWhere('dateStart >= NOW() or (dateEnd is not null and dateEnd >= NOW())');
			$fItems->setOrder('rand()');
			$fItems->getList(0,1);
			$data = '';
			if(!empty($fItems->data)) {
				$data = $fItems->render();
			}
			$cache->setData($data);
		}
		return $data;
	}

}