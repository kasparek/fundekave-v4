<?php
if ($user->idkontrol) {
    fSystem::secondaryMenuAddItem($user->getUri('','','v'),LABEL_SEARCH);
    fSystem::secondaryMenuAddItem($user->getUri('','','w'),LABEL_SEARCH_ITEMS);
    fSystem::secondaryMenuAddItem($user->getUri('','','t'),LABEL_TOP);
    fSystem::secondaryMenuAddItem($user->getUri('','','l'),LABEL_PAGES_LIVE);
}
if($user->currentPageParam=='t') {
    require('items.tags.php');
} elseif($user->currentPageParam=='v') {
    require('pages.search.php');
} elseif($user->currentPageParam=='w') {
    require('items.search.php');
} else {
    fXajax::register('forum_listcategory');
    if ($user->idkontrol) {
        $fForum = new fForum();
        $fForum->clearUnreadedMess();
        $fForum->afavAll($user->gid);
    }
    $fPages = new fPages('',$user->gid,&$db);
    $tmptext = $fPages->printCategoryList();
}
$TOPTPL->addTab(array("MAINDATA"=>$tmptext));