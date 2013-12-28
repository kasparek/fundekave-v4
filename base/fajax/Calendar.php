<?php
class fajax_Calendar extends FAjaxPluginBase {
  static function show($data) {
		$user = FUser::getInstance();
		$date = $data['year'].(!empty($data['month'])?'-'.$data['month']:'');
		if(!empty($date)) return; //missing date
		if(!$user->inDate($date)) return; //invalid date
		$ret = sidebar_calendar::show((object) array('coreOnly'=>true));
		//---create response
		FAjax::addResponse('call', 'calendarUpdate', $ret);
	}
	
}