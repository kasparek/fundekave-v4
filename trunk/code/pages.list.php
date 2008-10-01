<?php
if ($user->idkontrol) {
    fSystem::secondaryMenuAddItem($user->getUri('','','v'),LABEL_SEARCH);

    $typeId = $user->currentPage['typeIdChild'];
    if($typeId=='forum') fSystem::secondaryMenuAddItem($user->getUri('','','w'),LABEL_SEARCH_ITEMS_FORUMS);
    elseif($typeId=='blog') fSystem::secondaryMenuAddItem($user->getUri('','','w'),LABEL_SEARCH_ITEMS_BLOGS);
    
    fSystem::secondaryMenuAddItem($user->getUri('','','t'),LABEL_TOP);
    fSystem::secondaryMenuAddItem($user->getUri('','','l'),LABEL_PAGES_LIVE);
}
if($user->currentPageParam=='l') {
    require('items.live.php');
} elseif($user->currentPageParam=='t') {
    require('items.tags.php');
} elseif($user->currentPageParam=='v') {
    require('pages.search.php');
} elseif($user->currentPageParam=='w') {
    require('items.search.php');
} else {
    if ($user->idkontrol) {
        $fForum = new fForum();
        $fForum->clearUnreadedMess();
        $fForum->afavAll($user->gid);
    }
    $fPages = new fPages('',$user->gid,&$db);
    $tmptext = $fPages->printCategoryList();
    $TOPTPL->addTab(array("MAINDATA"=>$tmptext));
}