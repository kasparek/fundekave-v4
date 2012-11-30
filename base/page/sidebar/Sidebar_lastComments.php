<?php
class Sidebar_page {
	static function show() {
	
	//last of group
	//select * from (select itemId,itemIdTop from sys_pages_items where itemIdTop is not null and typeId='forum' order by dateCreated desc) as comments group by comments.itemIdTop
	
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