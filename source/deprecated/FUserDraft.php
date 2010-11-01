<?php
class FUserDraft {
	static function clear($placeId) {
		$user = FUser::getInstance();
		if($user->userVO->userId > 0) {
			$placeList = explode(',',$placeId);
			foreach($placeList as $k=>$v) $placeList[$k] = FSystem::safeText($v); 
			FDBTool::query("delete from sys_users_draft where userId='".$user->userVO->userId."' and place in ('".implode("','",$placeList)."')");
		}
	}
	static function get($place) {
		$user = FUser::getInstance();
		if($user->userVO->userId > 0) {
			return FDBTool::getOne("select text from sys_users_draft where userId='".$user->userVO->userId."' and place='".FSystem::safeText($place)."'");
		}
	}
	static function save($place,$text) {
		$user = FUser::getInstance();
		if($user->userVO->userId > 0) {
			$db=FDBConn::getInstance();
			$text = $db->escape($text); 
			return FDBTool::query("insert into sys_users_draft (userId,place,text) values ('".$user->userVO->userId."','".FSystem::safeText($place)."','".$text."') on duplicate key update text = '".$text."'");
		}
	}
}