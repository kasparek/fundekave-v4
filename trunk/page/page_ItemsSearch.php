<?php
include_once('iPage.php');
class page_ItemsSearch implements iPage {

	static function process() {

	}

	static function build() {
		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;

		FItems::setTagToolbarDefaults(array('enabled'=>1,'search'=>1,'perpage'=>SEARCH_PERPAGE));
		FBuildPage::addTab(array("MAINDATA"=>FItems::getTagToolbar()));

		$fItems = new FItems();
		$fItems->cacheResults = 's';
		$fItems->showPageLabel = true;
		$fItems->initData($typeId,$user->userVO->userId,true);
		//$fItems->setOrder('i.dateCreated desc');
		$fItems->addWhere('i.itemIdTop is null');
		FItems::setQueryTool(&$fItems);

		$pager = FSystem::initPager(0,SEARCH_PERPAGE,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * SEARCH_PERPAGE;
		$fItems->getData($from,SEARCH_PERPAGE+1);
		$totalItems = count($fItems->arrData);

		$maybeMore = false;
		if($totalItems > SEARCH_PERPAGE) {
			$maybeMore = true;
			unset($fItems->arrData[(count($fItems->arrData)-1)]);
		}
		if($from > 0) $totalItems += $from;

		$tpl = new FTemplateIT('items.list.tpl.html');

		if($totalItems > 0) {

			$pager->totalItems = $totalItems;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();
			$tpl->setVariable("PAGER",$pager->links);

			while ($fItems->arrData) {
				$fItems->parse();
			}
			$tpl->setVariable("RESULTS",$fItems->show());
		} else {
			$tpl->touchBlock('noitems');
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}