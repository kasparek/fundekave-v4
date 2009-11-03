<?php
include_once('iPage.php');
class page_PagePoll implements iPage {

	static function process($data) {

		//---administrace ankety
		if (FRules::getCurrent(2)) {
			page_PollEdit::process($data);
		}

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$do = FDBTool::getRow("SELECT pollId,question,votesperuser,publicresults FROM sys_poll WHERE activ=1 AND pageId='".$user->pageVO->pageId."'");

		$tmptext = '';
		if(!empty($do)) {
			if($do[3] == 1) {
				$tmptext = '<h2>'.$do[1]."</h2>\n";
				$vv = FDBTool::getAll("SELECT pollAnswerId, answer FROM sys_poll_answers WHERE pollId = ".$do[0]." ORDER BY ord");
				foreach ($vv as $odp) {
					$arrVotesUsers = FDBTool::getAll("select u.userId,u.name from sys_poll_answers_users as p left join sys_users as u on u.userId=p.userId where pollAnswerId=".$odp[0]." order by u.name");
					$tmptext.='<p><strong>'.$odp[1].'</strong> ['.count($arrVotesUsers)."]\n";
					if(!empty($arrVotesUsers)) {
						foreach ($arrVotesUsers as $votedUsers) {
							$tmptext.='&nbsp;:: <a href="?k=finfo&who='.$votedUsers[0].'">'.$votedUsers[1]."</a><br />\r\n";
						}
					} else {
						$tmptext .= '<br />'.FLang::$MESSAGE_POLL_NOANSWERS;
					}
					$tmptext.="</p>\n";
				}
				if (FRules::getCurrent(2)) {
					$tmptext .= FLang::$MESSAGE_POLL_RESULTSRESOURCE.'<br / ><textarea class="largetextarea">'.str_replace(array("<br />\r\n",'"'),array("\r\n",""),$tmptext).'</textarea>';
				}
			} else {
				$tmptext = FLang::$MESSAGE_POLL_NOTPUBLIC;
			}
		} else $tmptext = FLang::$MESSAGE_POLL_NORESULTS;
		FBuildPage::addTab(array("MAINHEAD"=>FLang::$LABEL_POLL_RESULTS,"MAINDATA"=>$tmptext));

		//---administrace ankety
		if (FRules::getCurrent(2)) {
			page_PollEdit::build();
		}
	}
}