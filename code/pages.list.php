<?php
if ($user->idkontrol) {
  $fForum = new fForum();
  $fForum->clearUnreadedMess();
  $fForum->afavAll($user->gid);
}
$fPages = new fPages('',$user->userVO->userId);
$TOPTPL->addTab(array("MAINDATA"=>$fPages->printCategoryList()));
