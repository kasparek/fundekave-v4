<?php

$validTypesArr = fItems::TYPES_VALID();

fXajax::register('forum_booked');
if($user->whoIs > 0) $addUrl = '&who='.$user->whoIs; else $addUrl = '';
fSystem::secondaryMenuAddItem($user->getUri($addUrl),LABEL_FORUMS,"xajax_forum_booked('forum','".$user->whoIs."');return false;");
fSystem::secondaryMenuAddItem($user->getUri('t=blog'.$addUrl),LABEL_BLOGS,"xajax_forum_booked('blog','".$user->whoIs."');return false;");
fSystem::secondaryMenuAddItem($user->getUri('t=galery'.$addUrl),LABEL_GALERIES,"xajax_forum_booked('galery','".$user->whoIs."');return false;");

$typeId = $user->currentPage['typeIdChild'];
if(isset($_GET['t'])) $typeId = $_GET['t'];

if(!in_array($typeId, $validTypesArr)) $typeId = fItems::TYPE_DEFAULT;

$fPages = new fPages($typeId,$user->gid);
$data = $fPages->printBookedList();

$TOPTPL->addTab(array("MAINDATA"=>$data));