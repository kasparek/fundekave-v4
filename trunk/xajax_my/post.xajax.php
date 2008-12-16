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
	$data = $user->showAvatar($recipientId);
	
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
	$data = $user->showAvatar($recipientId);

	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('recipientavatar', 'innerHTML', $data);
	$objResponse->assign('prokoho_book', 'value', "");
	$objResponse->assign('prokoho', 'value', $user->getgidname($recipientId));
	return $objResponse;
}
fXajax::register('post_setRecipientAvatarFromBooked');
fXajax::register('post_setRecipientAvatarFromInput');