<?php
class pocket {

	static function show() {
		$user = FUser::getInstance();
		$fPocket = new FPocket($user->userVO->userId);
		return $fPocket->show();
	}
	
}