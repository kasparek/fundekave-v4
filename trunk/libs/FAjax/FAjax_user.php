<?php
class FAjax_user {
static function switchFriend($data) {
		$userIdFriend = $data['userId'];
		if($userIdFriend > 0) {
			$user = FUser::getInstance();
			$user->userVO->getFriends();
			if($user->userVO->isFriend($userIdFriend)) {
				//remove
				$user->userVO->removeFriend($userIdFriend);
				$ret = FLang::$LABEL_FRIEND_ADD;
			} else {
				//add
				$user->userVO->addFriend($userIdFriend);
				$ret = FLang::$LABEL_FRIEND_REMOVE;
			}
			return $ret;
		}
	}
	static function TestTest($data) {
    file_put_contents('test.xml', $data);
    return 'ok';
  }
}