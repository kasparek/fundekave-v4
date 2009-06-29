<?php
include_once('iPage.php');
class page_ItemsSearch implements iPage {

	static function process($data) {

	}

	static function build() {
		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;

		$perPage = $user->pageVO->perPage();

		FItemsToolbar::setTagToolbarDefaults(array('enabled'=>1,'search'=>1,'perpage'=>$perPage));
		FBuildPage::addTab(array("MAINDATA"=>FItemsToolbar::getTagToolbar()));

		$itemRenderer = new FItemsRenderer();
		$itemRenderer->showPageLabel = true;

		$fItems = new FItems($user->pageVO->typeIdChild, $user->userVO->userId, $itemRenderer);
		$fItems->cacheResults = 's';
		$fItems->addWhere('itemIdTop is null');
		FItemsToolbar::setQueryTool(&$fItems);
		$pager = FSystem::initPager(0,$perPage,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * $perPage;
		$fItems->getList($from,$perPage+1);
		$totalItems = $fItems->total();

		$maybeMore = false;
		if($totalItems > $perPage) {
			$maybeMore = true;
			array_pop($fItems->data);
		}
		if($from > 0) $totalItems += $from;

		$tpl = new FTemplateIT('items.list.tpl.html');
		if($totalItems > 0) {
			$pager->totalItems = $totalItems;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();
			$tpl->setVariable("PAGER",$pager->links);
			$tpl->setVariable("RESULTS",$fItems->render());
		} else {
			$tpl->touchBlock('noitems');
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}