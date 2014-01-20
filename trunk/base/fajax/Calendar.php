<?php
class fajax_Calendar extends FAjaxPluginBase {
  static function show($data) {
		$user = FUser::getInstance();
		$viewmode = (int) $data['viewmode'];
		$user->year = null;
		$user->month = null;
		$user->day = null;
		if($viewmode < 2) {
			$date = $data['year'].(!empty($data['month']) && $viewmode==0?'-'.$data['month']:'');
			if(empty($date)) return; //missing date
			if(!$user->inDate($date)) return; //invalid date
		}
		$ret = sidebar_calendar::show((object) array('coreOnly'=>true));
		FAjax::addResponse('call', 'calendarLoaded', $data['loading']);
		FAjax::addResponse('call', 'calendarUpdate', $ret);
	}
}