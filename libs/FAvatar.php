<?php
class FAvatar {
	/**
	 * get avatar url
	 * @param $userId
	 * @return string - avatar pic url
	 */
	static function getAvatarUrl($userId=-1) {
		$urlBase = FConf::get('galery','targetUrlBase').AVATAR_WIDTH_PX.'x'.AVATAR_HEIGHT_PX.'/crop/';
		$avatar = 'default/'.AVATAR_DEFAULT;
		if($userId==-1) {
			$user = FUser::getInstance();
			$userVO = $user->userVO;
			$userId = $userVO->userId; 
		}
		if($userId > 0) {
				if(!empty($userVO->avatar)) $avatar = $userVO->avatar; //'default/profile/'.$userId.'.jpg';
		}
		return $urlBase.$avatar;
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

	static function profileBasePath() {
		$user = FUser::getInstance();
		return FConf::get('galery','sourceServerBase') . $user->userVO->name . '/profile';
	}

	static function profileBaseUrl($dir=null) {
		$user = FUser::getInstance();
		return FConf::get('galery','sourceUrlBase') . $user->userVO->name . '/profile';
	}

}