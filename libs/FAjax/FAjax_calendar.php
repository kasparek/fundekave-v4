<?php
class FAjax_calendar {
  static function walk($data) {
		$ret = FLeftPanelPlugins::rh_diar_kalendar($data['year'],$data['month']);
		//---create response
		//---$objResponse->assign('fcalendar', 'innerHTML', $data);
		$fajax = FAfax::getInstance();
		$fajax->addResponse('fcalendar', 'html', $ret);
	}
	
}