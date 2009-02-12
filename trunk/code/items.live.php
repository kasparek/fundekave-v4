<?php
$typeId = $user->currentPage['typeIdChild'];

$validTypesArr = fItems::TYPES_VALID();

if(!in_array($typeId, $validTypesArr)) $typeId = fItems::TYPE_DEFAULT;

$localPerPage = LIVE_PERPAGE;

$fItems = new fItems();
$fItems->showPageLabel = true;
$fItems->initData($typeId,$user->gid,true);
if($typeId=='blog') $fItems->addWhere('i.itemIdTop is null');

$fItems->setOrder('i.dateCreated desc');

$pager = fSystem::initPager(0,$localPerPage,array('noAutoparse'=>1));
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
    
    $TOPTPL->addTab(array("MAINDATA"=>$tmptext));
}