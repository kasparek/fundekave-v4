<?php
include_once('iPage.php');
class page_UserSettings implements iPage {

	static function process($data) {
		
		if(isset($data['nav'])) {
			$user = FUser::getInstance();
			if($data['nav']=='infosave') {
				$userVO = & $user->userVO;
				//--setxml elements
				$userVO->icq = str_replace("-","",FSystem::textins($data['infoicq'],array('plainText'=>1)));
				$userVO->email = FSystem::textins($data['infoemajl'],array('plainText'=>1));

				if($data['skin'] > 0) $userVO->skin = $data['skin'] * 1;
				$userVO->setXMLVal('settings','bookedorder', $data['bookedorder']*1);
				$userVO->setXMLVal('personal','www',FSystem::textins($data['infowww'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','place',FSystem::textins($data['infomisto'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','food',FSystem::textins($data['infojidlo'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','hobby',FSystem::textins($data['infohoby'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','motto',FSystem::textins($data['infomotto'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','about',FSystem::textins($data['infoabout']));
				if(!empty($data['homepageid'])) {
					$homePageId = FSystem::textins($data['homepageid'],array('plainText'=>1));
					if(FPages::pageOwner($homePageId) == $userVO->userId) $userVO->setXMLVal('personal','HomePageId',$homePageId);
				}
				//---webcam
				$userVO->setXMLVal('webcam','public',(int) $data['campublic']);
				if(!empty($data['camchosen'])) {
					$chosenUsernames = explode(',',$data['camchosen']);
					foreach ($chosenUsernames as $username) {
						$username = trim($username);
						$userId = FUser::getUserIdByName($username);
						if($userId > 0) $arrUserIdValidatedArr[$userId] = $userId;
					}
					if(!empty($arrUserIdValidatedArr)) {
						$userListChosen = implode(',',$arrUserIdValidatedArr);
						$userVO->setXMLVal('webcam','chosen',$userListChosen);
					}
				}
				$userVO->setXMLVal('webcam','avatar',(int) $data['camavatar']);
				$userVO->setXMLVal('webcam','resolution',(int) $data['camresolution']);

				$interval = (int) $data['caminterval'];
				if($interval<2) $interval = 2;
				if($interval>100) $interval = 100;
				$userVO->setXMLVal('webcam','interval',$interval);

				$quality = (int) $data['camquality'];
				if($quality<0) $quality = 0;
				if($quality>100) $quality = 100;
				$userVO->setXMLVal('webcam','quality',$quality);

				$userVO->setXMLVal('webcam','motion',(int) $data['cammotion']);

				$userVO->zbanner = (($data["zbanner"]=='1')?(1):(0));
				$userVO->zforumico = (($data["zaudico"]=='1')?(1):(0));
				$userVO->zavatar = (($data["zidico"]=='1')?(1):(0));
				$userVO->zgalerytype = (($data["galtype"]=='1')?(1):(0));

				//password
				$pass1 = FSystem::textins($data["pwdreg1"],array('plainText'=>1));
				$pass2 = FSystem::textins($data["pwdreg2"],array('plainText'=>1));
				if($pass1!=''){
					if(strlen($pass1)<3) FError::addError(FLang::$ERROR_REGISTER_PASSWORDTOSHORT);
					if($pass1!=$pass2) FError::addError(FLang::$ERROR_REGISTER_PASSWORDDONTMATCH);
					if (!FError::isError()){
						$userVO->passwordNew = md5(trim($data["pwdreg1"]));
						FError::addError(FLang::$MESSAGE_PASSWORD_SET);
					}
				}

				//avatar
				$avatarFile = $data['__files']["idfoto"];
				if ($avatarFile["error"] == 0){
					$avatarFile['name'] = FAvatar::createName($avatarFile["name"]);
					if(FSystem::upload($avatarFile, WEB_REL_AVATAR, 20000)) {
						$userVO->avatar = FAvatar::processAvatar($avatarFile['name']);
					}
				}
				$userVO->save();
				FHTTP::redirect(FSystem::getUri());
			}
		}
	}

	static function build() {



		$user = FUser::getInstance();
		$userVO = $user->userVO;

		$tpl = new FTemplateIT('users.personal.html');

		$tpl->setVariable("FORMACTION",FSystem::getUri('m=user-settings&u='.$userVO->userId));
		$tpl->setVariable("USERNAME",$userVO->name);
		$options='';
		$arrOpt = FDBTool::getAll('select skinId,name from sys_skin order by name','skin','categ','s');
		if(!empty($arrOpt)) foreach ($arrOpt as $row) {
			$options.='<option value="'.$row[0].'"'.(($row[0]==$userVO->skin)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
		}
		$tpl->setVariable("SKINOPTIONS",$options);

		/*
		 $options='';
		 $arrOpt = $db->getAll('select pageId,name from sys_pages where userIdOwner="'.$user->gid.'"');
		 if(!empty($arrOpt)) foreach ($arrOpt as $row) {
		 $options.='<option value="'.$row[0].'"'.(($row[0]==(string) $personal->HomePageId)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
		 }
		 $tpl->setVariable("HOMEPAGEOPTIONS",$options);
		 */
		$tpl->setVariable("USERICQ",$userVO->icq);
		$tpl->setVariable("USEREMAIL",$userVO->email);

		$tpl->setVariable("USERWWW",$userVO->getXMLVal('personal','www'));
		$tpl->setVariable("USERMOTTO",$userVO->getXMLVal('personal','motto'));
		$tpl->setVariable("USERMISTO",$userVO->getXMLVal('personal','place'));
		$tpl->setVariable("USERJIDLO",$userVO->getXMLVal('personal','food'));
		$tpl->setVariable("USERHOBBY",$userVO->getXMLVal('personal','hobby'));
		$tpl->setVariable("USERABOUT",FSystem::textToTextarea($userVO->getXMLVal('personal','about')));
		$tpl->addTextareaToolbox('USERABOUTTOOLBOX','userabout');

		if($userVO->zbanner == 1) $tpl->touchBlock('zbanner');
		if($userVO->zforumico == 1) $tpl->touchBlock('zaudico');
		if($userVO->zavatar == 1) $tpl->touchBlock('zidico');
		if($userVO->zgalerytype == 1) $tpl->touchBlock('galtype');
		if($userVO->getXMLVal('settings','bookedorder') == 1) $tpl->touchBlock('bookedorder');

		//webcam
		switch($userVO->getXMLVal('webcam','public')) {
			case 1:
				$tpl->touchBlock('campublicregistered');
				break;
			case 2:
				$tpl->touchBlock('campublicfriends');
				break;
			case 3:
				$tpl->touchBlock('campublicchosen');
				break;
		}
		$arrChosen = explode(',',$userVO->getXMLVal('webcam','chosen'));
		foreach ($arrChosen as $userIdFor) {
			$arrUsernames[] = FUser::getgidname($userIdFor);
		}
		$tpl->setVariable('CAMCHOSEN',implode(',',$arrUsernames));

		if($userVO->getXMLVal('webcam','avatar') == 1) $tpl->touchBlock('camavatar');
		if($userVO->getXMLVal('webcam','resolution') == 1) $tpl->touchBlock('camresolution1');

		$tpl->setVariable('CAMINTERVAL',$userVO->getXMLVal('webcam','interval'));
		$tpl->setVariable('CAMQUALITY',$userVO->getXMLVal('webcam','quality'));

		if($userVO->getXMLVal('webcam','motion') == 0) $tpl->touchBlock('cammotion');


		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}