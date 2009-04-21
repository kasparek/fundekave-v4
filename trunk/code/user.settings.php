<?php
if(isset($_POST['nav'])) {
	if($_POST['nav']=='infosave') {
		//--setxml elements
		$user->icq = str_replace("-","",fSystem::textins($_POST['infoicq'],array('plainText'=>1)));
		$user->email = fSystem::textins($_POST['infoemajl'],array('plainText'=>1));
		
		if($_POST['skin'] > 0) $user->skin = $_POST['skin'] * 1;
		$user->setXMLVal('settings','bookedorder', $_POST['bookedorder']*1);
		$user->setXMLVal('personal','www',fSystem::textins($_POST['infowww'],array('plainText'=>1)));
		$user->setXMLVal('personal','place',fSystem::textins($_POST['infomisto'],array('plainText'=>1)));
		$user->setXMLVal('personal','food',fSystem::textins($_POST['infojidlo'],array('plainText'=>1)));
		$user->setXMLVal('personal','hobby',fSystem::textins($_POST['infohoby'],array('plainText'=>1)));
		$user->setXMLVal('personal','motto',fSystem::textins($_POST['infomotto'],array('plainText'=>1)));
		$user->setXMLVal('personal','about',fSystem::textins($_POST['infoabout']));
		if(!empty($_POST['homepageid'])) {
		  $homePageId = fSystem::textins($_POST['homepageid'],array('plainText'=>1));
		  if(fPages::pageOwner($homePageId) == $user->gid) $user->setXMLVal('personal','HomePageId',$homePageId);
		}
		//---webcam
		$user->setXMLVal('webcam','public',(int) $_POST['campublic']);
		if(!empty($_POST['camchosen'])) {
		$chosenUsernames = explode(',',$_POST['camchosen']);
		foreach ($chosenUsernames as $username) {
            $username = trim($username);
            $userId = $user->getUserIdByName($username);
            if($userId > 0) $arrUserIdValidatedArr[$userId] = $userId; 
        }
            if(!empty($arrUserIdValidatedArr)) {
		       $userListChosen = implode(',',$arrUserIdValidatedArr);
                $user->setXMLVal('webcam','chosen',$userListChosen);
            }
		}
        $user->setXMLVal('webcam','avatar',(int) $_POST['camavatar']);
		$user->setXMLVal('webcam','resolution',(int) $_POST['camresolution']);
		
    $interval = (int) $_POST['caminterval'];
		if($interval<2) $interval = 2;
		if($interval>100) $interval = 100;
		$user->setXMLVal('webcam','interval',$interval);
		
        $quality = (int) $_POST['camquality'];
		if($quality<0) $quality = 0;
		if($quality>100) $quality = 100;
		$user->setXMLVal('webcam','quality',$quality);
		
		$user->setXMLVal('webcam','motion',(int) $_POST['cammotion']);
		
		$user->zbanner = (($_POST["zbanner"]=='1')?(1):(0));
		$user->zaudico = (($_POST["zaudico"]=='1')?(1):(0));
		$user->zidico = (($_POST["zidico"]=='1')?(1):(0));
		$user->galtype = (($_POST["galtype"]=='1')?(1):(0));
		
		//password
		$pass1 = fSystem::textins($_POST["pwdreg1"],array('plainText'=>1));
		$pass2 = fSystem::textins($_POST["pwdreg2"],array('plainText'=>1));
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

//---SHOW TIME
$tpl = new fTemplateIT('users.personal.html');

$tpl->setVariable("FORMACTION",BASESCRIPTNAME.'?k='.$user->currentPageId);
$tpl->setVariable("USERNAME",$user->gidname);
$options='';
$arrOpt = $db->getAll('select skinId,name from sys_skin order by name');
if(!empty($arrOpt)) foreach ($arrOpt as $row) {
	$options.='<option value="'.$row[0].'"'.(($row[0]==$user->skin)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
}
$tpl->setVariable("SKINOPTIONS",$options);

/*
$options='';
$arrOpt = $db->getAll('select pageId,name from sys_pages where userIdOwner="'.$user->gid.'"');
if(!empty($arrOpt)) foreach ($arrOpt as $row) {
	$options.='<option value="'.$row[0].'"'.(($row[0]==(string) $personal->HomePageId)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
}
$tpl->setVariable("HOMEPAGEOPTIONS",$options);
*/
$tpl->setVariable("USERICQ",$user->email);
$tpl->setVariable("USEREMAIL",$user->icq);

$tpl->setVariable("USERWWW",$user->getXMLVal('personal','www'));
$tpl->setVariable("USERMOTTO",$user->getXMLVal('personal','motto'));
$tpl->setVariable("USERMISTO",$user->getXMLVal('personal','place'));
$tpl->setVariable("USERJIDLO",$user->getXMLVal('personal','food'));
$tpl->setVariable("USERHOBBY",$user->getXMLVal('personal','hobby'));
$tpl->setVariable("USERABOUT",fSystem::textToTextarea($user->getXMLVal('personal','about')));
$tpl->addTextareaToolbox('USERABOUTTOOLBOX','userabout');

if($user->zbanner == 1) $tpl->touchBlock('zbanner');
if($user->zaudico == 1) $tpl->touchBlock('zaudico');
if($user->zidico == 1) $tpl->touchBlock('zidico');
if($user->galtype == 1) $tpl->touchBlock('galtype');
if($user->getXMLVal('settings','bookedorder') == 1) $tpl->touchBlock('bookedorder');

//webcam
switch($user->getXMLVal('webcam','public')) {
  case 1: 
    $tpl->touchBlock('campublicregistered');
    break;
  case 2:
    $tpl->touchBlock('campublicfriends');
    break;
  case 3:
    $tpl->touchBlock('campublicchosen');
    break;
}
$arrChosen = explode(',',$user->getXMLVal('webcam','chosen'));
foreach ($arrChosen as $userId) {
    $arrUsernames[] = $user->getgidname($userId);
}
$tpl->setVariable('CAMCHOSEN',implode(',',$arrUsernames));

if($user->getXMLVal('webcam','avatar') == 1) $tpl->touchBlock('camavatar');
if($user->getXMLVal('webcam','resolution') == 1) $tpl->touchBlock('camresolution1');

$tpl->setVariable('CAMINTERVAL',$user->getXMLVal('webcam','interval'));
$tpl->setVariable('CAMQUALITY',$user->getXMLVal('webcam','quality'));

if($user->getXMLVal('webcam','motion') == 0) $tpl->touchBlock('cammotion');

//konec editace infa
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));