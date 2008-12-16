<?php
if(!$who = $user->whoIs) $who = $user->gid;

if(isset($_REQUEST["save"])) {
	if(!$user->pritel($who)) $user->addpritel($who);
	$koment=fSystem::textins($_POST["koment"],array('plainText'=>1));
	$dot = "UPDATE sys_users_friends SET comment='".$koment."' WHERE userId='".$user->gid."' AND userIdFriend='".$who."'";
	
	$db->query($dot);
	//TODO ??? lama send comment change? better to do page with user comments
	fHTTP::redirect($user->getUri('who='.$who));
}

$arr = $db->getRow("SELECT u.userId,u.name,u.email,u.icq,u.info,
date_format(u.dateCreated,'%H:%i:%S %d.%m.%Y') as dateCreatedCz,
date_format(u.dateUpdated,'%H:%i:%S %d.%m.%Y') as dateUpdatedCz,
s.name as skinname,
f.comment FROM sys_users as u 
left join sys_skin as s on s.skinId=u.skinId 
left join sys_users_friends as f on f.userId='".$user->gid."' and f.userIdFriend=u.userId 
WHERE u.userId = '".$who."'");

$user->userXml = $arr[4];

$tpl = new fTemplateIT('users.info.tpl.html');

if($who != $user->gid) {
    $tpl->setVariable('FORMACTION',$user->getUri());
	$tpl->setVariable('MYCOMMENT',$arr[8]);
	$tpl->setVariable('WHOSELECTED',$who);
}

$tpl->setVariable('AVATAR',$user->showAvatar($who));
$tpl->setVariable('NAME',$arr[1]);
$tpl->setVariable('EMAIL',$arr[2]);
if(!empty($arr[3])) $tpl->setVariable('ICQ',$arr[3]);
  
$tpl->setVariable("WWW",$user->getXMLVal('personal','www'));
$tpl->setVariable("MOTTO",$user->getXMLVal('personal','motto'));
$tpl->setVariable("PLACE",$user->getXMLVal('personal','place'));
$tpl->setVariable("FOOD",$user->getXMLVal('personal','food'));
$tpl->setVariable("HOBBY",$user->getXMLVal('personal','hobby'));
$tpl->setVariable("ABOUT",$user->getXMLVal('personal','about'));
  
$homePageId = $user->getXMLVal('personal','HomePageId');
if(!empty($homePageId)) {
    $tpl->setVariable("HOMEPAGEID",$homePageId);
    $tpl->setVariable("HOMEPAGEUSERNAME",$arr[1]);
}

$tpl->setVariable("SKINNAME",$arr[7]);
$tpl->setVariable("DATECREATED",$arr[5]);
$tpl->setVariable("DATEUPDATED",$arr[6]);

$fUvatar = new fUvatar($arr[1],array('targetFtp'=>ROOT.'tmp/fuvatar/','refresh'=> $user->getXMLVal('webcam','interval'),'resolution'=> $user->getXMLVal('webcam','resolution')));
//check if has any image from webcam
if($fUvatar->hasData()) {
    $tpl->setVariable("WEBCAM",$fUvatar->getSwf());
}

$arr = $db->getAll('SELECT u.userId,u.name,f.comment 
FROM sys_users_friends AS f 
LEFT JOIN sys_users AS u ON u.userId = f.userId WHERE f.comment != "" AND f.userId!="'.$user->gid.'" AND f.userIdFriend="'.$who.'"');

foreach ($arr as $kom) {
    
	$tpl->setCurrentBlock('friendcomment');
	$tpl->setVariable("USERLINK",$user->getUri('who='.$kom[0]));
	$tpl->setVariable("USERAVATAR",$user->showAvatar($kom[0]));
	$tpl->setVariable("USERNAME",$kom[1]);
	$tpl->setVariable("USERCOMMENT",$kom[2]);
	$tpl->parseCurrentBlock();
}

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));