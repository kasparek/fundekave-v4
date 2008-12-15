<?php
//if messageId return empty form for new message
$nonUserInit = false;
function blog_blogEdit($messageId = 0,$currentPageId=0) {
    global $db,$user,$conf;
	if(!is_object($_SESSION["user"])) $_SESSION["user"] = new fUser();
	$user = & $_SESSION["user"];
	//???? $user->currentPageId = currentPageId();
	//--TODO check if user is logged else save draft data and return that user need to login, use popup, save draft
    
	$fBlog = new fBlog($db);
	$data = $fBlog->getEditForm($messageId);
    
	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('editnew', 'innerHTML', $data);
	$objResponse->call('draftSetEventListeners');
    $objResponse->call('initInsertToTextarea');
    $objResponse->call('datePickerInit');

	return $objResponse;
}

function blog_processFormBloged($aFormValues) {
	//--TODO check if user is logged else save draft data and return that user need to login, use popup, save draft
	global $db,$user;
	if(!is_object($_SESSION["user"])) $_SESSION["user"] = new fUser();
	$user = & $_SESSION["user"];
	$fBlog = new fBlog($db);
	
	$itemId = $fBlog->process($aFormValues);
	
	$data = $fBlog->listAll($itemId,true);
	
	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('bloged', 'innerHTML', $data);
	$objResponse->call('draftSetEventListeners');
    $objResponse->call('initInsertToTextarea');
    $objResponse->call('datePickerInit');
    
	return $objResponse;
}
fXajax::register("blog_processFormBloged");
fXajax::register('blog_blogEdit');