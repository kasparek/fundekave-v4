<?php
class FAjax_post extends FAjaxPluginBase {

  static function avatarFromInput($data) {
		$user = FUser::getInstance();
		$recipientId = FUser::getUserIdByName($data['username']) * 1;
		FAjax::addResponse('recipientavatar','$html',FAvatar::showAvatar($recipientId).'<br/>'.FUser::getgidname($recipientId));
	}
	
}