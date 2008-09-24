<?php

if(isset($_POST['nav'])) {
	if($_POST['nav']=='infosave') {
	    
		$xml = new SimpleXMLElement($user->userXml);
		//--setxml elements
		$user->icq = str_replace("-","",fSystem::textins($_POST['infoicq'],0,0));
		$user->email = fSystem::textins($_POST['infoemajl'],0,0);
		
		if($_POST['skin'] > 0) $user->skin = $_POST['skin'] * 1;
		
		$xml->personal[0]->www = fSystem::textins($_POST['infowww'],0,0);
		$xml->personal[0]->place = fSystem::textins($_POST['infomisto'],0,0);
		$xml->personal[0]->food = fSystem::textins($_POST['infojidlo'],0,0);
		$xml->personal[0]->hobby = fSystem::textins($_POST['infohoby'],0,0);
		$xml->personal[0]->motto = fSystem::textins($_POST['infomotto'],0,0);
		$xml->personal[0]->about = fSystem::textins($_POST['infoabout']);
		$homePageId = fSystem::textins($_POST['homepageid'],0,0);
		if(fPages::pageOwner($homePageId) == $user->gid) $xml->personal[0]->HomePageId = $homePageId;
		$user->userXml = $xml->asXML();
		
		$user->zbanner = (($_POST["zbanner"]=='1')?(1):(0));
		$user->zaudico = (($_POST["zaudico"]=='1')?(1):(0));
		$user->zidico = (($_POST["zidico"]=='1')?(1):(0));
		$user->galtype = (($_POST["galtype"]=='1')?(1):(0));
		
		//password
		$pass1 = fSystem::textins($_POST["pwdreg1"],0,0);
		$pass2 = fSystem::textins($_POST["pwdreg2"],0,0);
		if($pass1!=''){
			if(strlen($pass1)<3) fError::addError(ERROR_REGISTER_PASSWORDTOSHORT);
			if($pass1!=$pass2) fError::addError(ERROR_REGISTER_PASSWORDDONTMATCH);
			if (!fError::isError()){
				$user->newPassword = md5(trim($_POST["pwdreg1"]));
				fError::addError(MESSAGE_PASSWORD_SET);
			}
		}
		
		//avatar
		if ($_FILES["idfoto"]["error"] == 0){
			$konc = Explode(".",$_FILES["idfoto"]["name"]);
			$_FILES["idfoto"]['name'] = fSystem::safeText($user->gidname).".".$user->gid.".".$konc[(count($konc)-1)];
			if($up = fSystem::upload($_FILES["idfoto"],WEB_REL_AVATAR,20000)) {
			 //---resize and crop if needed
			 list($avatarWidth,$avatarHeight,$type) = getimagesize(WEB_REL_AVATAR.$up['name']);
			 if($avatarWidth!=AVATAR_WIDTH_PX || $avatarHeight!=AVATAR_HEIGHT_PX) {
			     if($type!=2) $up['name'] = str_replace($konc[(count($konc)-1)],'jpg',$up['name']);
                  //---RESIZE
                $resizeParams = array('quality'=>80,'crop'=>1,'width'=>AVATAR_WIDTH_PX,'height'=>AVATAR_HEIGHT_PX);
                $iProc = new fImgProcess(WEB_REL_AVATAR.$_FILES["idfoto"]['name'],WEB_REL_AVATAR.$up['name'],$resizeParams);
			  
       }
			 $user->ico = $up['name'];
			}
		}
		
		$user->infowrt();
		fHTTP::redirect($user->getUri());
	}
}

$arr = $db->getrow("select icq,email,info from sys_users where userId='".$user->gid."'");

$tpl = new fTemplateIT('users.personal.html');

$tpl->setVariable("FORMACTION",BASESCRIPTNAME.'?k='.$user->currentPageId);
$tpl->setVariable("USERNAME",$user->gidname);
$options='';
$arrOpt = $db->getAll('select skinId,name from sys_skin order by name');
if(!empty($arrOpt)) foreach ($arrOpt as $row) {
	$options.='<option value="'.$row[0].'"'.(($row[0]==$user->skin)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
}
$tpl->setVariable("SKINOPTIONS",$options);

$xml = new SimpleXMLElement($arr[2]);
$personal = $xml->personal[0];

$options='';
$arrOpt = $db->getAll('select pageId,name from sys_pages where userIdOwner="'.$user->gid.'"');
if(!empty($arrOpt)) foreach ($arrOpt as $row) {
	$options.='<option value="'.$row[0].'"'.(($row[0]==(string) $personal->HomePageId)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
}
$tpl->setVariable("HOMEPAGEOPTIONS",$options);

$tpl->setVariable("USERICQ",$arr[0]);
$tpl->setVariable("USEREMAIL",$arr[1]);

$tpl->setVariable("USERWWW",$personal->www);
$tpl->setVariable("USERMOTTO",$personal->motto);
$tpl->setVariable("USERMISTO",$personal->place);
$tpl->setVariable("USERJIDLO",$personal->food);
$tpl->setVariable("USERHOBBY",$personal->hobby);
$tpl->setVariable("USERABOUT",fSystem::textToTextarea($personal->about));
$tpl->addTextareaToolbox('USERABOUTTOOLBOX','userabout');

if($user->zbanner == 1) $tpl->touchBlock('zbanner');
if($user->zaudico == 1) $tpl->touchBlock('zaudico');
if($user->zidico == 1) $tpl->touchBlock('zidico');
if($user->galtype == 1) $tpl->touchBlock('galtype');

//konec editace infa
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>