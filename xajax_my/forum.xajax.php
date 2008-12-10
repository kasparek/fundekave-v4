<?php
$nonUserInit=false;

function forum_auditBook($auditId,$userId) {
	global $db;
	if ($db->getOne("select book from sys_pages_favorites where pageId = '".$auditId."' AND userId = '".$userId."'")) {
		$book = 0;
		$data = LABEL_BOOK;
	} else {
		$book = 1;
		$data = LABEL_UNBOOK;
	}
	$db->query("update sys_pages_favorites set book='".$book."' where pageId='".($auditId)."' AND userId='" . $userId."'");	
	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('bookButt', 'innerHTML', $data);
	return $objResponse;
}

function forum_booked($typeId,$userId) {
  global $db,$user;
  
  if($user->pritel($userId)) $user->whoIs = $userId;
  
  $fPages = new fPages($typeId,$user->gid,$db);
  $data = $fPages->printBookedList(true);
  
  $objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('bookedContent', 'innerHTML', $data);
	return $objResponse;

}
function forum_toolbar() {
    global $db,$user;
    
    $objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('thumbToolbar', 'innerHTML', fItems::getTagToolbar(false));
	return $objResponse;
}

function forum_fotoDetail($fotoId) {
    global $db,$user;
    $user->currentItemId = $fotoId;
    $user->checkItem();
    
    $objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	if($user->currentItemId>0) {
    	$galery = new fGalery();
        $galery->getGaleryData($user->currentPageId);
    	$objResponse->assign('fotoBox', 'innerHTML', $galery->printDetail($user->currentItemId));
    	$objResponse->call('setSwitchEventListeners');
    	$objResponse->call('setTagEventListeners');
    	$objResponse->call('setPocketAddEventListeners');
	}
	return $objResponse;
}

fXajax::register('forum_fotoDetail');
fXajax::register('forum_toolbar');
fXajax::register('forum_booked');
fXajax::register('forum_auditBook');
fXajax::register('forum_listcategory');