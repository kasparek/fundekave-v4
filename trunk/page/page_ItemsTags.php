<?php
include_once('iPage.php');
class page_ItemsTags implements iPage {

	static function process() {

	}

	static function build() {

		$user = FUser::getInstance();
		$userId = $user->userVO->userId;

		FItems::setTagToolbarDefaults(array('enabled'=>1,'order'=>3,'interval'=>3));
		$perpage = GALERY_PERPAGE;

		FBuildPage::addTab(array("MAINDATA"=>FItems::getTagToolbar()));

		$fItems = new FItems();
		$fItems->showPageLabel = true;
		$fItems->initData($user->pageVO->typeIdChild,$userId,true);
		$fItems->setOrder('i.dateCreated desc');
		$fItems->addWhere('i.itemIdTop is null');
		FItems::setQueryTool(&$fItems);

		$pager = FSystem::initPager(0,$perpage,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * $perpage;
		$fItems->getData($from,$perpage+1);
		$totalItems = count($fItems->arrData);

		$maybeMore = false;
		if($totalItems > $perpage) {
			$maybeMore = true;
			unset($fItems->arrData[(count($fItems->arrData)-1)]);
		}
		if($from > 0) $totalItems += $from;

		$tpl = new fTemplateIT('items.list.tpl.html');

		if($totalItems > 0) {
			$pager->totalItems = $totalItems;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();
			$tpl->setVariable("PAGER",$pager->links);
			while ($fItems->arrData) $fItems->parse();
			$tpl->setVariable("RESULTS",$fItems->show());
		} else {
			$tpl->touchBlock('noitems');
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
}