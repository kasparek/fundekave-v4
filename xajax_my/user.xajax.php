<?php
$nonUserInit = false;
function user_switchFriend($userIdFriend,$elementId='') {
	global $user;
	if(!is_object($_SESSION["user"])) $_SESSION["user"] = new fUser();
	$user = & $_SESSION["user"];
	$user->getFriends(0,true);
	if($user->pritel($userIdFriend)) {
    //remove
    $user->delpritel($userIdFriend);
    $ret = LABEL_FRIEND_ADD;
  } else {
    //add
    $user->addpritel($userIdFriend);
    $ret = LABEL_FRIEND_REMOVE;
  }
	
	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	if($elementId=='') $objResponse->assign('switchFriendButt', 'value', $ret);
	else $objResponse->assign($elementId, 'innerHTML', $ret);
	return $objResponse;
}

function user_tag($itemId) {
  global $user;
  $itemId = substr($itemId,1);
  if(!is_object($_SESSION["user"])) $_SESSION["user"] = new fUser();
	$user = & $_SESSION["user"];
	$ret = false;
	if($user->idkontrol) {
	//clean cache
	  $user->resetGroupTimeCache('itemTags');
	  $user->cacheRemove('fotornd');
	  $user->cacheRemove('fototags');
	  $user->cacheRemove('lastForumPost');
	  $user->cacheRemove('fPages');
	  $user->cacheRemove('fotodetail');
    if(fItems::tag($itemId,$user->gid)) $ret = true;
  }
  $objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	if($ret==true) $objResponse->assign('tag'.$itemId, 'innerHTML', fItems::tagLabel($itemId));
	if($ret==true) $objResponse->assign('tag'.$itemId, 'className', 'tagIs');
	return $objResponse;
}
fXajax::register('user_tag');
fXajax::register('user_switchFriend');