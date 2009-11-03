<?php
class FAvatar {
	/**
	 * get avatar url
	 * @param $userId
	 * @return string - avatar pic url
	 */
	static function getAvatarUrl($userId=-1){
		$picname = WEB_REL_AVATAR . AVATAR_DEFAULT;
		if($userId==-1) {
			$user = FUser::getInstance();
			$picname = WEB_REL_AVATAR . $user->userVO->avatar; //---myself
		} elseif($userId > 0) {
			$cache = FCache::getInstance('l');
			if( ($userAvatar = $cache->getData($userId,'UavaUrl')) === false ) {
				$userAvatar = WEB_REL_AVATAR . FDBTool::getOne("SELECT avatar FROM sys_users WHERE userId = '".$userId."'");
				if(!empty($userAvatar)) {
					if(file_exists($userAvatar) && !is_dir($userAvatar)) {
						$picname = $userAvatar;
					}
				}
				$cache->setData($userAvatar ,$userId,'UavaUrl');
			} else {
				$picname = $userAvatar;
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
		$noTooltip = (isset($paramsArr['noTooltip']))?(true):(false);
		$withTooltip = (isset($paramsArr['withTooltip']))?(true):(false);
			
		$avatarUserId = $userId;
		if($avatarUserId==-1) {
			$user = FUser::getInstance();
			$avatarUserId = $user->userVO->userId;
		}

		$cache = FCache::getInstance('l');

		if(!$ret = $cache->getData($avatarUserId,'Uavatar')) {
			if(!isset($user)) $user = FUser::getInstance();
				
			$tpl = FSystem::tpl(FLang::$TPL_USER_AVATAR);

			if($userId==-1 ) $avatarUserName = $user->userVO->name;
			elseif($userId > 0) $avatarUserName = FUser::getgidname($avatarUserId);
			else $avatarUserName = '';

			if($showName) $tpl->setVariable('USERNAME',$avatarUserName);
			if($user->userVO->zavatar==1) {
				$tpl->setVariable('AVATARURL',FAvatar::getAvatarUrl(($userId==-1)?(-1):($avatarUserId)));
				$tpl->setVariable('AVATARUSERNAME',$avatarUserName);
				if(isset($class)) $tpl->setVariable('AVATARCLASS',$class);
			}

			if($user->idkontrol && $avatarUserId>0) {
				$avatarUrl = FSystem::getUri('who='.$avatarUserId,'finfo','');
				if($showName) {
					$tpl->setVariable('NAMEURL',$avatarUserName);
					if($noTooltip === false) $tpl->setVariable('NAMECLASS','supernote-hover-avatar'.$avatarUserId);
					$tpl->touchBlock('linknameend');
				}
				if($user->userVO->zavatar) {
					$tpl->setVariable('AVATARLINK',$avatarUrl);
					if($noTooltip === false) $tpl->setVariable('AVATARLINKCLASS','supernote-hover-avatar'.$avatarUserId);
					$tpl->touchBlock('linkavatarend');
				}
			}
			$tpl->parse('useravatar');
			$ret = $tpl->get('useravatar');
			$cache->setData($ret,$avatarUserId,'Uavatar');
		}

		$tooltip = '';
		if($noTooltip === false) {
			if(FUser::logon() > 0 && $avatarUserId > 0) {
				if(($tooltip = $cache->getData($avatarUserId, 'UavatarTip')) === false) {
					if(!isset($user)) $user = FUser::getInstance();
						
					$avatarUserName = ($userId==-1)?($user->userVO->name):(FUser::getgidname($userId));
					if(!isset($tpl)) $tpl = FSystem::tpl(FLang::$TPL_USER_AVATAR);

					$tpl->setVariable('TOOLTIPID','supernote-note-avatar'.$avatarUserId);
					$tpl->setVariable('TIPCLASS','snp-mouseoffset notemenu');
					$tpl->setVariable('TIPUSERNAME',$avatarUserName);

					$arrLinks = array(
					array('url'=>FSystem::getUri('who='.$avatarUserId,'finfo',''),'text'=>FLang::$LABEL_INFO),
					array('url'=>FSystem::getUri('who='.$avatarUserId,'fpost',''),'text'=>FLang::$LABEL_POST),
					);
					if($avatarUserId != $user->userVO->userId) {
						$arrLinks[] = array('url'=>FSystem::getUri('m=user-switchFriend&d=user:'.$avatarUserId)
						,'id'=>'avbook'.$avatarUserId
						,'class'=>'fajaxa'
						,'text'=>(($user->userVO->isFriend($avatarUserId))?(FLang::$LABEL_FRIEND_REMOVE):(FLang::$LABEL_FRIEND_ADD)));
					}

					foreach ($arrLinks as $tip) {
						$tpl->setCurrentBlock('tip');
						$tpl->setVariable('TIPROWURL',$tip['url']);
						if(isset($tip['id'])) $tpl->setVariable('TIPROWID',$tip['id']);
						if(isset($tip['class'])) $tpl->setVariable('TIPROWCLASS',$tip['class']);
						$tpl->setVariable('TIPROWTEXT',$tip['text']);
						$tpl->parseCurrentBlock();
					}
					$tpl->parse('tooltip');
					$tooltip = $tpl->get('tooltip');
					$cache->setData($tooltip , $avatarUserId, 'UavatarTip' );
				}
			}
		}
		if($withTooltip === true) {
			$ret .= $tooltip;
		}
		return $ret;
	}

	static function createName($fileOrig) {
		$user = FUser::getInstance();
		return FSystem::safeText($user->userVO->name).".".$user->userVO->userId.".".date('U').".".FFile::fileExt($fileOrig);
	}
	static function processAvatar($avatarName) {
		//---resize and crop if needed
		list($avatarWidth,$avatarHeight,$type) = getimagesize(WEB_REL_AVATAR . $avatarName);
		if($avatarWidth != AVATAR_WIDTH_PX || $avatarHeight != AVATAR_HEIGHT_PX) {
			if($type != 2) $avatarName = str_replace(FSystem::fileExt($avatarName),'jpg',$avatarName);
			//---RESIZE
			$resizeParams = array('quality'=>80,'crop'=>1,'width'=>AVATAR_WIDTH_PX,'height'=>AVATAR_HEIGHT_PX);
			$iProc = new FImgProcess(WEB_REL_AVATAR.$avatarName,WEB_REL_AVATAR.$avatarName,$resizeParams);
		}
		return $avatarName;
	}
}