<?php
include_once('iPage.php');
class page_ItemsSearch implements iPage {

	static function process($data) {

	}

	static function build() {
		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;

		FItemsToolbar::setTagToolbarDefaults(array('enabled'=>1,'search'=>1,'perpage'=>SEARCH_PERPAGE));
		FBuildPage::addTab(array("MAINDATA"=>FItemsToolbar::getTagToolbar()));

    $itemRenderer = new FItemsRenderer();
    $itemRenderer->showPageLabel = true;

		$fItems = new FItems('forum', $user->userVO->userId, $itemRenderer);
		$fItems->cacheResults = 's';
		$fItems->addWhere('itemIdTop is null');
		FItemsToolbar::setQueryTool(&$fItems);
		$pager = FSystem::initPager(0,SEARCH_PERPAGE,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * SEARCH_PERPAGE;
		$fItems->getList($from,SEARCH_PERPAGE+1);
		$totalItems = count($fItems->data);

		$maybeMore = false;
		if($totalItems > SEARCH_PERPAGE) {
			$maybeMore = true;
			unset($fItems->data[(count($fItems->data)-1)]);
		}
		if($from > 0) $totalItems += $from;

		$tpl = new FTemplateIT('items.list.tpl.html');

		if($totalItems > 0) {

			$pager->totalItems = $totalItems;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();
			$tpl->setVariable("PAGER",$pager->links);

			while ($fItems->data) {
				$fItems->parse();
			}
			$tpl->setVariable("RESULTS",$fItems->show());
		} else {
			$tpl->touchBlock('noitems');
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}