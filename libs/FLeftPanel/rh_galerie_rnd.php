<?php
class rh_galerie_rnd {
static function show(){
		$cache = FCache::getInstance('f',180);
		if(false === ($data = $cache->getData((FUser::logon()>0)?('member'):('nonmember'),'fotornd'))) {

			$itemRenderer = new FItemsRenderer();
			$itemRenderer->openPopup = true;
			$itemRenderer->showPageLabel = true;
			$itemRenderer->showTooltip = true;
			$itemRenderer->showTag = true;
			$itemRenderer->showText = true;

			$fItems = new FItems('galery',false,$itemRenderer);
			$fItems->thumbInSysRes = true;
			$total = $fItems->getCount();
			$data = $fItems->render(rand(0,$total),1);
			$cache->setData($data);
		}
		return $data;
	}
}