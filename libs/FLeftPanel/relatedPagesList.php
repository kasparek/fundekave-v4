<?php
class relatedPagesList {
static function show() {
		$user = FUser::getInstance();
		$cache = FCache::getInstance('f',86400);
		if(false === ($tmptext = $cache->getData($user->pageVO->pageId.'-page-'.($user->userVO->userId*1).'-user','pagesrelated'))) {
			$fPages = new FPages('',$user->userVO->userId);
			$fPages->addJoin('join sys_pages_relations as r on p.pageId = r.pageIdRelative');
			$fPages->addWhere('r.pageId="'.$user->pageVO->pageId.'"');

			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0');

			$arr = $fPages->getContent();
			$tmptext = '';
			if(!empty($arr)) {
				$tmptext = FPages::printPagelinkList($arr);
			}
			$cache->setData($tmptext);
		}
		return $tmptext;
	}
}