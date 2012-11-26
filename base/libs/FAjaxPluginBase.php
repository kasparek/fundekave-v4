<?php
/**
 * Base class for all ajax plugins in FAjax folder
 * @author Frantisek Kaspar
 *
 */
class FAjaxPluginBase {
	public static function validate($data) {
		$user = FUser::getInstance();
		return $user->pageAccess;
	}
}