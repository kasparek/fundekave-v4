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

		} else {

			$userVO = $user->userVO;

		}

		$tpl = FSystem::tpl('users.info.tpl.html');

		$tpl->setVariable('AVATAR',FAvatar::showAvatar($userVO->userId));
		$tpl->setVariable('NAME',$userVO->name);
		$tpl->setVariable('EMAIL',$userVO->email);
		if(!empty($userVO->icq)) $tpl->setVariable('ICQ',$userVO->icq);

		$tpl->setVariable("WWW",$userVO->getXMLVal('personal','www'));
		$tpl->setVariable("MOTTO",$userVO->getXMLVal('personal','motto'));
		$tpl->setVariable("PLACE",$userVO->getXMLVal('personal','place'));
		$tpl->setVariable("FOOD",$userVO->getXMLVal('personal','food'));
		$tpl->setVariable("HOBBY",$userVO->getXMLVal('personal','hobby'));
		$tpl->setVariable("ABOUT",$userVO->getXMLVal('personal','about'));

		$homePageId = $userVO->getXMLVal('personal','HomePageId');
		if(!empty($homePageId)) {
			$tpl->setVariable("HOMEPAGEID",$homePageId);
			$tpl->setVariable("HOMEPAGEUSERNAME",$userVO->name);
		}

		$tpl->setVariable("SKINNAME",$userVO->skinName);
		$tpl->setVariable("DATECREATED",$userVO->dateCreated);
		$tpl->setVariable("DATEUPDATED",$userVO->dateLastVisit);

		$fUvatar = new FUvatar($userVO->name,array('targetFtp'=>ROOT.'tmp/fuvatar/','refresh'=> $userVO->getXMLVal('webcam','interval'),'resolution'=> $userVO->getXMLVal('webcam','resolution')));
		//check if has any image from webcam
		if($fUvatar->hasData()) {
			$tpl->setVariable("WEBCAM",$fUvatar->getSwf());
		}


		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}