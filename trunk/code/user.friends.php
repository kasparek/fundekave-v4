<?php
if(isset($_REQUEST["book_idpra"])){
	$user->addpritel($_REQUEST["book_idpra"]);
	fHTTP::redirect($user->getUri());
}
if(isset($_REQUEST["unbook_id"])) {
	$user->delpritel($_REQUEST["unbook_id"]);
	fHTTP::redirect($user->getUri());
}

$tpl = new fTemplateIT('user.friends.tpl.html');

if (!empty($user->whoIs)) {
    $usrid = $user->whoIs;
    $tpl->setVariable('WHOISNAME',$user->getgidname($usrid));
    $tpl->setVariable('SELECTEDFRIENDAVATAR',$user->showAvatar($usrid));
    $tpl->setVariable('SELECTEDFRIENDNAME',$user->getgidname($usrid));
    $tpl->setVariable('XAJAXDOFRIENDS',"xajax_user_switchFriend('".$usrid."');return(false);");
    $tpl->setVariable('XAJAXDOFRIENDSLABEL',($user->pritel($usrid))?(LABEL_FRIEND_REMOVE):(LABEL_FRIEND_ADD));
	
} else
	$usrid = $user->gid;
	
$arronline = $db->getAll("SELECT f.userIdFriend, 
SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateCreated)) as cas,
SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateUpdated)) as casklik, 
p.pageId, p.name, p.nameshort  
FROM sys_users_logged as l
INNER JOIN sys_users_friends as f ON f.userIdFriend=l.userId 
left join sys_pages as p on p.pageId=l.location 
WHERE subdate(NOW(),interval ".USERVIEWONLINE." minute) < l.dateUpdated 
AND f.userId = ".$usrid." 
AND l.userId!=".$usrid);

if(!empty($arronline)) {
	foreach ($arronline as $online) {
	    $tpl->setCurrentBlock('friendsonlinerow');
	    $tpl->setVariable('ONLINEFRIENDAVATAR',$user->showAvatar($online[0]));
        $tpl->setVariable('ONLINEFRIENDNAME',$user->getgidname($online[0]));
        $tpl->setVariable('ONLINECURRENTPAGE',$online[5].' '.$online[4]);
        $tpl->setVariable('ONLINECURRENTPAGELINK',$online[3]);
        $tpl->setVariable('ONLINELOGIN',$online[1]);
        $tpl->setVariable('ONLINELAST',$online[2]);
	    $tpl->parseCurrentBlock();
	}
} else $tpl->touchBlock('friendstable');

/*....zacatek vypisu booklych .....*/
$arrpra = $db->getAll("SELECT f.userIdFriend,
f.comment,
date_format(u.dateLastVisit,'%H:%i:%S %d.%m.%Y') as last,
u.name 
FROM sys_users_friends as f 
left join sys_users as u on f.userIdFriend=u.userId
WHERE f.userId='".$usrid."' ORDER BY u.name");
if(!empty($arrpra)) {
  foreach ($arrpra as $pra) {
      $tpl->setCurrentBlock('friendsrow');
  	    $tpl->setVariable('FRIENDSAVATAR',$user->showAvatar($pra[0]));
          $tpl->setVariable('FRIENDSNAME',$user->getgidname($pra[0]));
          $tpl->setVariable('FUSERID',$pra[0]);
          $tpl->setVariable('FLAST',$pra[2]);
          if(!empty($pra[1])) $tpl->setVariable('FCOMMENT',$pra[1]);
  	    $tpl->parseCurrentBlock();
  }
} else {
  $tpl->touchBlock('friendstable');
}
	
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>