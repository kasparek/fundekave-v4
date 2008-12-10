<?php
$nonUserInit = false;
function draft_save($place,$text) {
	global $user,$db;
	if(!is_object($_SESSION["user"])) $_SESSION["user"] = new fUser();
	$user = & $_SESSION["user"];
	fUserDraft::save($place,$text);
	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->call('draftSaved', $place);
	return $objResponse;
}

fXajax::register('draft_save');
?>