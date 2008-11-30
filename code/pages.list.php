<?php
if ($user->idkontrol) {
  $fForum = new fForum();
  $fForum->clearUnreadedMess();
  $fForum->afavAll($user->gid);
}
$fPages = new fPages('',$user->gid,&$db);
$TOPTPL->addTab(array("MAINDATA"=>$fPages->printCategoryList()));
