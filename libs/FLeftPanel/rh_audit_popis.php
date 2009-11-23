<?php
class rh_audit_popis {
	static function show(){
		$user = FUser::getInstance();
		$ret = '';
			
		$klub = FDBTool::getRow("SELECT userIdOwner,description FROM sys_pages WHERE pageId='".$user->pageVO->pageId."'");
		if(empty($klub[1])) return;

		if(!empty($klub)) {
			$admins = FDBTool::getCol("SELECT userId FROM sys_users_perm WHERE rules=2 and pageId='".$user->pageVO->pageId."'");

			$tpl = FSystem::tpl(FLang::$TPL_SIDEBAR_PAGE_DESC);
			$tpl->setVariable('DESCRIPTION',$klub[1]);
			$tpl->setVariable('OWNERAVATAR',FAvatar::showAvatar($klub[0]));
			if(!empty($admins))
			foreach ($admins as $adm) {
				$tpl->setVariable('SMALLADMINAVATAR',FAvatar::showAvatar($adm));
				$tpl->parse('otheradminsavatars');
			}
			$ret = $tpl->get();
		}
		return $ret;
	}
}