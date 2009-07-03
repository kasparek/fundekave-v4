<?php
class rh_logged_list {
static function show(){
		$cache = FCache::getInstance('l',10);
		if(false === ($ret = $cache->getData('loggedlist'))) {
			$ret = '';
			$user = FUser::getInstance();
			if (false !== ($arrpra = FDBTool::getAll("SELECT f.userIdFriend,SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateUpdated)) as casklik FROM sys_users_logged as l INNER JOIN sys_users_friends as f ON f.userIdFriend=l.userId  WHERE subdate(NOW(),interval ".USERVIEWONLINE." minute)<l.dateUpdated AND f.userId=".$user->userVO->userId." AND f.userIdFriend!='".$user->userVO->userId."' GROUP BY f.userIdFriend ORDER BY casklik"))) {
				$tpl = new FTemplateIT('sidebar.users.tpl.html');
				foreach ($arrpra as $pra){
					$kde = FUser::getLocation($pra[0]);
					$tpl->setCurrentBlock('user');
					$tpl->setVariable('AVATAR',FAvatar::showAvatar($pra[0]));
					$tpl->setVariable('USERLINK',FUser::getUri('who='.$pra[0],'finfo'));
					$tpl->setVariable('USERNAME',FUser::getgidname($pra[0]));
					$tpl->setVariable('USERLOCATIONLINK',FUser::getUri('',$kde['pageId'],$kde['param']));
					$tpl->setVariable('USERLOCATIONLONG',$kde['name']);
					$tpl->setVariable('USERLOCATIONSHORT',$kde['nameshort']);
					$tpl->setVariable('USERACTIVE',substr($pra[1],3,5));
					$tpl->parseCurrentBlock();
				}
				$ret = $tpl->get();

			}
			$cache->setData($ret);
		}
		return($ret);
	}
}