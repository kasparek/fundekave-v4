<?php
class FAjax_draft {
  static function save($data) {

		FUserDraft::save($data['place'],$data['text']);
		
		//---create response
		$objResponse->call('draftSaved', $place);
		
			$retData[] = array('target'=>$data['result'],'property'=>$data['resultProperty'],'value'=>$ret);
			unset($data['result']);
			unset($data['resultProperty']);
			return FAjax::buildResponse($retData, $data);
		
	}
	
}