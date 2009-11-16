<?php
include_once('iPage.php');
class page_ItemsLive implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;
		$userId = $user->userVO->userId;

		if(!FItems::isTypeValid($typeId)) $typeId = FItems::TYPE_DEFAULT;

		$typeId = '';
		
		$localPerPage = $user->pageVO->perPage();
		

		$itemRenderer = new FItemsRenderer();
		$itemRenderer->showPageLabel = true;
		
		/*
		$fPages = new FPages('forum', $userId);
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')).',i.itemId,p.typeId');
			//$fPages->addJoin('left join sys_pages_properties as pplastitem on pplastitem.pageId=p.pageId and pplastitem.name = "itemIdLive"');
			$fPages->addJoin('join sys_pages_items as i on i.pageId=p.pageId');
			$fPages->addWhere('i.public = 1');
			if($user->idkontrol!==true) {
				$fPages->addWhere('p.locked < 2');
			} else {
				$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			}
			$fPages->setOrder("i.itemId desc");
			$arr = $fPages->getContent(0,4);
			
			$data = FPages::printPagelinkList($arr);
			*/
		
		$fItems = new FItems('',$user->userVO->userId);
		
		$fItems->addJoin('join sys_pages as p on p.pageId=sys_pages_items.pageId');
		$fItems->addWhere('sys_pages_items.public > 0');
		
		$fItems->setOrder('sys_pages_items.itemId desc');
		$fItems->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
		$fItems->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId > 0)?(',(p.cnt-f.cnt)'):(',0')).' as newMess,sys_pages_items.itemId,sys_pages_items.typeId');
		
		$pager = new FPager(0,$localPerPage,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * $localPerPage;
		$fItems->map = false;
		$fItems->getList($from,$localPerPage+1);
		$totalItems = $fItems->getCount();

		$maybeMore = false;
		if($totalItems > ($localPerPage-$fItems->itemsRemoved)) {
			$maybeMore = true;
			array_pop($fItems->data);
		}

		if($from > 0) $totalItems += $from;

		if($totalItems > 0) {
			$pager->totalItems = $totalItems;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();

			//$tmptext = $fItems->render();

			$tmptext = FPages::printPagelinkList($fItems->data);
			if ($totalItems > $localPerPage) $tmptext .= $pager->links;

			FBuildPage::addTab(array("MAINDATA"=>$tmptext));
		}
	}
}