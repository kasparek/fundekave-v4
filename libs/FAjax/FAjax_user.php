<?php
class FAjax_user {
	static function switchFriend($data) {
		$userIdFriend = $data['user'];
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
			if($data['__ajaxResponse']==true) {
				$fajax = FAjax::getInstance();
				$fajax->addResponse($data['result'],$data['resultProperty'],$ret);
			}
		}
	}
	
	static function book($data) {
		if(($userId = FUser::logon()) !==false) {
			if (FDBTool::getOne("select book from sys_pages_favorites where pageId = '".$data['page']."' AND userId = '".$userId."'")) {
				$book = 0;
				$data = FLang::$LABEL_BOOK;
			} else {
				$book = 1;
				$data = FLang::$LABEL_UNBOOK;
			}
			FDBTool::query("update sys_pages_favorites set book='".$book."' where pageId='".$data['page']."' AND userId='" . $userId."'");
			if($data['__ajaxResponse']==true) {
				//---create response
				$fajax = FAjax::getInstance();
				$fajax->addResponse($data['result'],$data['resultProperty'],$data);
			}
		}	
	}

	static function tag($data) {
		//$itemId = substr($itemId,1);
		$itemId = $data['item'];

		if($userId = FUser::logon()) {
			//clean cache
			$cache = FCache::getInstance('s');
			$cache->invalidateGroup('mytags');
			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('items'); //TODO: check all places where items are cache so using this group
			
			if(!isset($data['a'])) $data['a'] = 'a';
			
			if($data['a']=='r') {
				FItemTags::removeTag($itemId,$userId);
			} else {
				FItemTags::tag($itemId,$userId);	
			}

			//---create response
			if($data['__ajaxResponse']==true) { 
				$fajax = FAjax::getInstance();
				$fajax->addResponse('tag'.$itemId,'html',FItemTags::getTag($itemId,$userId));
				$fajax->addResponse('function','call','fajaxa');
			}
		}
	}

	static function poll($data) {
		$fajax = FAjax::getInstance();
		$fajax->addResponse('poll','html',FLeftPanelPlugins::rh_anketa($data['po'],$data['an'],true));
		$fajax->addResponse('function','call','setPollListeners');
	}

	static function pocketIn($data) {
		$fPocket = new FPocket(FUser::logon());
		$fPocket->saveItem(((isset($data['item']))?($data['item']):('')),((isset($data['page']))?($data['page']):('')));
		$fajax = FAjax::getInstance();
		$fajax->addResponse('pocket','html',$fPocket->show(true));
	}

	static function pocketAc($data) {
		$fPocket = new FPocket(FUser::logon());
		$fPocket->action($data['ac'],$data['pocket']);
		$fajax = FAjax::getInstance();
		$fajax->addResponse('pocket','html',$fPocket->show(true));
	}
}