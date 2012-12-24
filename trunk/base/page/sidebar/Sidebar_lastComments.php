<?php
class Sidebar_lastComments {
	static function show() {
	
	
		$user = FUser::getInstance();

		$fItems = new FItems('forum',$user->userVO->userId);
		$fItems->fItemsRenderer = new FItemsRenderer();
		$fItems->fItemsRenderer->setCustomTemplate('item.forum.simple.tpl.html');
		if($user->pageVO->typeId!='top') $fItems->setPage($user->pageVO->pageId);
		$fItems->setOrder('itemId desc');
		if(SITE_STRICT) $fItems->addWhere("pageIdTop='".SITE_STRICT."'");
		$fItems->addWhere('itemIdTop is not null');
		
		return $fItems->render(0,10);
		
	}
}