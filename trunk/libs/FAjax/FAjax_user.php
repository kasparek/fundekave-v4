<?php
class FAjax_user {
	static function switchFriend($data) {
		if(empty($data)) {
			//---chech _GET for alternative
			if(!empty($_GET['d'])) {
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
			$fajax = FAfax::getInstance();
			$fajax->addResponse($data['result'],$data['resultProperty'],$ret);

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
			
			//---create response
			$fajax = FAfax::getInstance();
			$fajax->addResponse($data['result'],$data['resultProperty'],$data);
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

			if(FItems::tag($itemId,$userId)) {
				//---create response
				$fajax = FAfax::getInstance();
				if($ret==true) $fajax->addResponse('tag'.$itemId,'html',FItems::getTag($itemId,$userId));
			}
		}
	}

	static function poll($data) {
		list($ankid,$odpid) = explode(":",$data['poll']);
		$fajax = FAfax::getInstance();
		$fajax->addResponse('poll','html',fLeftPanelPlugins::rh_anketa($ankid,$odpid,true));
	}

	static function pocketIn($data) {
		$fPocket = new FPocket(FUser::logon());
		$fPocket->saveItem(((isset($data['item']))?($data['item']):('')),((isset($data['page']))?($data['page']):('')));
		$fajax = FAfax::getInstance();
		$fajax->addResponse('pocket','html',$fPocket->show(true));
	}

	static function pocketAc($data) {
		$fPocket = new FPocket(FUser::logon());
		$fPocket->action($data['ac'],$data['pocket']);
		$objResponse->assign('pocket', 'innerHTML', $fPocket->show(true));
	}
}