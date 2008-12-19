<?php
if($who = $user->whoIs) {
  $userinfo = new fUser();
  $userinfo->gid = $who;
  $userinfo->refresh();
} else {
  $userInfo = &$user;
}

if(isset($_REQUEST["save"])) {
	if(!$user->pritel($who)) $user->addpritel($who);
	$koment=fSystem::textins($_POST["koment"],array('plainText'=>1));
	$dot = "UPDATE sys_users_friends SET comment='".$koment."' WHERE userId='".$user->gid."' AND userIdFriend='".$who."'";
	
	$db->query($dot);
	//TODO ??? lama send comment change? better to do page with user comments
	fHTTP::redirect($user->getUri('who='.$who));
}

//---SHOWTIME
$tpl = new fTemplateIT('users.info.tpl.html');

if($who != $user->gid) {
    $tpl->setVariable('FORMACTION',$user->getUri());
	$tpl->setVariable('MYCOMMENT',$arr[8]);
	$tpl->setVariable('WHOSELECTED',$who);
}

$tpl->setVariable('AVATAR',$user->showAvatar($who));
$tpl->setVariable('NAME',$userinfo->gidname);
$tpl->setVariable('EMAIL',$userinfo->email);
if(!empty($userinfo->icq)) $tpl->setVariable('ICQ',$userinfo->icq);
  
$tpl->setVariable("WWW",$userinfo->getXMLVal('personal','www'));
$tpl->setVariable("MOTTO",$userinfo->getXMLVal('personal','motto'));
$tpl->setVariable("PLACE",$userinfo->getXMLVal('personal','place'));
$tpl->setVariable("FOOD",$userinfo->getXMLVal('personal','food'));
$tpl->setVariable("HOBBY",$userinfo->getXMLVal('personal','hobby'));
$tpl->setVariable("ABOUT",$userinfo->getXMLVal('personal','about'));
  
$homePageId = $userinfo->getXMLVal('personal','HomePageId');
if(!empty($homePageId)) {
    $tpl->setVariable("HOMEPAGEID",$homePageId);
    $tpl->setVariable("HOMEPAGEUSERNAME",$userinfo->gidname);
}

$tpl->setVariable("SKINNAME",$userinfo->skinName);
$tpl->setVariable("DATECREATED",$userinfo->dateCreated);
$tpl->setVariable("DATEUPDATED",$userinfo->dateLast);

$fUvatar = new fUvatar($userinfo->gidname,array('targetFtp'=>ROOT.'tmp/fuvatar/','refresh'=> $userinfo->getXMLVal('webcam','interval'),'resolution'=> $userinfo->getXMLVal('webcam','resolution')));
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