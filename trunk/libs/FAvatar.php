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
				$cache->setData($userAvatar ,$userId,'UavaUrl');
			}
			if(!empty($userAvatar)) {
				if(file_exists($userAvatar) && !is_dir($userAvatar)) {
					$picname = $userAvatar;
				}
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
		$user = FUser::getInstance();

		if(isset($paramsArr['class'])) $class = $paramsArr['class'];
		$showName = (isset($paramsArr['showName']))?(true):(false);
		$noTooltip = (isset($paramsArr['noTooltip']))?(true):(false);
		$withTooltip = (isset($paramsArr['withTooltip']))?(true):(false);
	  
	  
	 $avatarUserId = ($userId==-1)?($user->userVO->userId):($userId);

	 $cache = FCache::getInstance('l');

	 if(!$ret = $cache->getData($avatarUserId,'Uavatar')) {
	 	$tpl = new fTemplateIT('user.avatar.tpl.html');

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
	 		$avatarUrl = BASESCRIPTNAME.'?k=finfo&who='.$avatarUserId;
	 		if($showName) {
	 			$tpl->setVariable('NAMEURL',$avatarUserName);
	 			if($noTooltip==false) $tpl->setVariable('NAMECLASS','supernote-hover-avatar'.$avatarUserId);
	 			$tpl->touchBlock('linknameend');
	 		}
	 		if($user->userVO->zavatar) {
	 			$tpl->setVariable('AVATARLINK',$avatarUrl);
	 			if($noTooltip==false) $tpl->setVariable('AVATARLINKCLASS','supernote-hover-avatar'.$avatarUserId);
	 			$tpl->touchBlock('linkavatarend');
	 		}

	 	}
	 	 
	 	$tpl->parse('useravatar');
	 	$ret = $tpl->get('useravatar');
	 		
	 	$cache->setData($ret,$avatarUserId,'Uavatar');
		}
		$tooltip = '';
		if($noTooltip==false
		&& $user->idkontrol==true
		&& $avatarUserId > 0
		&& ($tooltip=$cache->getData($avatarUserId, 'UavatarTip'))===false) {

			$avatarUserName = ($userId==-1)?($user->userVO->name):(FUser::getgidname($userId));
			if(!isset($tpl)) $tpl = new fTemplateIT('user.avatar.tpl.html');
				
			$tpl->setVariable('TOOLTIPID','supernote-note-avatar'.$avatarUserId);
			$tpl->setVariable('TIPCLASS','snp-mouseoffset notemenu');
			$tpl->setVariable('TIPUSERNAME',$avatarUserName);
	   
			$arrLinks = array(
			array('url'=>'?k=finfo&who='.$avatarUserId,'text'=>FLang::$LABEL_INFO),
			array('url'=>'?k=fpost&who='.$avatarUserId,'text'=>FLang::$LABEL_POST),
			);
			if($avatarUserId!=$user->userVO->userId) {
				$arrLinks[] = array('url'=>FUser::getUri('switchUser='.$avatarUserId)
				,'id'=>'avbook'.$avatarUserId
				,'class'=>'switchFriend'
				,'text'=>(($user->userVO->isFriend($avatarUserId))?(FLang::$LABEL_FRIEND_REMOVE):(FLang::$LABEL_FRIEND_ADD)));
			}
//TODO: ,'click'=>"xajax_user_switchFriend('".$avatarUserId."','avbook".$avatarUserId."');return(false);"
				
			foreach ($arrLinks as $tip) {
				$tpl->setCurrentBlock('tip');
				$tpl->setVariable('TIPURL',$tip['url']);
				if(isset($tip['id'])) $tpl->setVariable('TIPID',$tip['id']);
				if(isset($tip['class'])) $tpl->setVariable('TIPCLASS',$tip['class']);
				$tpl->setVariable('TIPTEXT',$tip['text']);
				$tpl->parseCurrentBlock();
			}
			$tpl->parse('tooltip');
			$tooltip = $tpl->get('tooltip');
			$cache->setData($tooltip , $avatarUserId, 'UavatarTip' );
		}
		if($withTooltip === true) {
			$ret .= $tooltip;
		}
		return $ret;
	}
}