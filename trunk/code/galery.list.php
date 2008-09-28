<?php
if(fRules::get($user->gid,$user->currentPageId,2)) {
    fSystem::secondaryMenuAddItem($user->getUri('','galed'),LABEL_ADD);
}
fSystem::secondaryMenuAddItem($user->getUri('','taggi'),LABEL_TAG_PAGE);
fSystem::secondaryMenuAddItem($user->getUri('','','t'),'top');
if($user->currentPageParam=='t') {
  require('items.tags.php');
} else {
//category list
$category = new fCategory('sys_pages_category','categoryId');
$TOPTPL->addTab(array("MAINDATA"=>$category->getList('galery')));

if(isset($_REQUEST['kat'])) $kat = $_REQUEST['kat']*1; else $kat=0;

$fPages = new fPages('galery',$user->gid,$db);
if($kat > 0) $fPages->addWhere("p.categoryId='".$kat."'");

$celkem = $fPages->getCount();
$od = 0;

$tpl = new fTemplateIT('galery.list.tpl.html');

if($celkem > GALERY_PERPAGE) {
	$pager = fSystem::initPager($celkem,GALERY_PERPAGE);
	$od=($pager->getCurrentPageID()-1) * GALERY_PERPAGE;
	$do = $od + GALERY_PERPAGE;

	$tpl->setVariable("TOTAL",$celkem);
	//$tpl->setVariable("TOPPAGER",$pager->links);
	$tpl->setVariable("PAGER",$pager->links);
}

$fPages->setSelect("p.pageId,p.name,p.userIdOwner,date_format(dateContent,'{#date_local#}') as datumcz,description,date_format(dateContent,'{#date_iso#}') as diso");
$fPages->setOrder("dateContent",true);
$fPages->addOrder("pageId",true);
$fPages->setLimit($od,GALERY_PERPAGE);
//$fPages->debug =1;
$arrgal = $fPages->getContent();

if(!empty($arrgal)) {
  $fItems = new fItems();
  $fItems->initData('galery',$user->gid,true);
  $fItems->setOrder('i.hit desc');
  $fItems->setLimit(0,1);
  $fItems->showTooltip = false;
  $fItems->showText = false;
  $fItems->showTag = false;
  $fItems->showPageLabel = false;
  $fItems->showRating = false;
  $fItems->showHentryClass = false;
  $fItems->openPopup = false;
  $fItems->showPocketAdd = false;
  //$fItems->debug = 1;
	foreach ($arrgal as $gal) {
	  $fItems->setWhere('p.pageId="'.$gal[0].'"');
	  $fItems->getData();
	  $fItems->parse();
    $fotoThumb = $fItems->show();
	  
		$tpl->setCurrentBlock('galery');
		$tpl->setVariable("THUMB",$fotoThumb);
		$tpl->setVariable("PAGEID",$gal[0]);
		$tpl->setVariable("PAGELINK",'?k='.$gal[0]);
		$tpl->setVariable("PAGENAME",$gal[1]);
		$tpl->setVariable("DATELOCAL",$gal[3]);
		$tpl->setVariable("DATEISO",$gal[5]);
		$tpl->setVariable("GALERYTEXT",$gal[4]);
		$tpl->setVariable("AUTHOR",$user->getgidname($gal[2]));
		if($user->idkontrol) {
		  $tpl->setVariable("AUTHORLINK",'?k=finfo&who='.$gal[2]);
      $tpl->touchBlock('authorlinkclose');
    }
		
		$tpl->parseCurrentBlock();
	}
}

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
}