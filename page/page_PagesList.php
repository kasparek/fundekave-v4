<?php
include_once('iPage.php');
class page_PagesList implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		
		$cache = FCache::getInstance('s');
		$pageSearchCache = $cache->getData($user->pageVO->pageId,'search');
		if(!isset($pageSearchCache['filtrStr'])) $pageSearchCache['filtrStr']='';
		if(!isset($pageSearchCache['categoryId'])) $pageSearchCache['categoryId']='0';

		if(isset($data['kat'])) {
			$catId = (int) $data["kat"];
			if($catId != $pageSearchCache['categoryId']) {
				$pageSearchCache['categoryId'] = $catId;
			}
		}

		if(isset($data['filtr'])) {
			if($data['filtr'] !== $pageSearchCache['filtrStr']) {
				$pageSearchCache['filtrStr'] = FSystem::textins($data['filtr'],array('plainText'=>1));
			}
		}
		
		$pageSearchCache = $cache->setData($pageSearchCache,$user->pageVO->pageId,'search');
	}

	static function build() {
		$user = FUser::getInstance();
		$cache = FCache::getInstance('s');
		$pageSearchCache = $cache->getData($user->pageVO->pageId,'search');
		
		$userId = $user->userVO->userId;
		if ( $userId > 0 ) {
			FForum::clearUnreadedMess();
			FItems::afavAll( $userId );
		}

		//category list
		$category = new FCategory('sys_pages_category','categoryId');
		FBuildPage::addTab(array("MAINDATA"=>$category->getList($user->pageVO->typeIdChild)));

		$catId = 0;
		if(isset($_REQUEST['kat'])) {
			if($_REQUEST['kat']>0) {
				$catId = (int) $_REQUEST['kat'];
			}
		}
		
		$fPages = new FPages($user->pageVO->typeIdChild,$userId);
		
		if($catId > 0) {
			//---category selected - list only category
			$arrForums = $fPages->getListByCategory($catId);
			FBuildPage::addTab(array("MAINDATA"=>FPages::printPagelinkList($arrForums)));
		} else {
			$fPages->cacheResults = 's';
			//---NO category selected
			//---show search form
			
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId>0)?(',(p.cnt-f.cnt) as newMess'):(',0')));
			$fPages->addWhere('p.locked < 2');
			if ($userId>0) {
				$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			}
			
			$totalItems = $fPages->getCount();
			$from = 0;
			
			$pagerStr = '';
			$perPage = $user->pageVO->perPage();
			if($totalItems > $perPage) {
				$pager = new FPager($totalItems,$perPage);
				$from =($pager->getCurrentPageID()-1) * $perPage;
				$pager->getPager();
				$pagerStr = $pager->links;
			}
			
			$fPages->setOrder("p.dateUpdated desc,p.name");
			
			$arrForums = $fPages->getContent($from,$perPage);
				
			FBuildPage::addTab(array("MAINDATA"=>FPages::printPagelinkList($arrForums).$pagerStr));
		}
	}
}
