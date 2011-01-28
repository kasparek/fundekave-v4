<?php
class Sidebar_page {
	static function show() {
		$user = FUser::getInstance();
		$sideData = $user->pageVO->prop('sidebar');
		if(empty($sideData)) return false;

		return $sideData; 
	}
}