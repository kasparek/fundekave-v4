<?php
include_once('iPage.php');
class page_FriendsAll implements iPage {

	static function process() {

	}

	static function build() {
		$userId = FUser::logon();
		$arr = FDBTool::getAll("
SELECT u.userId,
u.name,
date_format(u.dateCreated,'%H:%i:%S %d.%m.%Y'),
date_format(u.dateLastVisit,'%H:%i:%S %d.%m.%Y'),
hit 
FROM sys_users as u 
left join sys_users_friends as f on f.userId='".$userId."' and f.userIdFriend=u.userId 
WHERE u.dateLastVisit IS NOT NULL and f.userId is null AND u.userId!='".$userId."' AND u.deleted is null 
ORDER BY u.dateLastVisit desc
");

		$tpl = new FTemplateIT('user.friends.all.tpl.html');
		/*....zacatek vypisu booklych nebo vsech pratel podle podminky idb......*/
		foreach ($arr as $pra) {
			$tpl->setCurrentBlock('user');
			$tpl->setVariable('USERNAME',$pra[1]);
			$tpl->setVariable('USERAVATAR',FAvatar::showAvatar($pra[0]));
			$tpl->setVariable('USERREG',$pra[2]);
			$tpl->setVariable('USERLAST',$pra[3]);
			$tpl->setVariable('USERHIT',$pra[4]);
			$tpl->parseCurrentBlock();
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}