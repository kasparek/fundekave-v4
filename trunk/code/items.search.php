<?php
$typeId = $user->currentPage['typeIdChild'];

fItems::setTagToolbarDefaults(array('enabled'=>1,'search'=>1,'perpage'=>SEARCH_PERPAGE));

$TOPTPL->addTab(array("MAINDATA"=>fItems::getTagToolbar()));

$fItems = new fItems();
$fItems->cacheResults = true;
$fItems->showPageLabel = true;
$fItems->initData($user->currentPage['typeIdChild'],$user->gid,true);
//$fItems->setOrder('i.dateCreated desc');
$fItems->addWhere('i.itemIdTop is null');
fItems::setQueryTool(&$fItems);

$pager = fSystem::initPager(0,SEARCH_PERPAGE,array('noAutoparse'=>1));
$from = ($pager->getCurrentPageID()-1) * SEARCH_PERPAGE;
$fItems->setLimit($from,SEARCH_PERPAGE+1);

$fItems->getData();
$totalItems = count($fItems->arrData);

$maybeMore = false;
if($totalItems > SEARCH_PERPAGE) {
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
    
	while ($fItems->arrData) {
        $fItems->parse();    
	}
	$tpl->setVariable("RESULTS",$fItems->show());
} else {
  $tpl->touchBlock('noitems');
}

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
/**/