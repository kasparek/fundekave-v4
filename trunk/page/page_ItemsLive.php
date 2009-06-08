<?php
include_once('iPage.php');
class page_ItemsLive implements iPage {

	static function process() {

	}

	static function build() {
		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;

		if(!FItems::isTypeValid($typeId)) $typeId = FItems::TYPE_DEFAULT;

		$localPerPage = LIVE_PERPAGE;

		$itemRenderer = new FItemsRenderer();
		$itemRenderer->showPageLabel = false;
		
		$fItems = new FItems($typeId,$user->userVO->userId,$itemRenderer);
		if($typeId=='blog') $fItems->addWhere('itemIdTop is null');
		$fItems->setOrder('dateCreated desc');

		$pager = FSystem::initPager(0,$localPerPage,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * $localPerPage;

		$fItems->getList($from,$localPerPage+1);
		$totalItems = count($fItems->data);

		$maybeMore = false;
		if($totalItems > ($localPerPage-$fItems->itemsRemoved)) {
			$maybeMore = true;
			unset($fItems->data[(count($fItems->data)-1)]);
		}
		if($from > 0) $totalItems += $from;

		if($totalItems > 0) {
			$pager->totalItems = $totalItems;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();

			while ($fItems->arrData) {
				$fItems->parse();
			}

			$tmptext = '<div class="hfeed">'.$fItems->show().'</div>';

			if ($totalItems > LIVE_PERPAGE) $tmptext .= $pager->links;

			FBuildPage::addTab(array("MAINDATA"=>$tmptext));
		}
	}
}