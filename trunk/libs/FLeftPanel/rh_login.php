<?php
class rh_login {
	static function show() {
		$user = FUser::getInstance();
		if($user->idkontrol === true) {
		
			$tpl = FSystem::tpl(FLang::$TPL_SIDEBAR_USER_LOGGED);
			$tpl->setVariable('AVATAR',FAvatar::showAvatar(-1));
			$tpl->setVariable('NAME',$user->userVO->name);

			$q = "select userId from sys_users_logged where subdate(NOW(),interval ".USERVIEWONLINE." minute) < dateUpdated";
			$arr = FDBTool::getCol($q);
			$online = 0;
			if(!empty($arr)) {
				foreach($arr as $userId) {
					if($user->userVO->isFriend($userId)) $online++;
				}
			}
			
			$tpl->setVariable('ONLINE',$online);
			
			$q = "select count(1) from sys_pages_items where typeId='request' and addon='".$user->userVO->userId."'";
			$reqNum = FDBTool::getOne($q,'friendrequest','default','s',120);
			if($reqNum>0)$tpl->setVariable('REQUESTSNUM',$reqNum);
			/*
			$recentEvent = $user->userVO->getDiaryCnt();
			if( $recentEvent > 0 ) $tpl->setVariable('DIARY',$recentEvent);
			*/
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