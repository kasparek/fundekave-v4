<?php
class bookedRelatedPagesList {

	static function show() {
		$user = FUser::getInstance();
		$fPages = new FPages('',$user->userVO->userId);
		$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0,sum(f1.book) as booksum');
		$fPages->addJoin('join sys_pages_favorites as f1 on p.pageId = f1.pageId');
		$fPages->addJoin("join sys_pages_favorites as f2 on f1.userId=f2.userId and f2.pageId='".$user->pageVO->pageId."' and f2.book = '1'");
		$fPages->addWhere("f1.book=1 and f1.pageId!='".$user->pageVO->pageId."'");
		$fPages->setGroup('f1.pageId');
		$fPages->setOrder('booksum desc');
		$fPages->setLimit(0,10);
		$arr = $fPages->getContent();
		$tmptext = '';
		if(!empty($arr)) {
			return FPages::printPagelinkList($arr);
		}
	}

}