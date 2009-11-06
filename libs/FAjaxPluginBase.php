<?php
class FAjaxPluginBase {
	public static function validate($data) {
		$user = FUser::getInstance();
		$ret = $user->idkontrol;
		if($user->pageAccess===false) $ret = false; 
		return $ret;
	}
}