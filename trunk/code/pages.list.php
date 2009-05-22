<?php
if ($user->idkontrol) {
  $fForum = new FForum();
  $fForum->clearUnreadedMess();
  $fForum->afavAll($user->userVO->userId);
}
$fPages = new fPages('',$user->userVO->userId);
$TOPTPL->addTab(array("MAINDATA"=>$fPages->printCategoryList()));
