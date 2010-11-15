<?php
class FAjax_user extends FAjaxPluginBase {

	static function avatar($data) {
		$user = FUser::getInstance();
		//create filename
		$ffile = new FFile(FConf::get("galery","ftpServer"));
		$x=1;
		$tmpFilename = FFile::getTemplFilename();
		$ext = FFile::fileExt($tmpFilename);
		do {
			$filename = strtolower($user->userVO->name.'.'.$user->userVO->userId.'.'.sprintf("%03d",$x).'.'.$ext);
			$x++;
		} while($ffile->file_exists(FAvatar::profileBasePath().'/'.$filename));
		//move file
		$ffile->makeDir(FAvatar::profileBasePath());
		$ffile->rename(FConf::get('galery','sourceServerBase').$tmpFilename,FAvatar::profileBasePath().'/'.$filename);
		//save changes
		$user->userVO->avatar = $filename;
		$user->userVO->save();
		FCommand::run(AVATAR_UPDATED,$user->userVO->userId);
		//build response
		$tpl = FSystem::tpl('users.personal.html');
		$cache = FCache::getInstance('d');
		$fileList = $cache->getData($userVO->userId,'profileFiles');
		if($fileList===false) {
			$ffile = new FFile(FConf::get("galery","ftpServer"));
			$fileList=$ffile->fileList(FAvatar::profileBasePath());
			$cache->setData($fileList);
		}
		if(!empty($fileList)) {
			sort($fileList);
			while($file = array_pop($fileList)) {
				$tpl->setVariable("IMGURL",FConf::get('galery','targetUrlBase').'800/prop/'.strtolower($user->userVO->name).'/profile/'.$file);
				$tpl->setVariable("THUMBURL",FConf::get('galery','targetUrlBase').FConf::get('galery','horiz_thumbCut').'/'.strtolower($user->userVO->name).'/profile/'.$file);
				$tpl->setVariable('USEAVATARIMGID','-'.FSystem::safetext($file));
				$tpl->setVariable('IMGID','-'.FSystem::safetext($file));
				$tpl->parse("foto");
			}
		}
		FAjax::addResponse('personalfoto','$html',$tpl->get('foto'));
		FAjax::addResponse('avatarBox','$html',FAvatar::showAvatar());
	}

	static function clientInfo($data) {
		$user = FUser::getInstance();
		if(!empty($data['size'])){
			list($w,$h) = explode('x',$data['size']);
			$user->userVO->clientWidth = (int) $w*1;
			$user->userVO->clientHeight = (int) $h*1;
		}
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
		FAjax::redirect(FSystem::getUri('','finfo',''));
	}

	static function friendrequest($data) {
		$tpl = FSystem::tpl('friend.request.tpl.html');
		$tpl->setVariable('ACTION',FSystem::getUri('m=user-friendrequestsend'));
		$tpl->setVariable('USER',$data['u']);
		$ret = $tpl->get();
		FAjax::addResponse('call','friendRequestInit',$ret);
	}

	static function friendrequestsend($data) {
		$user = FUser::getInstance();

		$itemVO = new ItemVO();
		$itemVO->typeId = 'request';
		$itemVO->text = FSystem::textins($data['message']);
		$itemVO->pageId = 'finfo';
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
		$itemId = (int) $data['item'];
		if($userId = FUser::logon()) {
			if(FItemTags::isTagged($itemId,$userId)) {
				FError::add(FLang::$MESSAGE_TAG_ONLYONE);
			} else {
				if(!isset($data['a'])) $data['a'] = 'a';
				if($data['a']=='r') FItemTags::removeTag($itemId,$userId);
				else FItemTags::tag($itemId,$userId);
				//---create response
				if($data['__ajaxResponse']==true) {
					FAjax::addResponse('tag'.$itemId,'$html',FItemTags::getTag($itemId,$userId));
					FAjax::addResponse('call','fajaxInit');
				}
			}
		} else {
			FError::add(FLang::$MESSAGE_TAG_REGISTEREDONLY);
		}
		if($data['__ajaxResponse']==false) {
			FHTTP::redirect(FSystem::getUri('i='.$itemId,'',''));
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