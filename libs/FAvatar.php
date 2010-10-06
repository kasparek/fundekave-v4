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
			if(!isset($userVO)) $userVO = new UserVO($userId,true);
			if(!empty($userVO->avatar)) $avatar = strtolower($userVO->name).'/profile/'.$userVO->avatar; //'default/profile/'.$userId.'.jpg';
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
	static function showAvatar($userId=-1){
			
		$avatarUserId = $userId;
		if( $avatarUserId == -1) {
			$user = FUser::getInstance();
			$avatarUserId = $user->userVO->userId;
		}
		$cacheId = $avatarUserId;
		$cacheGrp = 'avatar';
		
		$cache = FCache::getInstance('l',0);
		$ret = $cache->getData($cacheId,$cacheGrp);
		if($ret!==false) return $ret;

		$cache = FCache::getInstance('f',0);
		$ret = $cache->getData($cacheId,$cacheGrp);
		if(false !== $ret) return $ret;

		//set cache
		if(!isset($user)) $user = FUser::getInstance();
		$tpl = FSystem::tpl(FLang::$TPL_USER_AVATAR);

		if($userId == -1 ) $avatarUserName = $user->userVO->name;
		elseif($userId > 0) $avatarUserName = FUser::getgidname($avatarUserId);
		else $avatarUserName = '';

		$tpl->setVariable('USERNAME',$avatarUserName);
		$tpl->setVariable('AVATARURL',FAvatar::getAvatarUrl(($userId==-1)?(-1):($avatarUserId)));
		$tpl->setVariable('AVATARLINK',FSystem::getUri('who='.$userId,'finfo'));
			
		$ret = $tpl->get();
		$cache->setData($ret, $cacheId, $cacheGrp);
		
		$cache = FCache::getInstance('l',0);
		$cache->setData($ret, $cacheId, $cacheGrp);

		return $ret;
	}

	static function profileBasePath() {
		$user = FUser::getInstance();
		return FConf::get('galery','sourceServerBase') . strtolower($user->userVO->name) . '/profile';
	}

	static function profileBaseUrl($dir=null) {
		$user = FUser::getInstance();
		return FConf::get('galery','sourceUrlBase') . strtolower($user->userVO->name) . '/profile';
	}

}