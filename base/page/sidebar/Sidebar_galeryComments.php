<?php
class Sidebar_page {
	static function show() {
		$user = FUser::getInstance();
		$fItems = new FItems('forum',$user->userVO->userId);
		$fItems->setPage($pageVO->pageId);
		$fItems->setGroup('itemIdTop');
		$fItems->setOrder('itemId desc');
		

		if(!$user->pageVO) return false;
		$sideData = $user->pageVO->prop('sidebar');
		if(empty($sideData)) return false;

		return $sideData; 
	}
}