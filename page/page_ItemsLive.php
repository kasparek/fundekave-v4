<?php
include_once('iPage.php');
class page_ItemsLive implements iPage {

	static function process($data) {

	}

	static function build() {
		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;

		if(!FItems::isTypeValid($typeId)) $typeId = FItems::TYPE_DEFAULT;

		$localPerPage = $user->pageVO->perPage();

		$itemRenderer = new FItemsRenderer();
		$itemRenderer->showPageLabel = true;
		
		$fItems = new FItems($typeId,$user->userVO->userId,$itemRenderer);
		$fItems->addWhere('itemIdTop is null');
		$fItems->setOrder('dateCreated desc');
		
		$pager = new FPager(0,$localPerPage,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * $localPerPage;

		$fItems->getList($from,$localPerPage+1);
		$totalItems = $fItems->total();

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

			$tmptext = $fItems->render();
			if ($totalItems > $localPerPage) $tmptext .= $pager->links;

			FBuildPage::addTab(array("MAINDATA"=>$tmptext));
		}
	}
}