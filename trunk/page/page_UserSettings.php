<?php
include_once('iPage.php');
class page_UserSettings implements iPage {

	static function process() {
		
		if(isset($_POST['nav'])) {
			$user = FUser::getInstance();
			if($_POST['nav']=='infosave') {
				$userVO = & $user->userVO;
				//--setxml elements
				$userVO->icq = str_replace("-","",FSystem::textins($_POST['infoicq'],array('plainText'=>1)));
				$userVO->email = FSystem::textins($_POST['infoemajl'],array('plainText'=>1));

				if($_POST['skin'] > 0) $userVO->skin = $_POST['skin'] * 1;
				$userVO->setXMLVal('settings','bookedorder', $_POST['bookedorder']*1);
				$userVO->setXMLVal('personal','www',FSystem::textins($_POST['infowww'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','place',FSystem::textins($_POST['infomisto'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','food',FSystem::textins($_POST['infojidlo'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','hobby',FSystem::textins($_POST['infohoby'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','motto',FSystem::textins($_POST['infomotto'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','about',FSystem::textins($_POST['infoabout']));
				if(!empty($_POST['homepageid'])) {
					$homePageId = FSystem::textins($_POST['homepageid'],array('plainText'=>1));
					if(FPages::pageOwner($homePageId) == $userVO->userId) $userVO->setXMLVal('personal','HomePageId',$homePageId);
				}
				//---webcam
				$userVO->setXMLVal('webcam','public',(int) $_POST['campublic']);
				if(!empty($_POST['camchosen'])) {
					$chosenUsernames = explode(',',$_POST['camchosen']);
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
				$userVO->setXMLVal('webcam','avatar',(int) $_POST['camavatar']);
				$userVO->setXMLVal('webcam','resolution',(int) $_POST['camresolution']);

				$interval = (int) $_POST['caminterval'];
				if($interval<2) $interval = 2;
				if($interval>100) $interval = 100;
				$userVO->setXMLVal('webcam','interval',$interval);

				$quality = (int) $_POST['camquality'];
				if($quality<0) $quality = 0;
				if($quality>100) $quality = 100;
				$userVO->setXMLVal('webcam','quality',$quality);

				$userVO->setXMLVal('webcam','motion',(int) $_POST['cammotion']);

				$userVO->zbanner = (($_POST["zbanner"]=='1')?(1):(0));
				$userVO->zforumico = (($_POST["zaudico"]=='1')?(1):(0));
				$userVO->zavatar = (($_POST["zidico"]=='1')?(1):(0));
				$userVO->zgaltype = (($_POST["galtype"]=='1')?(1):(0));

				//password
				$pass1 = FSystem::textins($_POST["pwdreg1"],array('plainText'=>1));
				$pass2 = FSystem::textins($_POST["pwdreg2"],array('plainText'=>1));
				if($pass1!=''){
					if(strlen($pass1)<3) FError::addError(FLang::$ERROR_REGISTER_PASSWORDTOSHORT);
					if($pass1!=$pass2) FError::addError(FLang::$ERROR_REGISTER_PASSWORDDONTMATCH);
					if (!FError::isError()){
						$userVO->passwordNew = md5(trim($_POST["pwdreg1"]));
						FError::addError(FLang::$MESSAGE_PASSWORD_SET);
					}
				}

				//avatar
				if ($_FILES["idfoto"]["error"] == 0){
					$konc = Explode(".",$_FILES["idfoto"]["name"]);
					$_FILES["idfoto"]['name'] = FSystem::safeText($userVO->name).".".$userVO->userId.".".$konc[(count($konc)-1)];
					if($up = FSystem::upload($_FILES["idfoto"],WEB_REL_AVATAR,20000)) {
						//---resize and crop if needed
						list($avatarWidth,$avatarHeight,$type) = getimagesize(WEB_REL_AVATAR.$up['name']);
						if($avatarWidth!=AVATAR_WIDTH_PX || $avatarHeight!=AVATAR_HEIGHT_PX) {
							if($type!=2) $up['name'] = str_replace($konc[(count($konc)-1)],'jpg',$up['name']);
							//---RESIZE
							$resizeParams = array('quality'=>80,'crop'=>1,'width'=>AVATAR_WIDTH_PX,'height'=>AVATAR_HEIGHT_PX);
							$iProc = new FImgProcess(WEB_REL_AVATAR.$_FILES["idfoto"]['name'],WEB_REL_AVATAR.$up['name'],$resizeParams);

						}
						$userVO->avatar = $up['name'];
					}
				}

				$userVO->saveVO();
				FHTTP::redirect(FUser::getUri());
			}
		}
	}

	static function build() {



		$user = FUser::getInstance();
		$userVO = $user->userVO;

		$tpl = new FTemplateIT('users.personal.html');

		$tpl->setVariable("FORMACTION",FUser::getUri());
		$tpl->setVariable("USERNAME",$user->name);
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
		if($userVO->zgaltype == 1) $tpl->touchBlock('galtype');
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