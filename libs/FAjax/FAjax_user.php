<?php
class FAjax_user extends FAjaxPluginBase {
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
				FAjax::addResponse($data['result'],$data['resultProperty'],$ret);
			}
		}
	}
	
	static function avatar($data) {
		$userId = FUser::logon();
		FAjax::addResponse($data['result'], $data['resultProperty'], FAvatar::showAvatar($userId));
	}
	
	static function book($data) {
		if(($userId = FUser::logon()) !==false) {
			if (FDBTool::getOne("select book from sys_pages_favorites where pageId = '".$data['page']."' AND userId = '".$userId."'")) {
				$book = 0;
				$ret = FLang::$LABEL_BOOK;
			} else {
				$book = 1;
				$ret = FLang::$LABEL_UNBOOK;
			}
			FDBTool::query("update sys_pages_favorites set book='".$book."' where pageId='".$data['page']."' AND userId='" . $userId."'");
			if($data['__ajaxResponse']==true) {
				//---create response
				FAjax::addResponse($data['result'], $data['resultProperty'], $ret);
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
				FAjax::addResponse('tag'.$itemId,'html',FItemTags::getTag($itemId,$userId));
				FAjax::addResponse('function','call','fajaxa');
			}
		}
	}

	static function poll($data) {
		FAjax::addResponse('rh_anketa','html',FLeftPanelPlugins::rh_anketa($data['po'],$data['an']));
		FAjax::addResponse('function','call','setPollListeners');
	}

	static function pocketIn($data) {
		$fPocket = new FPocket(FUser::logon());
		$fPocket->saveItem(((isset($data['item']))?($data['item']):('')),((isset($data['page']))?($data['page']):('')));
		FAjax::addResponse('pocket','html',$fPocket->show(true));
	}

	static function pocketAc($data) {
		$fPocket = new FPocket(FUser::logon());
		$fPocket->action($data['ac'],$data['pocket']);
		FAjax::addResponse('pocket','html',$fPocket->show(true));
	}
	static function settings($data) {
		if(isset($data['uploadify'])) {
			//---get new avatar
			$user = FUser::getInstance();
			$cache = FCache::getInstance('d');
			$arr = $cache->getData($user->userVO->userId . '-' .$data['modul'],'uploadify');
			if(!empty($arr)) {
				$avatarName = FAvatar::createName($arr["filenameOriginal"]);
				if(file_exists(WEB_REL_AVATAR.$avatarName)) unlink(WEB_REL_AVATAR.$avatarName);
				rename($arr['filenameTmp'],WEB_REL_AVATAR.$avatarName);
				chmod(WEB_REL_AVATAR.$avatarName,0777);
				$user->userVO->avatar = FAvatar::processAvatar($avatarName);
				$user->userVO->save();
				//---create response
				//---return new avatar
				FAjax::addResponse('rh_login','html',FLeftPanelPlugins::rh_login());
			}
		}
	}
	static function postFilter($data) {
		if(!empty($data['user'])) {
			$userId = (int) $data['user'];
			if(FUser::isUserIdRegistered($userId)) {
				//---set filter for post
				$cache = FCache::getInstance('s');
				$cache->setData(FUser::getgidname($userId), 'name', 'filtrPost');
				if(isset($data['s'])) {
					if($data['s']=='s:received' || $data['s']=='s:sent') {
						$cache->setData($data['s'], 'select', 'filtrPost');
					}	
				}
			}
		}
	}
}