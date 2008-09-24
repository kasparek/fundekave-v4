<?php

$audit = new fForum();
$audit->clearUnreadedMess();
//---srovnani klubu
if ($user->idkontrol) $audit->afavAll($user->gid);

$fPages = new fPages('',$user->gid,&$db);
$tmptext = $fPages->printCategoryList();

fXajax::register('forum_listcategory');

$TOPTPL->addTab(array("MAINDATA"=>$tmptext));
?>