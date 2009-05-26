<?php
class FAjax {
	static function post_AvatarFromInput() {
		$user = FUser::getInstance();
		$recipientId = FUser::getUserIdByName($_POST['username']) * 1;
		$data = FAvatar::showAvatar($recipientId);
		return $data;
	}
}