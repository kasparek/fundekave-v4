<?php
class rh_login {
static function show() {
		$user = FUser::getInstance();
		if($user->idkontrol) {
			$tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
			$tpl->loadTemplatefile('sidebar.user.logged.tpl.html');
			$tpl->setVariable('AVATAR',FAvatar::showAvatar(-1,array('noTooltip'=>1)));
			$tpl->setVariable('NAME',$user->userVO->name);
			$tpl->setVariable('ONLINE',FUser::getOnlineUsersCount());
			$recentEvent = $user->userVO->getDiaryCnt();
			if($recentEvent>0) $tpl->setVariable('DIARY',$recentEvent);
			if($user->userVO->hasNewMessages()) {
				$tpl->setVariable('NEWPOST',$user->userVO->newPost);
				$tpl->setVariable('NEWPOSTFROMNAME',$user->userVO->newPostFrom);
			}
		} else {
			$tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
			$tpl->loadTemplatefile('sidebar.user.login.tpl.html');
			$tpl->setVariable('FORMACTION',$user->getUri());
			if(REGISTRATION_ENABLED == 1) $tpl->touchBlock('reglink');
		}
		return $tpl->get();
	}
}