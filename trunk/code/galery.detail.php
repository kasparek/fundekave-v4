<?php
if(fRules::get($user->gid,$user->currentPageId,2)) {
    if($user->currentPageParam == 'e') fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId,''),BUTTON_PAGE_BACK);
    else fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId.'e'),LABEL_SETTINGS);
}
if($user->idkontrol) {
      if($user->currentPageParam=='' && $user->currentPage['userIdOwner'] != $user->gid) {
      	fXajax::register('forum_auditBook');
      	fSystem::secondaryMenuAddItem('#book',((0 == $user->obliben($user->currentPageId,$user->gid))?(LABEL_BOOK):(LABEL_UNBOOK)),"xajax_forum_auditBook('".$user->currentPageId."','".$user->gid."');",0,'bookButt');
      }
     fSystem::secondaryMenuAddItem($user->getUri('p=a'),LABEL_POCKET_PUSH,"xajax_pocket_add('".$user->currentPageId."','1');return false;",0);
}
if($user->currentPageParam == 'e') require(ROOT.ROOT_CODE.'galery.edit.php');
else {

$galery = new fGalery();
$galery->getGaleryData($user->currentPageId);

if($user->gid == $user->currentPage['userIdOwner']) {
    //---run just wher owner access
    $galery->refreshImgToDb($user->currentPageId);
}

  if($user->currentItemId==0) {
    
    $fItems = new fItems();
    
    if($user->idkontrol) $fItems->xajaxSwitch = true; //---THINK ABOUT USABILITY AND BACK BUTTON
    $fItems->showTooltip = false;
    
    $fItems->initData('galery');
    $fItems->setWhere('i.pageId="'.$user->currentPageId.'"');
    $fItems->addWhere('i.itemIdTop is null');
    $totalItems = $fItems->getCount();
    $perPage = $galery->gWidth * $galery->gHeight;

    if($totalItems==0){
  		fError::addError(ERROR_GALERY_NOFOTO);
  		$user->currentPageAccess = false;
  	} else {
  	
    	if($galery->gOrderItems==0) $fItems->setOrder('i.enclosure');
      else $fItems->setOrder('i.dateCreated desc');
    
      $pager = fSystem::initPager($totalItems,$perPage);
    	$od = ($pager->getCurrentPageID()-1) * $perPage;
    	
    	$fItems->setLimit($od,$perPage);
    	$fItems->openPopup = ($user->galtype==0)?(false):(true);
    	$fItems->getData();
    
    	//---nahledy
    	$tpl = new fTemplateIT('galery.thumbnails.tpl.html');
    	$tpl->setCurrentBlock("thumbnails");
    	$tpl->setVariable("GALERYTEXT",$galery->gText);
    
    	for($y=0;$y<$galery->gHeight;$y++){ 
    		$tpl->setCurrentBlock("row");
    		for($x=0;$x<$galery->gWidth;$x++){ 
    			$tpl->setCurrentBlock("cell");
    			if($fItems->arrData) {
    			 $fItems->parse();
    			 $tpl->setVariable("THUMBNAIL",$fItems->show());
    			} else {
    				$tpl->setVariable("THUMBNAIL",'&nbsp;');
    			}
    			$tpl->edParseBlock("cell");
    		}
    		$tpl->edParseBlock("row");
    	}
    	
    	if($perPage<$totalItems) {
    		//$tpl->setVariable("FROM",$od+1);
    		//$tpl->setVariable("TO",$od+$perPage);
    		$tpl->setVariable("SUM",$totalItems);
    		//$tpl->setVariable("PAGER",$pager->links);
    		$tpl->setVariable("PAGEREND",$pager->links);
    	}
    	$tpl->edParseBlock("thumbnails");
    	
    	$tmptext=$tpl->get();
    	
    	$TOPTPL->addTab(array("MAINDATA"=>$tmptext,"MAINID"=>'fotoBox'));
  	}
  } else {
  	//---detail foto
  	
  	$TOPTPL->addTab(array("MAINDATA"=>$galery->printDetail($user->currentItemId),"MAINID"=>'fotoBox'));
  }
}