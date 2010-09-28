<?php
class FAjax_post extends FAjaxPluginBase {

  static function avatarFromInput($data) {
		$user = FUser::getInstance();
		$recipientId = FUser::getUserIdByName($data['username']) * 1;
		$ret = FAvatar::showAvatar($recipientId);
		
		FAjax::addResponse($data['result'],$data['resultProperty'],$ret);
	}
	
}