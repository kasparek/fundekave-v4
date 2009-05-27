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
	static function tag($data) {
  
  
  $itemId = substr($itemId,1);
  
	$ret = false;
	
	if($userId = FUser::logon()) {
	
  //clean cache
	      $cache = FCache::getInstance('s');
        $cache->invalidateGroup('mytags');
	
	  
	  $cache = FCache::getInstance('f');
	  $cache->invalidateGroup('items'); //TODO: check all places where items are cache so using this group

    if(FItems::tag($itemId,$userId)) $ret = true;
  }
  
	if($ret==true) $retData[] = array('target'=>'tag'.$itemId, 'property'=>'html', 'value'=>FItems::getTag($itemId,$userId));
	return FAjax::buildResponse($retData, $data);
  
  
  }
}