<?php
class FAjaxPluginBase {
	public static function validate($data) {
		$user = FUser::getInstance();
		return $user->pageAccess;
	}
}