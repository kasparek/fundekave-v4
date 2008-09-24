<?php
class fUserDraft {
  static function getAction() {
        return ' onKeyup="setDraftElement(this);handleDraft();" ';
    }
    static function clear($placeId) {
        global $db,$user;
        if(!empty($user->gid))
            $db->query("delete from sys_users_draft where userId='".$user->gid."' and place='".$placeId."'");
    }
    static function get($placeId) {
        global $db,$user;
        if(!empty($user->gid)) {
            $dot = "select text from sys_users_draft where userId='".$user->gid."' and place='".$placeId."'";
            return $db->getOne($dot);
        }
    }
    static function save($place,$text) {
        global $db,$user;
        if(!empty($user->gid))
            return $db->query("insert into sys_users_draft (userId,place,text) values ('".$user->gid."','".$place."','".$text."') on duplicate key update text = '".$text."'");
    }
}
?>