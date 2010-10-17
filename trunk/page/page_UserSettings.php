<?php
include_once('iPage.php');
class page_UserSettings implements iPage {
	static function process($data) {
		$action = '';
		if(isset($data['action'])) $action = $data['action'];

		if(strpos($action,'del-')!==false) {
			$foto = str_replace('del-','',$action);
			$action = 'del';
		}
		if(strpos($action,'avatar-')!==false) {
			$foto = str_replace('avatar-','',$action);
			$action = 'avatar';
		}

		if(isset($data['save'])) $action='save';
		if(isset($data['del'])) $action='del';
		$user = FUser::getInstance();
		$userVO = & $user->userVO;
		switch($action) {
			case 'avatar':
				$cache = FCache::getInstance('d');
				$fileList = $cache->getData($userVO->userId,'profileFiles');
				if($fileList===false) {
					$ffile = new FFile(FConf::get("galery","ftpServer"));
					$fileList=$ffile->fileList(FAvatar::profileBasePath());
					$cache->setData($fileList);
				}
				if(empty($fileList)) return;
				while($file = array_pop($fileList)) {
					if(FSystem::safetext($file)==$foto) $avatarFile=$file;
				}
				if(empty($avatarFile)) return;
				
				$userVO->avatar=$avatarFile;
				$userVO->save();
				FCommand::run(AVATAR_UPDATED,$userVO->userId);
				FAjax::addResponse('avatarBox','$html',FAvatar::showAvatar());
				FAjax::addResponse('call','msg','ok,'.FLang::$MESSAGE_SUCCESS_SAVED);
				break;
			case 'del':
				$cache = FCache::getInstance('d');
				$fileList = $cache->getData($userVO->userId,'profileFiles');
				if($fileList===false) {
					$ffile = new FFile(FConf::get("galery","ftpServer"));
					$fileList=$ffile->fileList(FAvatar::profileBasePath());
					$cache->setData($fileList);
				}
				if(empty($fileList)) return;
				while($file = array_pop($fileList)) {
					if(FSystem::safetext($file)==$foto) $delFile=$file;
				}
				if(empty($delFile)) return;
				unlink(FAvatar::profileBasePath().'/'.$delFile);
				if($delFile==$userVO->avatar) {
					$userVO->avatar='';
					$userVO->save();
				}
				FCommand::run(AVATAR_UPDATED,$userVO->userId);
				if(!$data['__ajaxResponse']) FHTTP::redirect(FSystem::getUri());
				else {
					FAjax::addResponse('call','msg','ok,'.FLang::$LABEL_DELETED_OK);
					FAjax::addResponse('call','remove','foto-'.$foto);
					FAjax::addResponse('avatarBox','$html',FAvatar::showAvatar());	
				}
				break;
			case 'save':
				$userVO = & $user->userVO;
				//--setxml elements
				$userVO->icq = str_replace("-","",FSystem::textins($data['infoicq'],array('plainText'=>1)));
				$userVO->email = FSystem::textins($data['infoemajl'],array('plainText'=>1));
				$userVO->setXMLVal('settings','bookedorder', $data['bookedorder']*1);
				$userVO->setXMLVal('personal','www',FSystem::textins($data['infowww'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','motto',FSystem::textins($data['infomotto'],array('plainText'=>1)));
				$userVO->setXMLVal('personal','about',FSystem::textins($data['infoabout']));
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
					$ffile = new FFile(FConf::get("galery","ftpServer"));
					$x=1;
					$ext = FFile::fileExt($avatarFile['name']);
					do {
						$avatarFile['name'] = strtolower($userVO->name.'.'.$userVO->userId.'.'.sprintf("%03d",$x).'.'.$ext);
						$x++;
					} while(file_exists(FAvatar::profileBasePath().$avatarFile['name']));
					$ffile->makeDir(FAvatar::profileBasePath());
					if($ffile->upload($avatarFile, FAvatar::profileBasePath(),5000000)) {
						$userVO->avatar = $avatarFile['name'];
						FCommand::run(AVATAR_UPDATED,$userVO->userId);
					}
				}
				if(!FError::is()) FAjax::addResponse('call','msg','ok,'.FLang::$MESSAGE_SUCCESS_SAVED);
				$userVO->save();
				if(!$data['__ajaxResponse']) FHTTP::redirect(FSystem::getUri());
		}
	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$userVO = $user->userVO;

		$tpl = FSystem::tpl('users.personal.html');
		$tpl->setVariable("FORMACTION",FSystem::getUri('m=user-settings&u='.$userVO->userId));
		$tpl->setVariable("USERNAME",$userVO->name);
		$tpl->setVariable("USERICQ",$userVO->icq);
		$tpl->setVariable("USEREMAIL",$userVO->email);
		$tpl->setVariable("USERWWW",$userVO->getXMLVal('personal','www'));
		$tpl->setVariable("USERMOTTO",$userVO->getXMLVal('personal','motto'));
		$tpl->setVariable("USERABOUT",FSystem::textToTextarea($userVO->getXMLVal('personal','about')));

		if($userVO->getXMLVal('settings','bookedorder') == 1) $tpl->touchBlock('bookedorder');

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
				$tpl->setVariable("THUMBURL",FConf::get('galery','targetUrlBase').FConf::get('galery','horiz_thumbCut').'/'.strtolower($userVO->name).'/profile/'.$file);
				$tpl->setVariable('USEAVATARIMGID','-'.FSystem::safetext($file));
				$tpl->setVariable('IMGID','-'.FSystem::safetext($file));
				$tpl->parse("foto");
			}
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}