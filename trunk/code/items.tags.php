<?php
if(!isset($_SESSION['thumbsDefsSet'][$user->currentPageId])){
    fItems::setTagToolbarDefaults(array('order'=>3,'interval'=>2));
    $_SESSION['thumbsDefsSet'][$user->currentPageId] = 1;
}

$TOPTPL->addTab(array("MAINDATA"=>fItems::getTagToolbar()));

$fItems = new fItems();
$fItems->showPageLabel = true;
$fItems->initData($user->currentPage['typeIdChild'],$user->gid,true);
$fItems->setOrder('i.dateCreated desc');
$fItems->addWhere('i.itemIdTop is null');
fItems::setQueryTool(&$fItems);

$pager = fSystem::initPager(0,GALERY_PERPAGE,array('noAutoparse'=>1));
$from = ($pager->getCurrentPageID()-1) * GALERY_PERPAGE;
$fItems->setLimit($from,GALERY_PERPAGE+1);
$fItems->getData();
$totalItems = count($fItems->arrData);

$maybeMore = false;
if($totalItems > GALERY_PERPAGE) {
    $maybeMore = true;
    unset($fItems->arrData[(count($fItems->arrData)-1)]);
}
if($from > 0) $totalItems += $from;

$tpl = new fTemplateIT('items.list.tpl.html');

if($totalItems > 0) {
    if($maybeMore==true) {
        $pager->totalItems = $totalItems;
    	$pager->maybeMore = $maybeMore;
    	$pager->getPager();
    	$tpl->setVariable("PAGER",$pager->links);
    }
	while ($fItems->arrData) {
        $fItems->parse();    
	}
	$tpl->setVariable("RESULTS",$fItems->show());
} else {
  $tpl->touchBlock('noitems');
}

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));