<?php
include_once('iPage.php');
class page_UserSettings implements iPage {

	static function process($data) {

		$action = '';
		if(isset($data['action'])) $action = $data['action'];

		if(isset($action)) {
			/*
			 * select new profile image
			 */
			if(strpos($action,'avatar')!==false) {
				$user = FUser::getInstance();

				$md5 = str_replace('avatar','',$data['action']);

				$dir = FAvatar::profileBasePath();
				$arr = FFile::fileList($dir,'jpg');

				foreach($arr as $file) {
					if(md5($file) == $md5) {
						$newSource = $file;
						break;
					}
				}

				$user->userVO->avatar = FAvatar::profileBaseUrl() . '/' . $newSource;
				$user->userVO->save();

				$cache = FCache::getInstance('f',0);
				$cache->invalidateData('avatar',$user->userVO->userId);
				
				FAjax::addResponse('avatarBox', '$html', FAvatar::showAvatar($user->userVO->userId));
				FAjax::addResponse('call','msg','ok,'.FLANG::$MSG_AVATAR_SET);
			}

			if(strpos($action,'del')!==false) {
				$user = FUser::getInstance();

				$md5 = str_replace('del','',$data['action']);

				$dir = FAvatar::profileBasePath();
				$arr = FFile::fileList($dir,'jpg');
				foreach($arr as $file) {
					if(md5($file) == $md5) {
						@unlink($dir.'/'.$file);
						break;
					}
				}
					
				FAjax::addResponse('call','remove','personalfoto'.$md5);
				FAjax::addResponse('call','msg','ok,File deleted');

				FAjax::addResponse('folderSize', '$html', round(FFile::folderSize($dir)/1024).'kB');
			}
		}

		if($action === 'sava') {
			$user = FUser::getInstance();
				
			$userVO = & $user->userVO;
			//--setxml elements
			$userVO->icq = str_replace("-","",FSystem::textins($data['infoicq'],array('plainText'=>1)));
			$userVO->email = FSystem::textins($data['infoemajl'],array('plainText'=>1));

			if(isset($data['skin'])) if($data['skin'] > 0) $userVO->skin = $data['skin'] * 1;
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

			//password
			$pass1 = FSystem::textins($data["pwdreg1"],array('plainText'=>1));
			$pass2 = FSystem::textins($data["pwdreg2"],array('plainText'=>1));
			if(!empty($pass1) && !empty($pass2)){
				if(strlen($pass1)<3) FError::add(FLang::$ERROR_REGISTER_PASSWORDTOSHORT);
				if($pass1!=$pass2) FError::add(FLang::$ERROR_REGISTER_PASSWORDDONTMATCH);
				if (!FError::is()){
					$userVO->passwordNew = md5(trim($data["pwdreg1"]));
					FError::add(FLang::$MESSAGE_PASSWORD_SET);
				}
			}

			//avatar
			if(isset($data['__files']["idfoto"])) {
				$avatarFile = $data['__files']["idfoto"];
				if ($avatarFile["error"] == 0){
					$avatarFile['name'] = FFile::safeFilename($avatarFile["name"]);
					if(FSystem::upload($avatarFile, FAvatar::avatarBasePath(), 20000)) {
						$userVO->avatar = FAvatar::avatarBaseUrl().'/'.$avatarFile['name'];
					}
				}
			}
			$userVO->save();

			if($data['__ajaxResponse']) {
				if(!FError::is()) FAjax::addResponse('call','msg','ok,Data saved');
			} else {
				FHTTP::redirect(FSystem::getUri());
			}
				
		}
	}

	static function build($data=array()) {
    $isWebcamEnabled = false;

		$user = FUser::getInstance();
		$userVO = $user->userVO;

		$tpl = FSystem::tpl('users.personal.html');

		$tpl->setVariable("FORMACTION",FSystem::getUri('m=user-settings&u='.$userVO->userId));
		$tpl->setVariable("USERNAME",$userVO->name);
		
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
				
		if($userVO->getXMLVal('settings','bookedorder') == 1) $tpl->touchBlock('bookedorder');

    if($isWebcamEnabled===true) {
    	$tpl->touchBlock('webcamButton');
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
		}

		$dir = FAvatar::profileBasePath();

		$tpl->setVariable('FOLDERSIZE',round(FFile::folderSize($dir)/1024).'kB');
		$tpl->setVariable('FOLDERLIMIT', FConf::get('settings','personal_foto_limit').'kB');

		$arr = FFile::fileList($dir,'jpg');
		sort($arr);
		$arr = array_reverse($arr);
		$ret = '';
		foreach($arr as $img) {
			$tpl->setVariable("IMGURL",FAvatar::profileBaseUrl().'/'.$img);
			$thumbUrlBase = FConf::get('galery','targetUrlBase').'0x'.AVATAR_HEIGHT_PX.'/crop/';
			$tpl->setVariable("THUMBURL",$thumbUrlBase.$user->userVO->name.'/profile/'.$img);
			$tpl->setVariable("IMGID",md5($img));
			$tpl->parse('personalImage');
		}


		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}