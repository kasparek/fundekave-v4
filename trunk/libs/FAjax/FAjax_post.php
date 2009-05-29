<?php
class FAjax_post {

  static function avatarFromInput($data) {
		$user = FUser::getInstance();
		$recipientId = FUser::getUserIdByName($data['username']) * 1;
		$ret = FAvatar::showAvatar($recipientId,array('showName'=>true,'withTooltip'=>true));
		
		//---create response
		$fajax = FAfax::getInstance();
		$fajax->addResponse($data['result'],$data['resultProperty'],$ret);
	}
	
}