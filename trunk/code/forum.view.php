<?php
$typeId = $user->currentPage['typeId'];

if(!empty($user->currentPageParam)) fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId),BUTTON_PAGE_BACK);

if(isset($_REQUEST['nid'])) {
  $user->currentItemId = $_REQUEST['nid']; //---backwards compatibility
  $user->checkItem();
}

if($user->currentItemId > 0 && $typeId == 'blog') {
    fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId),BUTTON_PAGE_BACK);
} else {
    
    if(fRules::get($user->gid,$user->currentPageId,2)) {
        if(empty($user->currentPageParam)) {
            if($typeId=='blog') {
                fXajax::register('blog_blogEdit');
                fXajax::register('blog_processFormBloged');
                fSystem::secondaryMenuAddItem('#editnew',LABEL_ADD,"xajax_blog_blogEdit('0');",1);
            }
        }
        fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId.'e'),LABEL_SETTINGS,'',1);
    }
    
    //tlacitko sledovat - jen pro nemajitele
    if($user->idkontrol) {
      if($user->currentPageParam=='' && $user->currentPage['userIdOwner'] != $user->gid) {
      	fXajax::register('forum_auditBook');
      	fSystem::secondaryMenuAddItem('#book',((0 == $user->obliben($user->currentPageId,$user->gid))?(LABEL_BOOK):(LABEL_UNBOOK)),"xajax_forum_auditBook('".$user->currentPageId."','".$user->gid."');",0,'bookButt');
      }
      fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId.'p'),LABEL_POLL);
      fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId.'s'),LABEL_STATS);
      if($user->currentPageParam=='') {
        if(isset($_GET['s']) || fItems::isToolbarEnabled()) $TOPTPL->addTab(array("MAINDATA"=>fItems::getTagToolbar(false)));
        else {
          fXajax::register('forum_toolbar');
          fSystem::secondaryMenuAddItem($user->getUri('s=t'),LABEL_THUMBS,"xajax_forum_toolbar();return false;");
        }
      }
    }
    fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId.'h'),LABEL_HOME);
}

if($user->currentPageParam == 'e') {
    require(ROOT.ROOT_CODE.'page.edit.php');
} elseif($user->currentPageParam == 'p') require(ROOT.ROOT_CODE.'page.poll.php');
elseif($user->currentPageParam == 's') require(ROOT.ROOT_CODE.'page.stat.php');
elseif($user->currentPageParam == 'h') {
    $tmptext = '';
    if(!empty($user->currentPage['pageParams'])) {
        $xml = new SimpleXMLElement($user->currentPage['pageParams']);
        if($xml->home!='') $tmptext = $xml->home;
        else $tmptext = MESSAGE_FORUM_HOME_EMPTY;
    }
    $TOPTPL->addTab(array("MAINDATA"=>$tmptext));    
} elseif ($typeId=='blog') {
    $fBlog = new fBlog($db);
    $TOPTPL->addTab(array("MAINDATA"=>$fBlog->listAll($user->currentItemId),"MAINID"=>'bloged'));
} else {
    fForum::process();
    $TOPTPL->addTab(array("MAINDATA"=>fForum::show()));
}