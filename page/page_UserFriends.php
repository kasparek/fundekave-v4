<?php
include_once('iPage.php');
class page_UserFriends implements iPage {

	static function process($data) {
		
	}

	static function build() {
		$user = FUser::getInstance();

		$tpl = new FTemplateIT('user.friends.tpl.html');

		if ($user->whoIs > 0) {
			$userId = $user->whoIs;
			$tpl->setVariable('WHOISNAME',UserVO::getgidname($userId));
			$tpl->setVariable('SELECTEDFRIENDAVATAR',FAvatar::showAvatar($userId));
			$tpl->setVariable('SELECTEDFRIENDNAME',UserVO::getgidname($userId));
		} else {
			$userId = $user->userVO->userId;
		}

		$q = "SELECT f.userIdFriend,
SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateCreated)) as cas,
SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateUpdated)) as casklik, 
p.pageId, p.name, p.nameshort  
FROM sys_users_logged as l
INNER JOIN sys_users_friends as f ON f.userIdFriend=l.userId 
left join sys_pages as p on p.pageId=l.location 
WHERE subdate(NOW(),interval ".USERVIEWONLINE." minute) < l.dateUpdated 
AND f.userId = ".$userId." 
AND l.userId!=".$userId;
		$arronline = FDBTool::getAll($q, 'on', 'friends','s',30);

		if(!empty($arronline)) {
			foreach ($arronline as $online) {
				$tpl->setCurrentBlock('friendsonlinerow');
				$tpl->setVariable('ONLINEFRIENDAVATAR',FAvatar::showAvatar($online[0]));
				$tpl->setVariable('ONLINEFRIENDNAME',FUser::getgidname($online[0]));
				$tpl->setVariable('ONLINECURRENTPAGE',$online[5].' '.$online[4]);
				$tpl->setVariable('ONLINECURRENTPAGELINK',$online[3]);
				$tpl->setVariable('ONLINELOGIN',$online[1]);
				$tpl->setVariable('ONLINELAST',$online[2]);
				$tpl->parseCurrentBlock();
			}
		} else $tpl->touchBlock('friendstable');

		/*....zacatek vypisu booklych .....*/
		$q = "SELECT f.userIdFriend, date_format(u.dateLastVisit,'%H:%i:%S %d.%m.%Y') as last, u.name FROM sys_users_friends as f left join sys_users as u on f.userIdFriend=u.userId WHERE f.userId='".$userId."' ORDER BY u.name";
		$arrpra = FDBTool::getAll($q, 'fav', 'friends','s',30);
		if(!empty($arrpra)) {
			foreach ($arrpra as $pra) {
				$tpl->setCurrentBlock('friendsrow');
				$tpl->setVariable('FRIENDSAVATAR', FAvatar::showAvatar($pra[0]));
				$tpl->setVariable('FRIENDSNAME', $pra[2]);
				$tpl->setVariable('FUSERID', $pra[0]);
				$tpl->setVariable('FLAST', $pra[1]);
				$tpl->parseCurrentBlock();
			}
		} else {
			$tpl->touchBlock('friendstable');
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}