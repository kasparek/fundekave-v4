<?php
if(!isset($_SESSION['thumbsDefsSet'][$user->currentPageId])){
    fItems::setTagToolbarDefaults(array('filter'=>1,'order'=>1));
    $_SESSION['thumbsDefsSet'][$user->currentPageId] = 1;
}

$TOPTPL->addTab(array("MAINDATA"=>fItems::getTagToolbar()));

$fItems = new fItems();

$fItems->showPageLabel = true;
$fItems->initData('',$user->gid);

$fItems->cacheResults = true;
$fItems->setOrder('i.dateCreated desc');

fItems::setQueryTool(&$fItems);

//$celkem = $fItems->getCount();
$celkem = 15;

$tpl = new fTemplateIT('items.list.tpl.html');

$od = 0;

$pager = fSystem::initPager($celkem,GALERY_PERPAGE);
$currentPage = $pager->getCurrentPageID();
if($currentPage > 1) {
    //---do count
    $celkem = $fItems->getCount(); 
    $pager = fSystem::initPager($celkem,GALERY_PERPAGE);
}
	$od = ($currentPage-1) * GALERY_PERPAGE;
	$do = $od + GALERY_PERPAGE;
	
//$tpl->setVariable("FROM",$od+1);
//$tpl->setVariable("TO",$do);
//$tpl->setVariable("TOTAL",$celkem);
//$tpl->setVariable("TOPPAGER",$pager->links);

$fItems->setLimit($od,GALERY_PERPAGE);
//$fItems->debug = 1;
$fItems->getData();

//---if there is less items than is page
$totalItems = count($fItems->arrData);
if($totalItems < GALERY_PERPAGE) {
  echo $celkem = $od + $totalItems;
  if($celkem > GALERY_PERPAGE) $pager = fSystem::initPager($celkem,GALERY_PERPAGE);
  else $pager = false; 
}


if($pager) $tpl->setVariable("PAGER",$pager->links);

if(!empty($fItems->arrData)) {
	while ($fItems->arrData) {
        $fItems->parse();    
	}
	$tpl->setVariable("RESULTS",$fItems->show());
} else {
  $tpl->touchBlock('noitems');
}


$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));