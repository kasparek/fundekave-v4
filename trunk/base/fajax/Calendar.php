<?php
class fajax_Calendar extends FAjaxPluginBase {
  static function show($data) {
		$ret = sidebar_calendar::show($data['year'],$data['month'],true);
		//---create response
		FAjax::addResponse('call', 'calendarUpdate', $ret);
	}
	
}