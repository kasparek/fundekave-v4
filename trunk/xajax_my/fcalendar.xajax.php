<?php
function fcalendar_monthSwitch($year,$month) {
	global $user;
	$data = fLeftPanel::rh_diar_kalendar($year,$month);
	$objResponse = new xajaxResponse();
	$objResponse->setCharacterEncoding(CHARSET);
	$objResponse->assign('fcalendar', 'innerHTML', $data);
	return $objResponse;
} 

fXajax::register('fcalendar_monthSwitch');