<?php
class FAjax_calendar {
  static function walk($data) {
		$ret = FLeftPanelPlugins::rh_diar_kalendar($data['year'],$data['month']);
		//---create response
		FAjax::addResponse('rh_diar_kalendar', 'html', $ret);
	}
	
}