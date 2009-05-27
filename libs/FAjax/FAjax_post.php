<?php
class FAjax_post {

  static function avatarFromInput($data) {
		$user = FUser::getInstance();
		$recipientId = FUser::getUserIdByName($data['username']) * 1;
		$ret = FAvatar::showAvatar($recipientId,array('showName'=>true,'withTooltip'=>true));
		
		//---create response
			$retData[] = array('target'=>$data['result'],'property'=>$data['resultProperty'],'value'=>$ret);
			unset($data['result']);
			unset($data['resultProperty']);
			return FAjax::buildResponse($retData, $data);
		
	}
	
}