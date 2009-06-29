<?php
include_once('iPage.php');
class page_ItemsTags implements iPage {

	static function process($data) {

	}

	static function build() {

		$user = FUser::getInstance();
		$userId = $user->userVO->userId;

		FItemsToolbar::setTagToolbarDefaults(array('enabled'=>1,'order'=>3,'interval'=>3));
		$perpage = $user->pageVO->perPage();

		FBuildPage::addTab(array("MAINDATA"=>FItemsToolbar::getTagToolbar()));

		$itemRenderer = new FItemsRenderer();
		$itemRenderer->showPageLabel = true;
		
		$fItems = new FItems($user->pageVO->typeIdChild,$userId,$itemRenderer);
		$fItems->setOrder('dateCreated desc');
		$fItems->addWhere('itemIdTop is null');
		FItemsToolbar::setQueryTool(&$fItems);

		$pager = FSystem::initPager(0,$perpage,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * $perpage;
		$fItems->getList($from,$perpage+1);
		$totalItems = $fItems->total();

		$maybeMore = false;
		if($totalItems > $perpage) {
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