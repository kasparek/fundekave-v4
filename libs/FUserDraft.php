<?php
class FUserDraft {
  static function getAction() {
        return ' onKeyup="setDraftElement(this);handleDraft();" ';
    }
    static function clear($placeId) {
        $db = FDBConn::getInstance();
        $user = FUser::getInstance();
        if($user->userVO->userId > 0)
            $db->query("delete from sys_users_draft where userId='".$user->userVO->userId."' and place='".$placeId."'");
    }
    static function get($placeId) {
        $db = FDBConn::getInstance();
        $user = FUser::getInstance();
        if($user->userVO->userId > 0) {
            $dot = "select text from sys_users_draft where userId='".$user->userVO->userId."' and place='".$placeId."'";
            return $db->getOne($dot);
        }
    }
    static function save($place,$text) {
        $db = FDBConn::getInstance();
        $user = FUser::getInstance();
        if($user->userVO->userId > 0)
            return $db->query("insert into sys_users_draft (userId,place,text) values ('".$user->userVO->userId."','".$place."','".$text."') on duplicate key update text = '".$text."'");
    }
}