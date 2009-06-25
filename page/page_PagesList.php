<?php
include_once('iPage.php');
class page_PagesList implements iPage {

	static function process($data) {

	}

	static function build() {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;
		if ( $userId > 0 ) {
			FForum::clearUnreadedMess();
			FItems::afavAll($userId);
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
		$fPages = new FPages($user->pageVO->typeIdChild,$user->userVO->userId);
		if($catId > 0) {
			$arrForums = $fPages->getListByCategory($catId);
			FBuildPage::addTab(array("MAINDATA"=>FPages::printPagelinkList($arrForums)));
		} else {
				
			$totalItems = $fPages->getCount();
			$from = 0;
			
			$pagerStr = '';
			if($totalItems > DEFAULT_PERPAGE) {
				$pager = FSystem::initPager($totalItems,DEFAULT_PERPAGE);
				$from =($pager->getCurrentPageID()-1) * DEFAULT_PERPAGE;
				$pagerStr = $pager->links;
			}

			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId>0)?(',(p.cnt-f.cnt) as newMess'):(',0')));
			$fPages->addWhere('p.locked < 2');
			if ($userId>0) {
				$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			}
			$fPages->setOrder("p.dateUpdated desc,p.name");
			
			$arrForums = $fPages->getContent($from,DEFAULT_PERPAGE);
				
			FBuildPage::addTab(array("MAINDATA"=>FPages::printPagelinkList($arrForums).$pagerStr));
		}
	}
}
