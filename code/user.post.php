<?php
$reqSetRecipient = fXajax::register('post_setRecipientAvatarFromBooked');
$reqSetRecipient->setParameter(0, XAJAX_INPUT_VALUE, 'prokoho_book');

$reqSetRecipientFromInput = fXajax::register('post_setRecipientAvatarFromInput');
$reqSetRecipientFromInput->setParameter(0, XAJAX_INPUT_VALUE, 'prokoho');

//---action part
$redir = false;
if(isset($_GET['filtr'])) {
	if($_GET['filtr'] == 'cancel') {
		$user->filterClean();
	}
}

if (isset($_POST["perpage"]) && $_POST["perpage"] != $user->postPerPage) { 
	$user->postPerPage = $_REQUEST["perpage"]*1;
	$redir = true;
}

if (isset($_REQUEST["filtr"])) {
	$user->filterSet($user->currentPageId,'text',fSystem::textins($_REQUEST["zprava"]));
	$user->filterSet($user->currentPageId,'username',fSystem::textins($_REQUEST["prokoho"],0,0));
	$redir = true;
}

if(isset($_POST["send"]) && $_POST["zprava"]!='') {
	$user->filterClean();
	if (!empty($_POST["prokoho"])){
		$protmp=Explode(",",$_POST["prokoho"]);
		foreach ($protmp as $usrname) {
			if($pro = $user->getUserIdByName(Trim($usrname))) $arrto[]=$pro;
			else $errjm[]=Trim($usrname);
		}
		if(!empty($errjm)) fError::addError(implode(", ",$errjm)." :: ".MESSAGE_USERNAME_NOTEXISTS);
	}

	if (!empty($_POST["prokoho_book"])) $arrto[]=$_POST["prokoho_book"];
	if (empty($arrto)) {
		fError::addError('postnoto');
		$_SESSION['posta_arr']=htmlspecialchars(trim($_POST["zprava"]));
	}
	$odkoho = $user->gid;
	$zprava = fSystem::textins($_POST["zprava"]);
	if(!fError::isError() && !empty($zprava)) {
		foreach ($arrto as $komu){
		  $user->send($komu,$zprava,$user->gid);
		}
		fUserDraft::clear('postText');
		$redir = true;
	}
}
if ($redir==true) {
	fHTTP::redirect($user->getUri());
}
//---mazani zprav
if ((isset($_POST["delo"]) || isset($_POST["delbe"])) && !empty($_POST["del"])) {
	if(isset($_POST["delbe"]) && Count($_POST["del"])>1){
		$arrdelex = $_SESSION["postid"];
		$de=false;
		for($x=0;$x<count($arrdelex);$x++){
			if($arrdelex[$x]==$_POST["del"][0] && $de==false) $de=true;
			if($de==true) $arrdel[]=$arrdelex[$x];
			if($arrdelex[$x]==$_POST["del"][(Count($del)-1)]) {$de=false; break;}
		}
	} else {
		$arrdel = $_POST["del"];
	}
	$user->deletePost($arrdel);
	fHTTP::redirect($user->getUri());
}
//---add remove friend
if (isset($_REQUEST["unbookpra"]) || isset($_REQUEST["bookpra"])) {
	if (isset($_REQUEST["unbookpra"])) $user->delpritel($_REQUEST["bookuser"],$user->gid);
	else $user->addpritel($_REQUEST["bookuser"]);
	fHTTP::redirect($user->getUri());
}
//---showTIME
$perpage = $user->postPerPage;
if ($perpage < 2) $perpage = 2;

if(!empty($_SESSION['posta_arr'])) {
	$zprava = $_SESSION['posta_arr'];
	unset($_SESSION['posta_arr']);
}

//load draft
$zprava = fUserDraft::get('postText');
//load from filter
if($filterText = $user->filterGet($user->currentPageId,'text')) $zprava = $filterText;

//---filtering
$pagerExtraVars = array();
	
$totalItems = $user->getPost(0,0,true);

if(!empty($user->whoIs)) $pagerExtraVars['who']=$user->whoIs;

$od = 0;
if($totalItems > $perpage) {
	$pager = fSystem::initPager($totalItems,$perpage,array('extraVars'=>$pagerExtraVars));
	$od=($pager->getCurrentPageID()-1) * $perpage;
}

$arrpost = $user->getPost($od,$perpage);
	
//---set default recipient
$arrFriends = & $user->getFriends();

$recipients = '';
if(!empty($arrpost)) {
	if($arrpost[0]['userIdFrom']!=$user->gid) {
		$recipients = $user->getgidname($arrpost[0]['userIdFrom']);
		$recipientId = $arrpost[0]['userIdFrom'];
	}
	elseif ($arrpost[0]['userIdTo']!=$user->gid) {
		$recipients =  $user->getgidname($arrpost[0]['userIdTo']);
		$recipientId = $arrpost[0]['userIdTo'];
	}
}
if(!empty($user->whoIs)) {
	if($recipients = $user->getgidname($user->whoIs)) $recipientId = $user->whoIs;
}
//override recipients if filtering
if($filterUsername = $user->filterGet($user->currentPageId,'username')) $recipients = $filterUsername;

//--output
$recipientId = $recipientId * 1;

$tpl = new fTemplateIT('users.post.tpl.html');

$tpl->setVariable('FORMACTION',$user->getUri());
$tpl->touchBlock('selectedfriend');
$tpl->touchBlock('friendscombo');
//$tpl->setVariable('DRAFTACTION',fUserDraft::getAction());

if($recipientId>0) {
	$tpl->setVariable('SELECTEDFRIENDAVATAR',$user->showAvatar($recipientId));
	$tpl->setVariable('SELECTEDFRIENDNAME',$user->getgidname($recipientId));
	if(!$user->pritel($recipientId)) $tpl->setVariable('XAJAXDOFRIENDS',"xajax_user_switchFriend('".$recipientId.'");return(false);');
}

$tpl->setVariable('RECIPIENTS',$recipients);
$tpl->setVariable('RECIPIENTSONCHANGE',$reqSetRecipientFromInput->getScript());
$tpl->setVariable('MESSAGE',$zprava);
$tpl->addTextareaToolbox('MESSAGETOOLBOX','postText');
$tpl->setVariable('HIDDENWHO',$user->whoIs);
$tpl->setVariable('PERPAGE',$perpage);

if ($filterText) {
	$tpl->setVariable('FILTERTEXT',$filterText);
}
if ($filterUsername) {
	$tpl->setVariable('FILTERUSERNAME',$filterUsername);
}
if($totalItems > $user->postPerPage) {
	$tpl->setVariable('TOPPAGER',$pager->links);
	$tpl->setVariable('TOTAL',$totalItems);
	$tpl->setVariable('BOTTOMPAGER',$pager->links);
}

if(!empty($arrFriends)) {
	$tpl->setVariable('FRIENDSCOMBOONCHANGE',$reqSetRecipient->getScript());
	foreach ($arrFriends as $v) {
		$tpl->setCurrentBlock("friendscombovalue");
        $tpl->setVariable("FRIENDCOMBOID", $v);
        $tpl->setVariable("FRIENDCOMBONAME", $user->getgidname($v));
        $tpl->parseCurrentBlock();
	}
}

$_SESSION["postid"]=array();
//---data printing

if(!empty($arrpost)) {
	foreach ($arrpost as $post) {
		$tpl->setCurrentBlock("message");
		if($post['userIdFrom'] != $user->gid) $tpl->setVariable("MESSAGEAVATAR", $user->showAvatar($post['userIdFrom']));
		$tpl->setVariable("READEDCLASS", (($post["readed"]==1)?(' head'):(' butt')));
		if($post['readed']==0) $tpl->setVariable("READEDMESSAGEFAKEVAR", ' ');
		$tpl->setVariable("DELMESSAGEID", $post['postId']);
		$tpl->setVariable("DATE", $post["datumcz"]);
		if($post["userIdFrom"]==$user->gid) {
			$tpl->setVariable("SENTLINK", "34&who=".$post["userIdTo"]);
			$tpl->setVariable("SENTNAME", $user->getgidname($post["userIdTo"]));
		} else {
			$tpl->setVariable("RECEIVEDLINK", "34&who=".$post["userIdTo"],$user->getgidname($post["userIdTo"]));
			$tpl->setVariable("RECEIVEDNAME", $user->getgidname($post["userIdFrom"]));
			if(!$user->pritel($post["userIdFrom"])) $tpl->setVariable("RECEIVETOFRIEND", $user->getUri('bookpra=1&bookuser='.$post["userIdFrom"]));
		}
		$tpl->setVariable("MESSAGETEXT", $post["text"]);
		$tpl->parseCurrentBlock();
	
		/*prectena*/
		if ($post["userIdFrom"]!=$user->gid && $post["readed"]==0) {
		    $dot = "update sys_users_post set readed='1' where postId='".$post["postId"]."' || postIdFrom='".$post["postId"]."'";
			$db->query($dot);
		}
		$_SESSION["postid"][]=$post["postId"];
	}
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>