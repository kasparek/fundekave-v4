<?php
class rh_audit_popis {
static function show(){
		$cache = FCache::getInstance('f',86400);
		$user = FUser::getInstance();
		if($user->pageAccess==true) {
			if(false === ($ret = $cache->getData($user->pageVO->pageId.'-page-'.($user->userVO->userId*1).'-user','forumdesc'))) {
				$ret['klub'] = FDBTool::getRow("SELECT userIdOwner,description FROM sys_pages WHERE pageId='".$user->pageVO->pageId."'");
				if(!empty($ret['klub'])){
					$ret['admins'] = FDBTool::getCol("SELECT userId FROM sys_users_perm WHERE rules=2 and pageId='".$user->pageVO->pageId."'");
				}
				$cache->setData($ret);
			}
			$klub = $ret['klub'];
			$admins = $ret['admins'];
			$ret = '';
			if(!empty($klub)) {
				$tpl = new FTemplateIT('sidebar.page.description.tpl.html');
				$tpl->setVariable('DESCRIPTION',$klub[1]);
				$tpl->setVariable('OWNERAVATAR',FAvatar::showAvatar($klub[0]));
				if(!empty($admins))
				foreach ($admins as $adm) {
					$tpl->setCurrentBlock('otheradminsavatars');
					$tpl->setVariable('SMALLADMINAVATAR',FAvatar::showAvatar($adm));
					$tpl->parseCurrentBlock();
				}
				$ret = $tpl->get();
			}
			return $ret;
		}
	}
}