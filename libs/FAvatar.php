<?php
class FAvatar {
	/**
	 * get avatar url
	 * @param $userId
	 * @return string - avatar pic url
	 */
	static function getAvatarUrl($userId=-1) {
		//TODO:
		//check fimgprocess that croping does not resize if it match
		//return /image/AVATAR_WIDTH_PXxAVATAR_HEIGHT_PX/crop/username/profile/selectedimage
		//return /image/AVATAR_WIDTH_PXxAVATAR_HEIGHT_PX/crop/AVATAR_DEFAULT = default/useravatar.jpg
		$picname = AVATAR_DEFAULT;
		if($userId==-1) {
			$user = FUser::getInstance();
			$picname = FAvatar::avatarBaseUrl() . $user->userVO->avatar; //---myself
		} elseif($userId > 0) {
			$cache = FCache::getInstance('d',0);
			$avatarUrl = $cache->getData($userId,'avatar_url');
			if( $avatarUrl === false ) {
				$userAvatar = FDBTool::getOne("SELECT avatar FROM sys_users WHERE userId = '".$userId."'");
				if(!empty($userAvatar)) {
					if(file_exists( FAvatar::avatarBasePath().$userAvatar ) && !is_dir( FAvatar::avatarBasePath().$userAvatar )) {
						$picname = FAvatar::avatarBaseUrl().$userAvatar;
					}
				}
				$cache->setData($picname ,$userId,'avatar_url');
			} else {
				$picname = $avatarUrl;
			}
		}
		return($picname);
	}

	/**
	 * creates avatar image holder with image
	 *
	 * @param int $userId
	 * @param array $paramsArr
	 * @return html formated avatar
	 */
	static function showAvatar($userId=-1,$paramsArr = array()){
		if(isset($paramsArr['class'])) $class = $paramsArr['class'];
		$showName = (isset($paramsArr['showName']))?(true):(false);
			
		$avatarUserId = $userId;
		if( $avatarUserId == -1) {
			$user = FUser::getInstance();
			$avatarUserId = $user->userVO->userId;
		}

		$cache = FCache::getInstance('f',0);
		$cacheId = 'opt'.(($showName===true)?('-1'):('-0'));
		$cacheGrp = 'avatar_'.$avatarUserId;
		$ret = $cache->getData( $cacheId,$cacheGrp);
		if(false === $ret) {
			if(!isset($user)) $user = FUser::getInstance();
			$tpl = FSystem::tpl(FLang::$TPL_USER_AVATAR);

			if($userId == -1 ) $avatarUserName = $user->userVO->name;
			elseif($userId > 0) $avatarUserName = FUser::getgidname($avatarUserId);
			else $avatarUserName = '';

			if($showName) $tpl->setVariable('USERNAME',$avatarUserName);
			if($user->userVO->zavatar == 1) {
				$tpl->setVariable('AVATARURL',FAvatar::getAvatarUrl(($userId==-1)?(-1):($avatarUserId)));
				$tpl->setVariable('AVATARUSERNAME',$avatarUserName);
				if(isset($class)) $tpl->setVariable('AVATARCLASS',$class);
			}

			if($user->idkontrol===true && $avatarUserId > 0) {
				$avatarUrl = FSystem::getUri('who='.$avatarUserId,'finfo','');
				if( $showName ) {
					$tpl->setVariable('NAMEURL',$avatarUserName);
					$tpl->touchBlock('linknameend');
				}
				if( $user->userVO->zavatar ) {
					$tpl->setVariable('AVATARLINK',$avatarUrl);
					$tpl->touchBlock('linkavatarend');
				}
			}
			$tpl->parse('useravatar');
			$ret = $tpl->get('useravatar');
			$cache->setData($ret, $cacheId, $cacheGrp);
		}

		return $ret;
	}

	static function createName($fileOrig) {
		$user = FUser::getInstance();
		return FSystem::safeText($user->userVO->name).".".$user->userVO->userId.".".date('U').".".FFile::fileExt($fileOrig);
	}
	
	static function profileBasePath() {
		$user = FUser::getInstance();
		return FConf::get('galery','sourceServerBase') . $user->userVO->name . '/profile';
	}
	
	static function profileBaseUrl() {
		$user = FUser::getInstance();
		return FConf::get('galery','sourceUrlBase') . $user->userVO->name . '/profile';
	}
	
	static function avatarBasePath() {
		$user = FUser::getInstance();
		return FConf::get('galery','sourceServerBase') . $user->userVO->name . '/avatar';
	}
	
	static function avatarBaseUrl() {
		$user = FUser::getInstance();
		return FConf::get('galery','sourceUrlBase') . $user->userVO->name . '/avatar';
	}
	
	static function processAvatar($avatarName) {
		//---resize and crop if needed
		list($avatarWidth,$avatarHeight,$type) = getimagesize( $avatarName );
		
		$newName = FAvatar::createName($avatarName);
		$targetName = FAvatar::avatarBasePath().$newName;
		
		if($avatarWidth != AVATAR_WIDTH_PX || $avatarHeight != AVATAR_HEIGHT_PX) {
			if($type != 2) $avatarName = str_replace(FSystem::fileExt($avatarName),'jpg',$avatarName);
			//---RESIZE
			$resizeParams = array('quality'=>80,'crop'=>1,'width'=>AVATAR_WIDTH_PX,'height'=>AVATAR_HEIGHT_PX);
			FImgProcess::process($avatarName,$targetName,$resizeParams);
		} else {
		  copy($avatarName, $targetName);
      chmod($targetName,0777); 
		}
		return $newName;
	}
}