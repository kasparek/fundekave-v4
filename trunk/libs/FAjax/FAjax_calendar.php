<?php
class FAjax_calendar {
  static function walk($data) {

		$ret = fLeftPanelPlugins::rh_diar_kalendar($data['year'],$data['month']);
		
		//---create response
		//---$objResponse->assign('fcalendar', 'innerHTML', $data);
			$retData[] = array('target'=>$data['result'],'property'=>$data['resultProperty'],'value'=>$ret);
			unset($data['result']);
			unset($data['resultProperty']);
			return FAjax::buildResponse($retData, $data);
		
	}
	
}