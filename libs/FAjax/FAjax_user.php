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
	
	static function friendremove($data) {
		$user = FUser::getInstance();
		$user->userVO->removeFriend( (int) $data['u']);
		FError::addError(FLang::$MSG_FRIEND_REMOVED,1);
		FAjax::redirect(FSystem::getUri('','frien',''));
	}
	
	static function friendrequest($data) {
		$tpl = FSystem::tpl('friend.request.tpl.html');
		$tpl->setVariable('ACTION',FSystem::getUri('m=user-friendrequestsend'));
		$tpl->setVariable('USER',$data['u']);
		$ret = $tpl->get();
		FAjax::addResponse('function','call','remove;friendrequest,1');
		FAjax::addResponse('okmsgJS','$after',$ret);
		FAjax::addResponse('function','getScript',URL_JS.'jquery.form.fcut.min.js;friendRequestInit');
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
		FAjax::addResponse('function','call','remove;friendButt');
		FAjax::addResponse('function','call','remove;friendrequest');
		FAjax::addResponse('okmsgJS','$html','Request sent');
	}
	
	static function requestaccept($data) {
		$action = $data['action'];
		switch($action) {
			case 'requestaccept':
				$itemVO = new ItemVO();
				$itemVO->itemId = $data['request'];
				$itemVO->load(false);
				
				$user = FUser::getInstance();
				$user->userVO->addFriend( (int) $itemVO->userId );
				FError::addError(FLang::$MSG_FRIEND_ADDED,1);
				FAjax::addResponse('function','call','remove;request'.$itemVO->userId);
				
				$itemVO->delete();
				
				//send message
				FMessages::send((int) $itemVO->userId,FLang::$MSG_FRIEND_REQUEST_ACCEPTED,$user->userVO->userId);
				break;
			case 'requestcancel':
				$itemVO = new ItemVO();
				$itemVO->itemId = $data['request'];
				$itemVO->load(false);
				
				FError::addError(FLang::$MSG_FRIEND_CANCEL,1);
				FAjax::addResponse('function','call','remove;request'.$itemVO->userId);

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
		$dir = ROOT_AVATAR . $user->userVO->name;
		$arr = FFile::fileList($dir,'jpg');
		sort($arr);
		$arr = array_reverse($arr);
		$tpl = FSystem::tpl("users.personal.html");
		$ret = '';
		foreach($arr as $img) {
			$tpl->setVariable("IMGURL",URL_AVATAR.$user->userVO->name.'/'.$img);
			$tpl->setVariable("IMGID",md5($img));
			$tpl->parse('personalImage');
		}
		$ret .= $tpl->get('personalImage');
		FAjax::addResponse('personalfoto', '$html', $ret);
		FAjax::addResponse('folderSize', '$html', round(FFile::folderSize($dir)/1024).'kB');
		FAjax::addResponse('function','call','fconfirm');
		FAjax::addResponse('function','call','fajaxform');
		FAjax::addResponse('function','call','initSlimbox');
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
				FAjax::addResponse('tag'.$itemId,'$html',FItemTags::getTag($itemId,$userId));
				FAjax::addResponse('function','call','fajaxa');
			}
		}
	}

	static function poll($data) {
		FAjax::addResponse('rh_anketa','$html',FLeftPanelPlugins::rh_anketa($data['po'],$data['an']));
		FAjax::addResponse('function','call','setPollListeners');
	}

	static function pocketIn($data) {
		$fPocket = new FPocket(FUser::logon());
		$fPocket->saveItem(((isset($data['item']))?($data['item']):('')),((isset($data['page']))?($data['page']):('')));
		FAjax::addResponse('pocket','$html',$fPocket->show(true));
	}

	static function pocketAc($data) {
		$fPocket = new FPocket(FUser::logon());
		$fPocket->action($data['ac'],$data['pocket']);
		FAjax::addResponse('pocket','$html',$fPocket->show(true));
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