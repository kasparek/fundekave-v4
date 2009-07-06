<?php
class rh_galerie_rnd {
	static function show(){
		$itemRenderer = new FItemsRenderer();
		$itemRenderer->openPopup = true;
		$itemRenderer->showPageLabel = true;
		$itemRenderer->showTooltip = true;
		$itemRenderer->showTag = true;
		$itemRenderer->showText = true;

		$fItems = new FItems('galery',false,$itemRenderer);
		$fItems->thumbInSysRes = true;
		$total = $fItems->getCount();
		return $fItems->render(rand(0,$total),1);
	}
}