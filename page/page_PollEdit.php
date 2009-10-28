<?php
//TODO: refactor _POST _REQUEST
include_once('iPage.php');
class page_PollEdit implements iPage {

	static function process($data) {
		$user = FUser::getInstance();

		$selectedPageId = $user->pageVO->pageId;
		
		if($superAdmin = FRules::get($user->userVO->userId,'sadmi',1)) {
			$cache = FCache::getInstance('s');
			if(isset($data['selpageid'])) {
				$tmpSelectedPageId = trim($data['selpageid']);
				if(FPages::page_exist('pageId',$tmpSelectedPageId)) {
					$cache->setData($tmpSelectedPageId, 'page','poll');
				}
				else FError::addError(FLang::$ERROR_PAGE_NOTEXISTS);
			}
			if(false !== ($pageId = $cache->getData('page','poll'))) {
				$selectedPageId = $pageId;
			}
		}

		if(!empty($_REQUEST["ankid"])) $ankid = $_REQUEST["ankid"]*1; else $ankid=0;

		if(isset($_POST["add"])) {
			$arr=array('dateCreated'=>'NOW()','userId'=>$user->gid);
			$arr['question']=Trim($_POST['otazka']);
			if(isset($_POST['aktivnov'])) $arr['activ']=1; else $arr['activ']=0;
			$arr['pageId'] = $selectedPageId;
			if($superAdmin) {
				$tmpSelectedPageId = $_POST['newpageid'];
				if(FPages::page_exist('pageId',$tmpSelectedPageId)) $arr['pageId'] = $tmpSelectedPageId;
			}
			$arr['votesperuser'] = $_POST['votesperuser']*1;
			if(isset($_POST['publicresults'])) $arr['publicresults'] = 1; else $arr['publicresults'] = 0;
			if(strlen($arr['question'])>0) {
				$sAnketa = new FDBTool('sys_poll','pollId');
				$sAnketa->Save($arr,array('dateCreated'));
				$cache = FCache::getInstance('s');
				$cach->invalidateGroup('poll');
			}
			else FError::addError(FLang::$ERROR_POLL_QUESTION);
			FHTTP::redirect(FSystem::getUri());
		}
		
		if(isset($_POST['saveank']) && !empty($_POST['arr'])){
			foreach($_POST['arr'] as $k=>$ank){
				$arr=array('pollId'=>$k);
				if(isset($ank['aktiv'])) $arr['activ']=1; else $arr['activ']=0;
				if($superAdmin) {
					if(FPages::page_exist('pageId',$ank['idstr'])) $arr['pageId'] = $ank['idstr'];
				}
				$arr['dateUpdated']='now()';
				$sAnketa = new FDBTool('sys_poll','pollId');
				$sAnketa->Save($arr,array('dateUpdated'));
				$cache = FCache::getInstance('s');
				$cach->invalidateGroup('poll');
			}
			
			if(isset($_POST["delank"])){
				foreach ($_POST["delank"] as $an){
					FDBTool::query("DELETE FROM sys_poll_answers_users WHERE pollId='".$an."'");
					FDBTool::query("DELETE FROM sys_poll_answers WHERE pollId='".$an."'");
					FDBTool::query("DELETE FROM sys_poll WHERE pollId='".$an."'");
				}
				$cache = FCache::getInstance('s');
				$cach->invalidateGroup('poll');
			}
			
			FHTTP::redirect(FSystem::getUri());
		}
		
		if(isset($_POST['saveodp'])){

			$otazka=FSystem::textins($_POST['otazka'],array('plainText'=>1));
			$votesperuser = $_POST['votesperuser']*1;
			$publicResults = ((isset($_POST['publicresults']))?(1):(0));
			
			if($otazka!='') {
				$sAnketa = new FDBTool('sys_poll','pollId');
				$sAnketa->Save(array('question'=>$otazka,'votesperuser'=>$votesperuser,'pollId'=>$ankid,'publicresults'=>$publicResults,'dateUpdated'=>'now()'),array('dateUpdated'));
			}

			if(!empty($_POST['arr'])){
				foreach ($_POST['arr'] as $k=>$odp) {
					$arr=array('pollId'=>$ankid,'answer'=>FSystem::textins($odp['odpoved'],array('plainText'=>1)),'ord'=>$odp['poradi']*1);
					$sAnkOdp = new FDBTool('sys_poll_answers','pollAnswerId');
					if($k!=0) {
						$arr['pollAnswerId']=$k;
						$sAnkOdp->Save($arr);
					} elseif($arr['answer']!='') $sAnkOdp->Save($arr);
						
				}
			}
			
			if(isset($_POST['delodp'])){
				foreach ($_POST['delodp'] as $an){
					FDBTool::query("DELETE FROM sys_poll_answers_users WHERE pollAnswerId='".$an."'");
					FDBTool::query("DELETE FROM sys_poll_answers WHERE pollAnswerId='".$an."'");
				}
			}
			
			$cache = FCache::getInstance('s');
			$cach->invalidateGroup('poll');
			FHTTP::redirect(FSystem::getUri((!empty($ankid))?('ankid='.$ankid):('')));
		}
		if(isset($_POST['letsnull'])){
			FDBTool::query("DELETE FROM sys_poll_answers_users WHERE pollId='".$ankid."'");
			$cache = FCache::getInstance('s');
			$cach->invalidateGroup('poll');
			FHTTP::redirect(FSystem::getUri((!empty($ankid))?('ankid='.$ankid):('')));
		}
	}
	
	static function build() {
		$user = FUser::getInstance();
		
		$selectedPageId = $user->pageVO->pageId;
		
		if($superAdmin = FRules::get($user->gid,'sadmi',1)) {
			$cache = FCache::getInstance('s');
			if(false !== ($pageId = $cache->getData('page','poll'))) {
				$selectedPageId = $pageId;
			}
		}
		
		if(!empty($_REQUEST["ankid"])) $ankid = $_REQUEST["ankid"]*1; else $ankid=0;

		$tpl = new FTemplateIT('poll.edit.tpl.html');
		if($superAdmin) {
			$tpl->setVariable('FORMACTIONADM',FSystem::getUri());
			$tpl->setVariable('SELECTEDPAGEID',$selectedPageId);
		}

		$tpl->setVariable('FORMACTIONNEW',FSystem::getUri());
		if($superAdmin) $tpl->touchBlock('pageadm');

		$arr=$db->getAll("SELECT pollId,pageId,activ,question,dateCreated,userId FROM sys_poll".(($selectedPageId!='')?(" where pageId='".$selectedPageId."'"):(''))." ORDER BY dateCreated DESC");

		$tpl->setVariable('FORMACTIONEDIT',FSystem::getUri());
		if($superAdmin) $tpl->touchBlock('paheader');
		foreach ($arr as $row){
			$tpl->setCurrentBlock('polllist');
			$tpl->setVariable('LISTPOLLID',$row[0]);
			$tpl->setVariable('LISTACTIV',(($row[2]==1)?(' checked="checked"'):('')));
			$tpl->setVariable('LISTLINKEDIT',FSystem::getUri('ankid='.$row[0]));
			$tpl->setVariable('LISTQUESTION',$row[3]);
			$tpl->setVariable('LISTOWNERLINK',FSystem::getUri('who='.$row[5],'33'));
			$tpl->setVariable('LISTOWNERNAME',FUser::getgidname($row[5]));
			$tpl->setVariable('LISTDATECREATE',$row[4]);
			if($superAdmin) {
				$tpl->setVariable('LISTPOLLIDADM',$row[0]);
				$tpl->setVariable('POLLPAGEIDADM',$row[1]);
			}
			$tpl->parseCurrentBlock();
		}


		if(!empty($ankid)){
			$tpl->setVariable('FORMACTIONDETAIL',FSystem::getUri());
			$tpl->setVariable('POLLID',$ankid);

			$dot = FDBTool::getRow("SELECT question,publicresults,votesperuser FROM sys_poll WHERE pollId='".$ankid."'");

			if(!empty($dot)){
				//$tpl->setVariable('POLLSHOW',fLeftPanelPlugins::rh_anketa($ankid));
				$tpl->setVariable('POLLQUESTION',$dot[0]);
				$tpl->setVariable('POLLPUBLICCHECKED',(($dot[1]==1)?(' checked="checked"'):('')));
				$tpl->setVariable('POLLVOTESPERUSER',$dot[2]);
				 
				$vv = FDBTool::getAll("SELECT a.pollAnswerId, a.answer, a.ord, count(u.pollAnswerId) FROM sys_poll_answers as a
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
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}