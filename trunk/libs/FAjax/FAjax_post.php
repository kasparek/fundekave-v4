<?php
class FAjax_post {

  static function avatarFromInput($data) {
		$user = FUser::getInstance();
		$recipientId = FUser::getUserIdByName($data['username']) * 1;
		$data = FAvatar::showAvatar($recipientId,array('showName'=>true,'withTooltip'=>true));
		return $data;
	}
	
}