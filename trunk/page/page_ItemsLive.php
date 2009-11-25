<?php
include_once('iPage.php');
class page_ItemsLive implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$userId = (int) $user->userVO->userId;
		
		$cache = FCache::getInstance('f',0);
		$p = 1;
		$urlVar = FConf::get('pager','urlVar');
		if(isset($_GET[$urlVar])) $p = (int) $_GET[$urlVar];
		$cacheKey = 'live-u-'.$userId.(($p>1)?('-p-'.$p):(''));
		$cacheGrp = 'pagelist';
		$ret = $cache->getData($cacheKey,$cacheGrp);
		if($ret === false) {
			$localPerPage = $user->pageVO->perPage();
								
			$fItems = new FItems('',$user->userVO->userId);
			$fItems->addJoin('join sys_pages as p on p.pageId=sys_pages_items.pageId');
			$fItems->addWhere('sys_pages_items.public > 0');
			$fItems->setOrder('sys_pages_items.itemId desc');
			$fItems->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			$fItems->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,p.typeId'.(($userId > 0)?(',(p.cnt-f.cnt)'):(',0')).' as newMess,sys_pages_items.itemId,sys_pages_items.typeId');
			
			$pager = new FPager(0,$localPerPage,array('noAutoparse'=>1));
			$from = ($pager->getCurrentPageID()-1) * $localPerPage;
			$fItems->map = false;
			$fItems->getList($from,$localPerPage+1);
			$totalItems = count($fItems->data);
	
			$maybeMore = false;
			if($totalItems > ($localPerPage-$fItems->itemsRemoved)) {
				$maybeMore = true;
				array_pop($fItems->data);
			}
	
			if($from > 0) $totalItems += $from;
			$ret = '';
			if($totalItems > 0) {
				$pager->totalItems = $totalItems;
				$pager->maybeMore = $maybeMore;
				$pager->getPager();
				$tmptext = FPages::printPagelinkList($fItems->data);
				if ($totalItems > $localPerPage) $tmptext .= $pager->links;
				$ret = $tmptext; 
			}
			$cache->setData($ret,$cacheKey,$cacheGrp);
		}
		if(!empty($ret)) FBuildPage::addTab(array("MAINDATA"=>$ret));
	}
}