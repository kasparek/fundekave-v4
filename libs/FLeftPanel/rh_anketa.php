<?php
class rh_anketa {
	//---odpid je nastaveno jen kdyz se hlasuje
	static function process($data) {
		if(isset($_GET['poll']) && $user->idkontrol) {
			$arrGet = explode(";",$_GET['poll']);
			if($ankid==0) $ankid = FDBTool::getOne("SELECT pollId FROM sys_poll WHERE activ=1 AND pageId='".$user->pageVO->pageId."'");
			if($arrGet[0]==$ankid) $odpid = $arrGet[1];
		}
		if ($odpid > 0) {
			$cache->invalidateGroup('poll');
		}
	}
	static function show($ankid=0,$odpid=0) {
		$user = FUser::getInstance();

		$data = '';

		if($ankid == 0) $do=FDBTool::getRow("SELECT pollId,question,votesperuser FROM sys_poll WHERE activ=1 AND pageId='".$user->pageVO->pageId."'");
		else $do=FDBTool::getRow("SELECT pollId,question,votesperuser FROM sys_poll WHERE pollId=".$ankid);
		if(!empty($do))	{
			$voted=false;
			$arrVoted = FDBTool::getCol("SELECT pollAnswerId FROM sys_poll_answers_users WHERE pollId=".$do[0]." AND userId=".$user->userVO->userId);

			if(($do[2]-count($arrVoted))<1) $voted = true;
			//---write wote
			if (!empty($odpid) && $user->idkontrol && !$voted){
				FDBTool::query("INSERT INTO sys_poll_answers_users (pollId,pollAnswerId,userId) VALUES ('".$ankid."','".$odpid."','".$user->userVO->userId."')");
				$arrVoted = FDBTool::getCol("SELECT pollAnswerId FROM sys_poll_answers_users WHERE pollId=".$do[0]." AND userId=".$user->userVO->userId);
			}
			$restVotes = $do[2]-count($arrVoted);
			if($restVotes<1) {
				$restVotes = 0;
				$voted = true;
			}
			if(!empty($do)) {
				//odpovedi
				$vv=FDBTool::getAll("SELECT pollAnswerId, answer FROM sys_poll_answers WHERE pollId = ".$do[0]." ORDER BY ord");
				if($voted || !empty($arrVoted)) {
					//pocet odpovedi
					$pocet=FDBTool::getOne("SELECT count(1) FROM sys_poll_answers_users WHERE pollId=".$do[0]);
					//klik
					$vk=FDBTool::getAll("SELECT pollAnswerId,count(1) AS soucet FROM sys_poll_answers_users WHERE pollId = ".$do[0]." GROUP BY pollAnswerId ORDER BY pollAnswerId");
					foreach($vk as $row) $sc[$row[0]]=$row[1];
				}
				/* ........... viditelna cast ........*/
				$tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
				$tpl->loadTemplatefile('sidebar.poll.tpl.html');	
				
				$tpl->setVariable('QUESTION',$do[1]);
				foreach($vv as $odp){
					$votedtmp = $voted;
					if($restVotes>0 && in_array($odp[0],$arrVoted)) $votedtmp=true;
					$tpl->setCurrentBlock('answer');
					if(!$votedtmp) {
						if(!empty($odpid)){
							$tpl->setVariable('POLLID',$do[0]);
							$tpl->setVariable('ANSWERID',$odp[0]);
						}
						$tpl->setVariable('NOTVOTEDANSWER',$odp[1]);
						$tpl->setVariable('ANSWERURL',FUser::getUri('m=user-poll&d=po:'.$do[0].';an:'.$odp[0]));
					} else {
						$tpl->setVariable('ANSWER',$odp[1]);
						$tpl->setVariable('COLUMNSIMGURL','/sloupec.gif');
						if(!isset($sc[$odp[0]])) $sc[$odp[0]] = 0;
						$tpl->setVariable('COLUMNWIDTH',(($sc[$odp[0]]!=0)?(Round(($sc[$odp[0]]/$pocet)*160)):('1')));
						$tpl->setVariable('ANSWEWRCOUNT',((isset($sc[$odp[0]]))?($sc[$odp[0]]):('0')));
					}
					$tpl->parseCurrentBlock();
				}
				if($do[2]>1 && $restVotes>0) $tpl->setVariable('RESTVOTES',$restVotes);
			}
			$data = $tpl->get();
		}
			
		return $data;

	}
}