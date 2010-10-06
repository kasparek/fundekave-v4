<?php
class FAjax_user extends FAjaxPluginBase {
		
	static function clientInfo($data) {
		$user = FUser::getInstance();
		$user->userVO->clientWidth = $data['view-width'];
		$user->userVO->clientHeight = $data['view-height']; 
		FAjax::addResponse('void','void','1');
	}

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
	
	static function friendremove($data) {
		$user = FUser::getInstance();
		$user->userVO->removeFriend( (int) $data['u']);
		FError::add(FLang::$MSG_FRIEND_REMOVED,1);
		FAjax::redirect(FSystem::getUri('','frien',''));
	}
	
	static function friendrequest($data) {
		$tpl = FSystem::tpl('friend.request.tpl.html');
		$tpl->setVariable('ACTION',FSystem::getUri('m=user-friendrequestsend'));
		$tpl->setVariable('USER',$data['u']);
		$ret = $tpl->get();
		FAjax::addResponse('call','remove','friendrequest,1');
		FAjax::addResponse('okmsgJS','$after',$ret);
		FAjax::addResponse('call','friendRequestInit');
	}
	
	static function friendrequestsend($data) {
		$user = FUser::getInstance();
	
		$itemVO = new ItemVO();
		$itemVO->typeId = 'request';
		$itemVO->text = FSystem::textins($data['message']);
		$itemVO->pageId = 'frien';
		$itemVO->userId = $user->userVO->userId;
		$itemVO->name = $user->userVO->name;
		$itemVO->userId = $user->userVO->userId;
		$itemVO->addon = $data['user'];
		$itemVO->save();
		
		sleep(2);
		FAjax::addResponse('call','remove','friendButt');
		FAjax::addResponse('call','remove','friendrequest');
		FAjax::addResponse('okmsgJS','$html','Request sent');
	}
	
	static function requestaccept($data) {
		$action = $data['action'];
		switch($action) {
			case 'requestaccept':
				$itemVO = new ItemVO();
				$itemVO->itemId = $data['request'];
				$itemVO->load();
				
				$user = FUser::getInstance();
				$user->userVO->addFriend( (int) $itemVO->userId );
				FError::add(FLang::$MSG_FRIEND_ADDED,1);
				FAjax::addResponse('call','remove','request'.$itemVO->userId);
				
				$itemVO->delete();
				
				//send message
				FMessages::send((int) $itemVO->userId,FLang::$MSG_FRIEND_REQUEST_ACCEPTED,$user->userVO->userId);
				break;
			case 'requestcancel':
				$itemVO = new ItemVO();
				$itemVO->itemId = $data['request'];
				$itemVO->load();
				
				FError::add(FLang::$MSG_FRIEND_CANCEL,1);
				FAjax::addResponse('call','remove','request'.$itemVO->userId);

				$itemVO->delete();
				break;
		}
		//clear cache 'friendrequest','default'
		$cache = FCache::getInstance('s');
		$cache->invalidateData('friendrequest');
	}
	
	static function settings($data) {
		page_UserSettings::process($data);
	}
	
	static function avatar($data) {
		//---list user images
		$user = FUser::getInstance();
		$dir = FAvatar::profileBasePath();
		$arr = FFile::fileList($dir,'jpg');
		sort($arr);
		$arr = array_reverse($arr);
		$tpl = FSystem::tpl("users.personal.html");
		$ret = '';
		foreach($arr as $img) {
			$tpl->setVariable("IMGURL",FAvatar::profileBaseUrl().'/'.$img);
			$tpl->setVariable("IMGID",md5($img));
			$tpl->parse('personalImage');
		}
		$ret .= $tpl->get('personalImage');
		FAjax::addResponse('personalfoto', '$html', $ret);
		FAjax::addResponse('folderSize', '$html', round(FFile::folderSize($dir)/1024).'kB');
		FAjax::addResponse('call','fconfirmInit');
		FAjax::addResponse('call','fajaxformInit');
		FAjax::addResponse('call','slimboxInit');
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
		$itemId = $data['item'];

		if($userId = FUser::logon()) {
			if(FItemTags::isTagged()) {
				FError::add(FLang::$MESSAGE_TAG_ONLYONE);
				return;
			}
			//clean cache
			FItemTags::invalidateCache();
			FCommand::run(ITEM_UPDATED,new ItemVO($itemId,true));
			
			if(!isset($data['a'])) $data['a'] = 'a';
			
			if($data['a']=='r') {
				FItemTags::removeTag($itemId,$userId);
			} else {
				FItemTags::tag($itemId,$userId);	
			}

			//---create response
			if($data['__ajaxResponse']==true) { 
				FAjax::addResponse('tag'.$itemId,'$html',FItemTags::getTag($itemId,$userId));
				FAjax::addResponse('call','fajaxaInit');
			}
		} else {
			FError::add(FLang::$MESSAGE_TAG_REGISTEREDONLY);
		}
	}

	static function poll($data) {
		FAjax::addResponse('rh_anketa','$html',FLeftPanelPlugins::rh_anketa($data['po'],$data['an']));
		FAjax::addResponse('call','setPollListeners');
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