<?php
class relatedPagesList {
	static function show() {
		$user = FUser::getInstance();
		$fPages = new FPages('',$user->userVO->userId);
		$fPages->addJoin('join sys_pages_relations as r on p.pageId = r.pageIdRelative');
		$fPages->addWhere('r.pageId="'.$user->pageVO->pageId.'"');
		$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0');
		$arr = $fPages->getContent();
		$tmptext = '';
		if(!empty($arr)) {
			return FPages::printPagelinkList($arr);
		}
	}
}