<?php
$typeId = $user->currentPage['typeIdChild'];

$fItems = new fItems();
$fItems->showPageLabel = true;
$fItems->initData($typeId,$user->gid,true);

if($typeId=='blog') $fItems->addWhere('i.itemIdTop is null');
 
$totalItems = 100;

$fItems->setOrder('i.dateCreated desc');

$pager = fSystem::initPager($totalItems,LIVE_PERPAGE);
$od = ($pager->getCurrentPageID()-1) * LIVE_PERPAGE;

$fItems->setLimit($od,LIVE_PERPAGE);

$fItems->getData();

$tmptext='';

$x=0;
while ($fItems->arrData) {
  $fItems->parse();
}

$tmptext .= '<div class="hfeed">'.$fItems->show().'</div>';

if ($totalItems>LIVE_PERPAGE) $tmptext.='<div class="pager">'.$pager->links.'</div>';

$TOPTPL->addTab(array("MAINDATA"=>$tmptext));



/*
$typeId = $user->currentPage['typeIdChild'];

$fItems = new fItems();
$fItems->showPageLabel = true;
$fItems->initData($typeId,$user->gid,true);

//$fItems->debug = 1;
if($typeId=='blog') $fItems->addWhere('i.itemIdTop is null');
$totalItems = $fItems->getCount();
if($totalItems>300) $totalItems = 300;

$fItems->setOrder('i.dateCreated desc');

$pager = fSystem::initPager($totalItems,LIVE_PERPAGE);
$od = ($pager->getCurrentPageID()-1) * LIVE_PERPAGE;

$fItems->setLimit($od,LIVE_PERPAGE);

$fItems->getData();

$tmptext='';
if ($totalItems>LIVE_PERPAGE) $tmptext.='<div class="pager">'.$pager->links.'</div>';

while ($fItems->arrData) $fItems->parse();

$tmptext .= '<div class="hfeed">'.$fItems->show().'</div>';
if ($totalItems>LIVE_PERPAGE) $tmptext.='<div class="pager">'.$pager->links.'</div>';

$TOPTPL->addTab(array("MAINDATA"=>$tmptext));
*/
?>