<?php
include_once('iPage.php');
class page_ItemsLive implements iPage {

	static function process() {

	}

	static function build() {
		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;

		$validTypesArr = FItems::TYPES_VALID();

		if(!in_array($typeId, $validTypesArr)) $typeId = FItems::TYPE_DEFAULT;

		$localPerPage = LIVE_PERPAGE;

		$fItems = new FItems();
		$fItems->showPageLabel = true;
		$fItems->initData($typeId,$user->userVO->userId,true);
		if($typeId=='blog') $fItems->addWhere('i.itemIdTop is null');

		$fItems->setOrder('i.dateCreated desc');

		$pager = FSystem::initPager(0,$localPerPage,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * $localPerPage;

		$fItems->getData($from,$localPerPage+1);
		$totalItems = count($fItems->arrData);

		$maybeMore = false;
		if($totalItems > ($localPerPage-$fItems->itemsRemoved)) {
			$maybeMore = true;
			unset($fItems->arrData[(count($fItems->arrData)-1)]);
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