<?php
class rh_logged_list {
	static function show(){
		$ret = '';
		$user = FUser::getInstance();
		
		$q = "SELECT l.userId, SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateUpdated)) as casklik, l.location, p.name, p.nameshort 
		FROM sys_users_logged as l 
		join sys_users_friends as f ON ((f.userIdFriend=l.userId AND f.userIdFriend!='".$user->userVO->userId."' and f.userId='".$user->userVO->userId."') or (f.userId=l.userId AND f.userId!='".$user->userVO->userId."' and f.userIdFriend='".$user->userVO->userId."'))
		join sys_pages as p on p.pageId=l.location  
		WHERE subdate(NOW(),interval ".USERVIEWONLINE." minute)<l.dateUpdated 
		GROUP BY l.userId 
		ORDER BY casklik";
		
		if (false !== ($arrpra = FDBTool::getAll($q))) {
			$tpl = FSystem::tpl(FLang::$TPL_SIDEBAR_USERS);
			foreach ($arrpra as $pra){
				$tpl->setCurrentBlock('user');
				$tpl->setVariable('AVATAR',FAvatar::showAvatar($pra[0]));
				$tpl->setVariable('USERLINK',FSystem::getUri('who='.$pra[0],'finfo'));
				$tpl->setVariable('USERNAME',FUser::getgidname($pra[0]));
				$tpl->setVariable('USERLOCATIONLINK',FSystem::getUri('',$pra[2]));
				$tpl->setVariable('USERLOCATIONLONG',$pra[3]);
				$tpl->setVariable('USERLOCATIONSHORT',$pra[4]);
				$tpl->setVariable('USERACTIVE',substr($pra[1],3,5));
				$tpl->parseCurrentBlock();
			}
			$ret = $tpl->get();

		}
		return $ret;
	}
}