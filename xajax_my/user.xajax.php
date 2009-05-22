<?php
$nonUserInit = false;
function user_switchFriend($userIdFriend,$elementId='') {
	$user = FUser::getInstance();
	
	$user->userVO->getFriends(true);
	if($user->userVO->isFriend($userIdFriend)) {
    //remove
    $user->userVO->removeFriend($userIdFriend);
    $ret = LABEL_FRIEND_ADD;
  } else {
    //add
    $user->userVO->addFriend($userIdFriend);
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
	if($ret==true) $objResponse->assign('tag'.$itemId, 'innerHTML', fItems::getTag($itemId,$user->gid));
	//if($ret==true) $objResponse->assign('tag'.$itemId, 'className', 'tagIs');
	return $objResponse;
}
fXajax::register('user_tag');
fXajax::register('user_switchFriend');