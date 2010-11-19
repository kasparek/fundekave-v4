<?php
class Sidebar_login {
	static function show() {
		$user = FUser::getInstance();
		if($user->idkontrol === true) {

			$tpl = FSystem::tpl(FLang::$TPL_SIDEBAR_USER_LOGGED);
			$tpl->setVariable('AVATAR',FAvatar::showAvatar(-1));
			$tpl->setVariable('NAME',$user->userVO->name);

				
			$q = "select count(1) from sys_pages_items where typeId='request' and addon='".$user->userVO->userId."'";
			$reqNum = FDBTool::getOne($q,'friendrequest','default','s',120);
			if($reqNum>0)$tpl->setVariable('REQUESTSNUM',$reqNum);

			$q = "SELECT l.userId, SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateUpdated)) as casklik, l.location, p.name, p.typeId
			FROM sys_users_logged as l 
			join sys_users_friends as f ON ((f.userIdFriend=l.userId AND f.userIdFriend!='".$user->userVO->userId."' and f.userId='".$user->userVO->userId."') or (f.userId=l.userId AND f.userId!='".$user->userVO->userId."' and f.userIdFriend='".$user->userVO->userId."'))
			join sys_pages as p on p.pageId=l.location  
			WHERE subdate(NOW(),interval ".USERVIEWONLINE." second)<l.dateUpdated 
			GROUP BY l.userId 
			ORDER BY casklik";
				
			if (false !== ($arrpra = FDBTool::getAll($q))) {
				if(!empty($arrpra)) {
					$tpl->setVariable('NUMFRIENDSONLINE',count($arrpra));
					foreach ($arrpra as $pra){
						$tpl->setVariable('FRIENDAVATAR',FAvatar::showAvatar($pra[0]));
						$tpl->setVariable('USERLINK',FSystem::getUri('who='.$pra[0].'#tabs-profil','finfo'));
						$tpl->setVariable('MSGLINK',FSystem::getUri('who='.$pra[0],'fpost'));
						$tpl->setVariable('USERNAME',FUser::getgidname($pra[0]));
						if($pra[4]!='top') {
							$tpl->setVariable('USERLOCATIONLINK',FSystem::getUri('',$pra[2]));
							$tpl->setVariable('USERLOCATIONLONG',$pra[3]);
							$tpl->setVariable('USERLOCATIONSHORT',FLang::$TYPEID[$pra[4]]);
						}
						$tpl->setVariable('USERACTIVE',substr($pra[1],3,5));
						$tpl->parse('user');
					}
				}
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