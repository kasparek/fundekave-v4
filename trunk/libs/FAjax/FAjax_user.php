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
			
			//---create response
			$retData[] = array('target'=>$data['result'],'property'=>$data['resultProperty'],'value'=>$ret);
			unset($data['result']);
			unset($data['resultProperty']);
			return FAjax::buildResponse($retData, $data);
		}
	}
}