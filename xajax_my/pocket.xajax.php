<?php
$nonUserInit = false;
function pocket_add($itemId,$page=false) {	
	global $user;
	$fPocket = new fPocket($user->gid);
  $fPocket->saveItem($itemId,$page);
  $data = $fPocket->show(true);

  $objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('pocket', 'innerHTML', $data);
	return $objResponse;
}
function pocket_action($action,$pocketId) {
    global $user;    
    $fPocket = new fPocket($user->gid);
    $fPocket->action($action,$pocketId);
    $data = $fPocket->show(true);
    
    $objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('pocket', 'innerHTML', $data);
	return $objResponse;
}
fXajax::register('pocket_add');
fXajax::register('pocket_action');