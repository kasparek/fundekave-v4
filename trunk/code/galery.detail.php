<?php
if(fRules::get($user->gid,$user->currentPageId,2)) {
    if($user->currentPageParam == 'e') fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId,''),BUTTON_PAGE_BACK);
    else fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId,'e'),LABEL_SETTINGS);
}
if($user->idkontrol) {
      if($user->currentPageParam=='' && $user->currentPage['userIdOwner'] != $user->gid) {
      	fSystem::secondaryMenuAddItem('#book',((0 == $user->obliben($user->currentPageId,$user->gid))?(LABEL_BOOK):(LABEL_UNBOOK)),"xajax_forum_auditBook('".$user->currentPageId."','".$user->gid."');",0,'bookButt');
      }
     fSystem::secondaryMenuAddItem($user->getUri('p=a'),LABEL_POCKET_PUSH,"xajax_pocket_add('".$user->currentPageId."','1');return false;",0);
}
if($user->currentPageParam == 'e') require(ROOT.ROOT_CODE.'galery.edit.php');
else {

$galery = new fGalery();
$galery->getGaleryData($user->currentPageId);
if(fRules::get($user->gid,$user->currentPageId,2)) {
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
    $perPage = $galery->gPerpage;

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
    	$tpl->setVariable("GALERYHEAD",$user->currentPage['content']);
    
    	$x=0;
        while($fItems->arrData && $x < $galery->gPerpage) {
    		$tpl->setCurrentBlock("cell");
    		 $fItems->parse();
    		 $tpl->setVariable("THUMBNAIL",$fItems->show());
    		$tpl->parseCurrentBlock();
        }
    		
    	
    	
    	if($perPage<$totalItems) {
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