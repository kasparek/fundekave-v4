<?php
class rh_login {
	static function show() {
		$user = FUser::getInstance();
		if($user->idkontrol === true) {
			$q = "select count(1) from sys_users_logged where subdate(NOW(),interval ".USERVIEWONLINE." minute)<dateUpdated";
			$tpl = FSystem::tpl(FLang::$TPL_SIDEBAR_USER_LOGGED);
			$tpl->setVariable('AVATAR',FAvatar::showAvatar(-1,array('noTooltip'=>1)));
			$tpl->setVariable('NAME',$user->userVO->name);
			$tpl->setVariable('ONLINE',FDBTool::getOne($q,'uOnC','default','s',60));
			$recentEvent = $user->userVO->getDiaryCnt();
			if( $recentEvent > 0 ) $tpl->setVariable('DIARY',$recentEvent);
			if($user->userVO->hasNewMessages()) {
				$tpl->setVariable('NEWPOST',$user->userVO->newPost);
				$tpl->setVariable('NEWPOSTFROMNAME',$user->userVO->newPostFrom);
			}
			return $tpl->get();
			
		} else {
			$tpl = FSystem::tpl(FLang::$TPL_SIDEBAR_USER_LOGIN);
			$tpl->setVariable('FORMACTION',FSystem::getUri());
			if( REGISTRATION_ENABLED == 1 ) $tpl->touchBlock('reglink');
			return $tpl->get();
			
		}
	}
}