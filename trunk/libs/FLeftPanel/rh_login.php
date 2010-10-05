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
			
			if($user->userVO->hasNewMessages()) {
				$tpl->setVariable('NEWPOST',$user->userVO->newPost);
				$tpl->setVariable('NEWPOSTFROMNAME',$user->userVO->newPostFrom);
			} else {
				$tpl->touchBlock('msgHidden');
			}
			
			$q = "SELECT l.userId, SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateUpdated)) as casklik, l.location, p.name, p.nameshort 
			FROM sys_users_logged as l 
			join sys_users_friends as f ON ((f.userIdFriend=l.userId AND f.userIdFriend!='".$user->userVO->userId."' and f.userId='".$user->userVO->userId."') or (f.userId=l.userId AND f.userId!='".$user->userVO->userId."' and f.userIdFriend='".$user->userVO->userId."'))
			join sys_pages as p on p.pageId=l.location  
			WHERE subdate(NOW(),interval ".USERVIEWONLINE." minute)<l.dateUpdated 
			GROUP BY l.userId 
			ORDER BY casklik";
			
			if (false !== ($arrpra = FDBTool::getAll($q))) {
				$tpl->setVariable('NUMFRIENDSONLINE',count($arrpra));
				foreach ($arrpra as $pra){
					$tpl->setVariable('FRIENDAVATAR',FAvatar::showAvatar($pra[0]));
					$tpl->setVariable('USERLINK',FSystem::getUri('who='.$pra[0],'finfo'));
					$tpl->setVariable('USERNAME',FUser::getgidname($pra[0]));
					$tpl->setVariable('USERLOCATIONLINK',FSystem::getUri('',$pra[2]));
					$tpl->setVariable('USERLOCATIONLONG',$pra[3]);
					$tpl->setVariable('USERLOCATIONSHORT',$pra[4]);
					$tpl->setVariable('USERACTIVE',substr($pra[1],3,5));
					$tpl->parse('user');
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