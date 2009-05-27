<?php
class FAjax_user {
static function switchFriend($data) {
    if(empty($data)) {
      //---chech _GET for alternative
      if(!empty($_GET['d']) {
        $dataArr = explode(';',$_GET['d']);
        if(count($dataArr)>0) {
          foreach($dataArr as $var) {
            list($k,$v) = explode(':',$var);
            $data[$k] = $v;
          }
        }
      } else {
        return false;
      }
    }
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