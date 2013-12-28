<?php
/**
 *  TODO:
 *  fix kluby, galerie - stranky
 *  profil fotky - galerie na hlavni zalozce
 *  z infa zachovat pouze - motto, info, icq, www
 *
 **/
include_once('iPage.php');
class page_UserInfo implements iPage {

	static function tabPageList($userId,$typeId) {
		switch($typeId) {
			case 'galery':
				$output = '<h3>'.FLang::$LABEL_GALERIES.'</h3>';
				break;
			case 'forum':
				$output = '<h3>'.FLang::$LABEL_FORUMS.'</h3>';
				break;
			case 'blog':
				$output = '<h3>'.FLang::$LABEL_BLOGS.'</h3>';
				break;
			case 'booked':
				$output = '<h3>'.FLang::$LABEL_BOOKED.'</h3>';
				break;
			default:
				return '<h3>Not valid tab</h3>';
		}
		$user = FUser::getInstance();
		$fp = new FPages($typeId=='booked'?'':$typeId,$user->userVO->userId);
		if($typeId=='booked') {
			$bookOrder = $user->userVO->getXMLVal('settings','bookedorder') * 1;
			$fp->addJoin('join sys_pages_favorites as f2 on sys_pages.pageId=f2.pageId and f2.userId="'.$userId.'"');
			$fp->setWhere('f2.book="1" and sys_pages.userIdOwner!="'.$userId.'" and sys_pages.locked<2');
			$fp->setOrder($bookOrder==1?'sys_pages.name':($typeId=='galery'?'sys_pages.dateCreated desc':'(sys_pages.cnt-favoriteCnt) desc,sys_pages.name'));
		} else {
			$fp->setWhere('sys_pages.userIdOwner="'.$userId.'" and sys_pages.locked<3');
			$fp->setOrder('sys_pages.name');
		}
		
		$arrLinks = $fp->getContent();
		
		if(!empty($arrLinks)){
			$output .= FPages::printPagelinkList($arrLinks,array('noitem'=>1));
		}
		return $output;
	}

	static function process($data) {
		
	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$fajax = FAjax::getInstance();
		$includeTab = false;
		if(!empty($fajax->data['tab'])) $includeTab = str_replace('tab-','',$fajax->data['tab']);
		if($fajax->data['__ajaxResponse']) {
			$who = $user->whoIs;
			if(!empty($data['who'])) $who = (int) $data['who'];
			return self::tabPageList($who?$who:$user->userVO->userId,$includeTab);
		}
		FMenu::secondaryMenuAddItem(FSystem::getUri('','fpost'), FLang::$LABEL_POST);
		FMenu::secondaryMenuAddItem(FSystem::getUri('','fedit',''), FLang::$LABEL_PERSONALSETTINGS);
		$tpl = FSystem::tpl('users.info.tpl.html');
		$isFriend = false;
		if($who = $user->whoIs) {
			$userVO = new UserVO();
			$userVO->userId = $who;
			$userVO->load();
			if($user->idkontrol) {
				if($user->userVO->userId != $userVO->userId) {
					if($userVO->isFriend($user->userVO->userId)) {
						//button remove friend
						$tpl->setVariable('FRIENDLINKURL',FSystem::getUri('m=user-friendremove&d=u='.$userVO->userId));
						$tpl->setVariable('FRIENDLINKLABEL',FLang::$LABEL_FRIEND_REMOVE);
						$tpl->setVariable('FRIENDLINKID','removeFriendButt');
						$tpl->setVariable('FRIENDLINKCLASS',' confirm');
						$tpl->setVariable('FRIENDLINKTITLE',FLang::$LABEL_FRIEND_REMOVE_CONFIRM);
						$isFriend = true;
					} else {
						if(!$userVO->isRequest($user->userVO->userId)) {
							//button send frien request
							$tpl->setVariable('FRIENDLINKURL',FSystem::getUri('m=user-friendrequest&d=u='.$userVO->userId));
							$tpl->setVariable('FRIENDLINKLABEL',FLang::$LABEL_FRIEND_ADD);
							$tpl->setVariable('FRIENDLINKID','friendButt');
							$tpl->setVariable('FRIENDLINKCLASS','');
							$tpl->setVariable('FRIENDLINKTITLE',FLang::$TITLE_FRIEND_ADD);
						} else {
							FError::add(FLang::$MSG_REQUEST_WAITING,1);
						}
					}
					//button send message
					$tpl->setVariable('SENDMSGURL',FSystem::getUri('who='.$userVO->userId,'fpost',''));
					$tpl->setVariable('SENDMSGLABEL',FLang::$SEND_MESSAGE);
				}
			}
		} else {
			if($user->idkontrol) {
				$userVO = $user->userVO;
			} else {
				$user->pageAccess = false;
				FError::add(FLang::$ERROR_ACCESS_DENIED);
				return;
			}
		}
		$tpl->setVariable('AVATAR',FAvatar::showAvatar($userVO->userId));
		$tpl->setVariable('NAME',$userVO->name);
		$tpl->setVariable("DATECREATED",$userVO->dateCreated);
		$tpl->setVariable("DATEUPDATED",$userVO->dateLastVisit);
		if(!empty($userVO->email)) $tpl->setVariable('EMAIL',$userVO->email);
		if(!empty($userVO->icq)) $tpl->setVariable('ICQ',$userVO->icq);
		if(($www = $userVO->getXMLVal('personal','www')) !='' ) $tpl->setVariable("WWW",$www);
		if(($motto=$userVO->getXMLVal('personal','motto')) !='') $tpl->setVariable("MOTTO",$motto);
		if(($about=$userVO->getXMLVal('personal','about')) !='') $tpl->setVariable("ABOUT",$about);

		$cache = FCache::getInstance('d');
		$fileList = $cache->getData($userVO->userId,'profileFiles');
		if($fileList===false) {
			$ffile = new FFile(FConf::get("galery","ftpServer"));
			$fileList=$ffile->fileList(FAvatar::profileBasePath($userVO));
			$cache->setData($fileList);
		}
		if(!empty($fileList)) {
			sort($fileList);
			while($file = array_pop($fileList)) {
				$tpl->setVariable("IMGURL",FConf::get('galery','targetUrlBase').'800x800/prop/'.strtolower($userVO->name).'/profile/'.$file);
				$tpl->setVariable("THUMBURL",FConf::get('galery','targetUrlBase').FConf::get('galery','horiz_thumbCut').'/'.strtolower($userVO->name).'/profile/'.$file);
				$tpl->parse("foto");	
			}
		}

		/**
		 * PAGES
		 */
		
		if($includeTab == 'forum') {
			$tpl->setVariable('FORUMS',self::tabPageList($userVO->userId,'forum'));
		}

		if($includeTab == 'galery') {
			$tpl->setVariable('GALERYS',self::tabPageList($userVO->userId,'galery'));
		}

		if($includeTab == 'blog') {
			$tpl->setVariable('BLOGS',self::tabPageList($userVO->userId,'blog'));
		}
		if($includeTab == 'booked') {
			$tpl->setVariable('BOOKED',self::tabPageList($userVO->userId,'booked'));
		}
		
		$tpl->setVariable('GALERYTABURL',FSystem::getUri('m=tab-show&d=tab=tab-galery'.($user->whoIs?';who='.$user->whoIs:'')));
		$tpl->setVariable('FORUMTABURL',FSystem::getUri('m=tab-show&d=tab=tab-forum'.($user->whoIs?';who='.$user->whoIs:'')));
		$tpl->setVariable('BLOGTABURL',FSystem::getUri('m=tab-show&d=tab=tab-blog'.($user->whoIs?';who='.$user->whoIs:'')));
		$tpl->setVariable('BOOKEDTABURL',FSystem::getUri('m=tab-show&d=tab=tab-booked'.($user->whoIs?';who='.$user->whoIs:'')));

		/**
		 * pratele
		 */
		$showFriendsTab = false;
		$friends='';
		
		if(empty($who)) {
			$arr = $user->userVO->loadRequests();
			if(!empty($arr)) $friends .= FUser::usersList( $arr, 'request', 'Requests' );
			$arr = $user->userVO->loadOnlineFriends();
			if(!empty($arr)) $friends .= FUser::usersList( $arr, 'online', 'Online' );
		}
		
		$arr = $userVO->loadFriends();
		if(!empty($arr)) {
			if(!empty($who)) {
				foreach($arr as $friend) {
					if($user->userVO->isFriend($friend->userId)) $arrFrCom[]=$friend; else $arrFr[]=$friend;
				}
			} else {
				$arrFr = $arr;
			}
			if(!empty($arrFr)) $friends .= FUser::usersList( $arrFr,'friends',FLang::$FRIENDS);
			if(!empty($arrFrCom)) $friends .= FUser::usersList( $arrFrCom,'commonFriends',FLang::$FRIENDS_COMMON);
			$showFriendsTab=true;
		}

		if(empty($who) && empty($friends)) {
			$showFriendsTab=true;
		} 
		if($showFriendsTab === true) {
			if(!empty($friends)) $tpl->setVariable('FRIENDS',$friends);
		}

		
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}