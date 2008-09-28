<?php
$typeId = $user->currentPage['typeIdChild'];

$fItems = new fItems();
$fItems->showPageLabel = true;
$fItems->initData($typeId,$user->gid,true);
if($typeId=='blog') $fItems->addWhere('i.itemIdTop is null');

$fItems->setOrder('i.dateCreated desc');

$pager = fSystem::initPager(0,LIVE_PERPAGE,array('noAutoparse'=>1));
$from = ($pager->getCurrentPageID()-1) * LIVE_PERPAGE;

$fItems->setLimit($from,LIVE_PERPAGE+1);
$fItems->getData();
$totalItems = count($fItems->arrData);
        
$maybeMore = false;
if($totalItems > LIVE_PERPAGE) {
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
    
    if ($totalItems>LIVE_PERPAGE) $tmptext .= $pager->links;
    
    $TOPTPL->addTab(array("MAINDATA"=>$tmptext));
}