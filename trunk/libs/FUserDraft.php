<?php
class FUserDraft {
  static function getAction() {
        return ' onKeyup="setDraftElement(this);handleDraft();" ';
    }
    static function clear($placeId) {
        $user = FUser::getInstance();
        if($user->userVO->userId > 0)
            FDBTool::query("delete from sys_users_draft where userId='".$user->userVO->userId."' and place='".$placeId."'");
    }
    static function get($placeId) {
        $user = FUser::getInstance();
        if($user->userVO->userId > 0) {
            $dot = "select text from sys_users_draft where userId='".$user->userVO->userId."' and place='".$placeId."'";
            return FDBTool::getOne($dot);
        }
    }
    static function save($place,$text) {
        $user = FUser::getInstance();
        if($user->userVO->userId > 0)
            return FDBTool::query("insert into sys_users_draft (userId,place,text) values ('".$user->userVO->userId."','".$place."','".$text."') on duplicate key update text = '".$text."'");
    }
}