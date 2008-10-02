<?php
$superAdmin = false;
$selectedPageId = $user->currentPageId;
if($superAdmin = fRules::get($user->gid,'sadmi',1)) {
    if(!isset($_SESSION['syseditpoll'])) $_SESSION['syseditpoll'] = '';
    $selectedPageId = & $_SESSION['syseditpoll'];
    if(isset($_POST['selpageid'])) {
        $tmpSelectedPageId = trim($_POST['selpageid']);
        if(fPages::page_exist('pageId',$tmpSelectedPageId)) $selectedPageId = $tmpSelectedPageId;
        else fError::addError(ERROR_PAGE_NOTEXISTS);
    }
}

if(!empty($_REQUEST["ankid"])) $ankid=$_REQUEST["ankid"]*1; else $ankid=0;

if(isset($_POST["add"])) {
	$arr=array('dateCreated'=>'NOW()','userId'=>$user->gid);
	$arr['question']=Trim($_POST['otazka']);
	if(isset($_POST['aktivnov'])) $arr['activ']=1; else $arr['activ']=0;
	$arr['pageId'] = $selectedPageId;
	if($superAdmin) {
	    $tmpSelectedPageId = $_POST['newpageid'];
	    if(fPages::page_exist('pageId',$tmpSelectedPageId)) $arr['pageId'] = $tmpSelectedPageId;
	}
	$arr['votesperuser'] = $_POST['votesperuser']*1;
	if(isset($_POST['publicresults'])) $arr['publicresults'] = 1; else $arr['publicresults'] = 0;
	if(strlen($arr['question'])>0) {
		$sAnketa = new fSqlSaveTool('sys_poll','pollId');
		$sAnketa->Save($arr,array('dateCreated'));
		$user->cacheRemove('poll');
	}
	else fError::addError(ERROR_POLL_QUESTION);
	fHTTP::redirect($user->getUri());
}
if(isset($_POST['saveank']) && !empty($_POST['arr'])){
	foreach($_POST['arr'] as $k=>$ank){
		$arr=array('pollId'=>$k);
		if(isset($ank['aktiv'])) $arr['activ']=1; else $arr['activ']=0;
		if($superAdmin) {
		    if(fPages::page_exist('pageId',$ank['idstr'])) $arr['pageId'] = $ank['idstr'];
		}
		$arr['dateUpdated']='now()';
		$sAnketa = new fSqlSaveTool('sys_poll','pollId');
		$sAnketa->Save($arr,array('dateUpdated'));
		$user->cacheRemove('poll');
	}
	if(isset($_POST["delank"])){
		foreach ($_POST["delank"] as $an){
			$db->query("DELETE FROM sys_poll_answers_users WHERE pollId='".$an."'");
			$db->query("DELETE FROM sys_poll_answers WHERE pollId='".$an."'");
			$db->query("DELETE FROM sys_poll WHERE pollId='".$an."'");
		}
		$user->cacheRemove('poll');
	}
	fHTTP::redirect($user->getUri());
}
if(isset($_POST['saveodp'])){
    
	$otazka=fSystem::textins($_POST['otazka'],array('plainText'=>1));
	$votesperuser = $_POST['votesperuser']*1;
	$publicResults = ((isset($_POST['publicresults']))?(1):(0));
	if($otazka!='') {
		$sAnketa = new fSqlSaveTool('sys_poll','pollId');
		
		$sAnketa->Save(array('question'=>$otazka,'votesperuser'=>$votesperuser,'pollId'=>$ankid,'publicresults'=>$publicResults,'dateUpdated'=>'now()'),array('dateUpdated'));
	}
	
	if(!empty($_POST['arr'])){
		foreach ($_POST['arr'] as $k=>$odp) {
			$arr=array('pollId'=>$ankid,'answer'=>fSystem::textins($odp['odpoved'],array('plainText'=>1)),'ord'=>$odp['poradi']*1);
			$sAnkOdp = new fSqlSaveTool('sys_poll_answers','pollAnswerId');
			
			if($k!=0) {
				$arr['pollAnswerId']=$k;
				$sAnkOdp->Save($arr);
			} elseif($arr['answer']!='') $sAnkOdp->Save($arr);
			
		}
	}
	if(isset($_POST['delodp'])){
		foreach ($_POST['delodp'] as $an){
			$db->query("DELETE FROM sys_poll_answers_users WHERE pollAnswerId='".$an."'");
			$db->query("DELETE FROM sys_poll_answers WHERE pollAnswerId='".$an."'");
		}
	}
	$user->cacheRemove('poll');
	fHTTP::redirect($user->getUri((!empty($ankid))?('ankid='.$ankid):('')));
}
if(isset($_POST['letsnull'])){
	$db->query("DELETE FROM sys_poll_answers_users WHERE pollId='".$ankid."'");
	$user->cacheRemove('poll');
	fHTTP::redirect($user->getUri((!empty($ankid))?('ankid='.$ankid):('')));
}

$tpl = new fTemplateIT('poll.edit.tpl.html');
if($superAdmin) {
    $tpl->setVariable('FORMACTIONADM',$user->getUri());
    $tpl->setVariable('SELECTEDPAGEID',$selectedPageId);
}

$tpl->setVariable('FORMACTIONNEW',$user->getUri());
if($superAdmin) $tpl->touchBlock('pageadm');

$arr=$db->getAll("SELECT pollId,pageId,activ,question,dateCreated,userId FROM sys_poll".(($selectedPageId!='')?(" where pageId='".$selectedPageId."'"):(''))." ORDER BY dateCreated DESC");

$tpl->setVariable('FORMACTIONEDIT',$user->getUri());
if($superAdmin) $tpl->touchBlock('paheader');
foreach ($arr as $row){
    $tpl->setCurrentBlock('polllist');
        $tpl->setVariable('LISTPOLLID',$row[0]);
        $tpl->setVariable('LISTACTIV',(($row[2]==1)?(' checked="checked"'):('')));
        $tpl->setVariable('LISTLINKEDIT',$user->getUri('ankid='.$row[0]));
        $tpl->setVariable('LISTQUESTION',$row[3]);
        $tpl->setVariable('LISTOWNERLINK',$user->getUri('who='.$row[5],'33'));
        $tpl->setVariable('LISTOWNERNAME',$user->getgidname($row[5]));
        $tpl->setVariable('LISTDATECREATE',$row[4]);
        if($superAdmin) {
            $tpl->setVariable('LISTPOLLIDADM',$row[0]);
            $tpl->setVariable('POLLPAGEIDADM',$row[1]);
        }
    $tpl->parseCurrentBlock();
}


if(!empty($ankid)){
    $tpl->setVariable('FORMACTIONDETAIL',$user->getUri());
    $tpl->setVariable('POLLID',$ankid);
        
	$dot=$db->getRow("SELECT question,publicresults,votesperuser FROM sys_poll WHERE pollId='".$ankid."'");
	
	if(!empty($dot)){
	    $tpl->setVariable('POLLSHOW',fLeftPanel::rh_anketa($ankid));
	    $tpl->setVariable('POLLQUESTION',$dot[0]);
	    $tpl->setVariable('POLLPUBLICCHECKED',(($dot[1]==1)?(' checked="checked"'):('')));
	    $tpl->setVariable('POLLVOTESPERUSER',$dot[2]);
	    
		$vv = $db->getAll("SELECT a.pollAnswerId, a.answer, a.ord, count(u.pollAnswerId) FROM sys_poll_answers as a 
		left join sys_poll_answers_users as u on a.pollAnswerId=u.pollAnswerId 
		WHERE a.pollId = ".$ankid." group by a.pollAnswerId ORDER BY a.ord");
	
		foreach ($vv as $odp) {
		    
		    $tpl->setCurrentBlock('pollanswer');
		    $tpl->setVariable('ANSWERID',$odp[0]);
		    $tpl->setVariable('ANSWER',$odp[1]);
		    $tpl->setVariable('ANSWERORD',$odp[2]);
		    $tpl->setVariable('ANSWERHITS',$odp[3]);
		    $tpl->parseCurrentBlock();
	   }
	   $tpl->setVariable('ANSWERNEWORD',count($vv)+1);
	}
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));