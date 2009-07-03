<?php
class rh_posta_kdo {
static function show() {
	$cache = FCache::getInstance('s',86400);
if(false === ($tmptext = $cache->getData('who','post'))) {
		$user = FUser::getInstance();

		$arrPost = array();


		$dot = "SELECT count(p.postId),userIdFrom,u.name
      	FROM sys_users_post as p join sys_users as u on u.userId=p.userIdFrom 
      	WHERE p.userId=".$user->userVO->userId." AND userIdFrom!=".$user->userVO->userId." 
      	GROUP BY userIdFrom ORDER BY u.name";
		$arr = FDBTool::getAll($dot,'received','post','s');
		foreach($arr as $row) {
			$arrPost[$row[1]] = array('received'=>$row[0],'sent'=>0,'name'=>$row[2]);
		}
			
		$dot = "SELECT count(p.postId),userIdTo,u.name
      	FROM sys_users_post as p join sys_users as u on u.userId=p.userIdTo 
      	WHERE p.userId=".$user->userVO->userId." AND userIdTo!=".$user->userVO->userId." 
      	GROUP BY userIdTo ORDER BY u.name";
		$arr = FDBTool::getAll($dot,'send','post','s');
		foreach($arr as $row) {
			if(isset($arrPost[$row[1]])) {
				$arrPost[$row[1]]['sent']=$row[0];
			} else {
				$arrPost[$row[1]] = array('received'=>0,'sent'=>$row[0],'name'=>$row[2]);
			}
		}

		$tmptext = '';
		if(!empty($arr)) {
			$tpl = new FTemplateIT('sidebar.users.tpl.html');
			foreach ($arrPost as $userId=>$userPost) {
				$tpl->setCurrentBlock('user');
				$tpl->setVariable('AVATAR',FAvatar::showAvatar($userId));
				$tpl->setVariable('USERLINK',FUser::getUri('m=user-postFilter&d=user:'.$userId.';s:all','fpost'));
				$tpl->setVariable('USERNAME',$userPost['name']);
				$tpl->setVariable('RECEIVEDLINK',FUser::getUri('m=user-postFilter&d=user:'.$userId.';s:received','fpost'));
				$tpl->setVariable('SENTLINK',FUser::getUri('m=user-postFilter&d=user:'.$userId.';s:sent','fpost'));
				$tpl->setVariable('RECEIVED',$userPost['received']);
				$tpl->setVariable('SENT',$userPost['sent']);
				$tpl->parseCurrentBlock();
			}
			$tmptext = $tpl->get();
		}
		$cache->setData($tmptext);
		}
		return($tmptext);
	}
}