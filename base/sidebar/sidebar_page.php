<?php
class sidebar_page {
	static function show() {
		$user = FUser::getInstance();
    if(!$user->pageVO) return false;
		$sideData = $user->pageVO->prop('sidebar');
		if(empty($sideData)) return false;
		return $sideData; 
	}
}