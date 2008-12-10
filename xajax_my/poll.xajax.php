<?php
$nonUserInit = false;
function poll_pollVote($params) {	
	global $user;
	list($ankid,$odpid) = explode(":",$params);
	$data = fLeftPanel::rh_anketa($ankid,$odpid,& $user,true);
	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('poll', 'innerHTML', $data);
	$objResponse->call('setPollListeners');
	return $objResponse;
}
fXajax::register('poll_pollVote');