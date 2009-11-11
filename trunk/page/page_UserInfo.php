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

		$dir = ROOT.ROOT_WEB.WEB_REL_AVATAR . $user->userVO->name;
		$arr = FFile::fileList($dir,'jpg');
		if(!empty($arr)) {
			$tpl->touchBlock('tabfoto');
			sort($arr);
			$arr = array_reverse($arr);
			$ret = '';
			foreach($arr as $img) {
				$tpl->setVariable("IMGURL",WEB_REL_AVATAR.$user->userVO->name.'/'.$img);
				$tpl->parse('foto');
			}
		}

		$fUvatar = new FUvatar($userVO->name,array('targetFtp'=>ROOT.'tmp/fuvatar/','refresh'=> $userVO->getXMLVal('webcam','interval'),'resolution'=> $userVO->getXMLVal('webcam','resolution')));
		//check if has any image from webcam
		if($fUvatar->hasData()) {
			$tpl->setVariable("WEBCAM",$fUvatar->getSwf());
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}