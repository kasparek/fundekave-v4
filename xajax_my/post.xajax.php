<?php
//require_once("./libs/fUser.class.php");
$nonUserInit=false;
function post_setRecipientAvatarFromInput($recipientName) {
	global $user;
	if(!is_object($_SESSION["user"])) $_SESSION["user"] = new fUser();
	$user = & $_SESSION["user"];
	$data = '';
	$recipientId = 0;
	$recipientId = $user->getUserIdByName($recipientName) * 1;
	if($recipientId==0) $recipientName='';
	$data = $user->showAvatar($recipientId)
			.'<br />'.$recipientName.
			(($recipientId>0)?((($user->pritel($recipientId))?('')
:('<br /><input id="switchFriendButt" type="button" onClick="xajax_user_switchFriend(\''.$recipientId.'\');return(false);" value="'.LABEL_FRIEND_ADD.'" class="button tlacitko" title="Pøidat / odebrat kamarada" />'))):(''));
	
	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('recipientavatar', 'innerHTML', $data);
	return $objResponse;
}

function post_setRecipientAvatarFromBooked($recipientId) {
	global $user;
	if(!is_object($_SESSION["user"])) $_SESSION["user"] = new fUser();
	$user = & $_SESSION["user"];
	$data = '';
	$recipientId = $recipientId * 1;
	$data = $user->showAvatar($recipientId)
			.'<br />'.$user->getgidname($recipientId).
			(($recipientId>0)?((($user->pritel($recipientId))?('')
:('<br /><input id="switchFriendButt" type="button" onClick="xajax_user_switchFriend(\''.$recipientId.'\');return(false);" value="'.LABEL_FRIEND_REMOVE.'" class="button tlacitko" title="Pøidat / odebrat kamarada" />'))):(''));

	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('recipientavatar', 'innerHTML', $data);
	$objResponse->assign('prokoho_book', 'value', "");
	$objResponse->assign('prokoho', 'value', $user->getgidname($recipientId));
	return $objResponse;
}
fXajax::register('post_setRecipientAvatarFromBooked');
fXajax::register('post_setRecipientAvatarFromInput');
//delete after testing
//$reqSetRecipient->setParameter(0, XAJAX_INPUT_VALUE, 'prokoho_book');
//$reqSetRecipientFromInput->setParameter(0, XAJAX_INPUT_VALUE, 'prokoho');
?>