<?php
include_once('iPage.php');
class page_UserInfo implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();

		if($who = $user->whoIs) {

			$userVO = new UserVO();
			$userVO->userId = $who;
			$userVO->load();
				
			if($userVO->isFriend($user->userVO->userId)) {
				//button remove friend
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=user-friendremove&d=u:'.$userVO->userId), FLang::$LABEL_FRIEND_REMOVE, 0, 'removeFriendButt','fajaxa confirm');
			} else {
				if(!$userVO->isRequest($user->userVO->userId)) {
					//button send frien request
					FMenu::secondaryMenuAddItem(FSystem::getUri('m=user-friendrequest&d=u:'.$userVO->userId), FLang::$LABEL_FRIEND_ADD, 0, 'friendButt','fajaxa');
				} else {
					FError::addError('Request Waitin',1);
				}
			}
				
			//button send message
			FMenu::secondaryMenuAddItem(FSystem::getUri('who='.$userVO->userId,'fpost',''),FLang::$SEND_MESSAGE);
				
			//users pages - galeries,forums,blogs
			
				
			//button favorites
				
			//events
				
			//button friends

		} else {

			$userVO = $user->userVO;

		}

		$tpl = FSystem::tpl('users.info.tpl.html');

		$tpl->setVariable('AVATAR',FAvatar::showAvatar($userVO->userId));
		$tpl->setVariable('NAME',$userVO->name);
		if(!empty($userVO->email)) $tpl->setVariable('EMAIL',$userVO->email);
		if(!empty($userVO->icq)) $tpl->setVariable('ICQ',$userVO->icq);

		if(($www = $userVO->getXMLVal('personal','www')) !='' ) $tpl->setVariable("WWW",$www);
		if(($motto=$userVO->getXMLVal('personal','motto')) !='') $tpl->setVariable("MOTTO",$motto);
		if(($place=$userVO->getXMLVal('personal','place')) !='') $tpl->setVariable("PLACE",$place);
		if(($food=$userVO->getXMLVal('personal','food')) !='') $tpl->setVariable("FOOD",$food);
		if(($hobby=$userVO->getXMLVal('personal','hobby')) !='') $tpl->setVariable("FOOD",$hobby);
		if(($about=$userVO->getXMLVal('personal','about')) !='') $tpl->setVariable("about",$about);

		/*
		 $homePageId = $userVO->getXMLVal('personal','HomePageId');
		 if(!empty($homePageId)) {
			$tpl->setVariable("HOMEPAGEID",$homePageId);
			$tpl->setVariable("HOMEPAGEUSERNAME",$userVO->name);
			}
			*/

		$tpl->setVariable("DATECREATED",$userVO->dateCreated);
		$tpl->setVariable("DATEUPDATED",$userVO->dateLastVisit);

		$dir = ROOT_AVATAR . $user->userVO->name;
		$arr = FFile::fileList($dir,'jpg');
		if(!empty($arr)) {
			$tpl->touchBlock('tabfoto');
			sort($arr);
			$arr = array_reverse($arr);
			$ret = '';
			foreach($arr as $img) {
				$tpl->setVariable("IMGURL",URL_AVATAR.$user->userVO->name.'/'.$img);
				$tpl->parse('foto');
			}
		}

		$fUvatar = new FUvatar($userVO->name,array('targetFtp'=>ROOT.'tmp/fuvatar/','refresh'=> $userVO->getXMLVal('webcam','interval'),'resolution'=> $userVO->getXMLVal('webcam','resolution')));
		//check if has any image from webcam
		if($fUvatar->hasData()) {
			$tpl->setVariable("WEBCAM",$fUvatar->getSwf());
		}
		
		$bookOrder = $user->userVO->getXMLVal('settings','bookedorder') * 1;
		/**
		 * PAGES
		 */
		$showPagesTab = false;
		$fp = new FPages('forum',$user->userVO->userId);
		$fp->fetchmode = 1;
		$fp->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
		$fp->addJoin('left join sys_pages_favorites as f on f.userId=p.userIdOwner');
		$fp->setWhere('p.userIdOwner="'.$userVO->userId.'" and p.pageId=f.pageId and p.locked<3');
		if($bookOrder==1) {
			$fp->setOrder('p.name');
		} else {
			$fp->setOrder('newMess desc,p.name');
		}
		$fp->setGroup('p.pageId');
		$arrLinks = $fp->getContent();
		if(count($arrLinks)>0){
			//pages
			$tpl->setVariable('FORUMS',FPages::printPagelinkList($arrLinks));
			$showPagesTab = true;
		}

		$fp = new FPages('blog',$user->userVO->userId);
		$fp->fetchmode = 1;
		$fp->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
		$fp->addJoin('left join sys_pages_favorites as f on f.userId=p.userIdOwner');
		$fp->setWhere('p.userIdOwner="'.$userVO->userId.'" and p.pageId=f.pageId and p.locked<3');
		if($bookOrder==1) {
			$fp->setOrder('p.name');
		} else {
			$fp->setOrder('newMess desc,p.name');
		}
		$fp->setGroup('p.pageId');
		$arrLinks = $fp->getContent();
		if(count($arrLinks)>0){
			//pages
			$tpl->setVariable('BLOGS',FPages::printPagelinkList($arrLinks));
			$showPagesTab = true;
		}
		
		$fp = new FPages('galery',$user->userVO->userId);
		$fp->fetchmode = 1;
		$fp->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
		$fp->addJoin('left join sys_pages_favorites as f on f.userId=p.userIdOwner');
		$fp->setWhere('p.userIdOwner="'.$userVO->userId.'" and p.pageId=f.pageId and p.locked<3');
		if($bookOrder==1) {
			$fp->setOrder('p.name');
		} else {
			$fp->setOrder('newMess desc,p.name');
		}
		$fp->setGroup('p.pageId');
		$arrLinks = $fp->getContent();
		if(count($arrLinks)>0){
			//pages
			$tpl->setVariable('GALERYS',FPages::printPagelinkList($arrLinks));
			$showPagesTab = true;
		}
		if($showPagesTab === true) $tpl->touchBlock('tabpages');
		
		/**
		 * pratele
		 */
		$showFriendsTab = false;
		$arrFriends = $user->userVO->loadFriends();
		if(!empty($arrFriends)) {
		foreach($arrFriends as $friend) {
			if($user->userVO->isFriend($friend->userId)) {
				$arrFrCom[]=$friend;
			} else {
				$arrFr[]=$friend;
			}
		}
		
			if(!empty($arrFr))$tpl->setVariable('FRIENDS',FUser::usersList( $arrFr,'friends',FLang::$FRIENDS));
			if(!empty($arrFrCom)) $tpl->setVariable('COMMONFRIENDS',FUser::usersList( $arrFrCom,'commonFriends',FLang::$FRIENDS_COMMON ));
			$showFriendsTab=true;
		}
		if($showFriendsTab === true) $tpl->touchBlock('tabfriends');
		
		/**
		 * FAVORITES
		 */
	
		$showFavoritesTab = false;
		$fp = new FPages('forum',$user->userVO->userId);
		$fp->fetchmode = 1;
		$fp->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
		$fp->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId="'.$userVO->userId.'"');
		$fp->setWhere('f.book="1" and p.userIdOwner!="'.$userVO->userId.'" and p.locked<2');
		if($bookOrder==1) {
			$fp->setOrder('p.name');
		} else {
			$fp->setOrder('newMess desc,p.name');
		}
		$fp->setGroup('p.pageId');
		$arrLinks = $fp->getContent();
		if(count($arrLinks)>0){
			//pages
			$tpl->setVariable('FORUMSFAV',FPages::printPagelinkList($arrLinks));
			$showFavoritesTab = true;
		}

		$fp = new FPages('blog',$user->userVO->userId);
		$fp->fetchmode = 1;
		$fp->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
		$fp->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId="'.$userVO->userId.'"');
		$fp->setWhere('f.book="1" and p.userIdOwner!="'.$userVO->userId.'" and p.locked<2');
		if($bookOrder==1) {
			$fp->setOrder('p.name');
		} else {
			$fp->setOrder('newMess desc,p.name');
		}
		$fp->setGroup('p.pageId');
		$arrLinks = $fp->getContent();
		if(count($arrLinks)>0){
			//pages
			$tpl->setVariable('BLOGSFAV',FPages::printPagelinkList($arrLinks));
			$showFavoritesTab = true;
		}
		
		$fp = new FPages('galery',$user->userVO->userId);
		$fp->fetchmode = 1;
		$fp->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,(p.cnt-f.cnt) as newMess,p.typeId');
		$fp->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId="'.$userVO->userId.'"');
		$fp->setWhere('f.book="1" and p.userIdOwner!="'.$userVO->userId.'" and p.locked<2');
		if($bookOrder==1) {
			$fp->setOrder('p.name');
		} else {
			$fp->setOrder('newMess desc,p.name');
		}
		$fp->setGroup('p.pageId');
		$arrLinks = $fp->getContent();
		if(count($arrLinks)>0){
			//pages
			$tpl->setVariable('GALERYSFAV',FPages::printPagelinkList($arrLinks));
			$showFavoritesTab = true;
		}
		if($showFavoritesTab === true) $tpl->touchBlock('tabfavorites');
		
		/**
		 * UDALOSTI - vlastni, kamaradu, opakovany vsechny, na ty ktery pujdu z tipu a vsechny vlastni
		 */

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}