<?php
$do = $db->getRow("SELECT pollId,question,votesperuser,publicresults FROM sys_poll WHERE activ=1 AND pageId='".$user->currentPageId."'");

$tmptext = '';
if(!empty($do)) {
	if($do[3] == 1) {
		$tmptext = '<h2>'.$do[1]."</h2>\n";
		$vv = $db->getAll("SELECT pollAnswerId, answer FROM sys_poll_answers WHERE pollId = ".$do[0]." ORDER BY ord");
		foreach ($vv as $odp) {
			$arrVotesUsers = $db->getAll("select u.userId,u.name from sys_poll_answers_users as p left join sys_users as u on u.userId=p.userId where pollAnswerId=".$odp[0]." order by u.name");
			$tmptext.='<p><strong>'.$odp[1].'</strong> ['.count($arrVotesUsers)."]\n";
			if(!empty($arrVotesUsers)) {
				foreach ($arrVotesUsers as $votedUsers) {
					$tmptext.='&nbsp;:: <a href="?k=finfo&who='.$votedUsers[0].'">'.$votedUsers[1]."</a><br />\r\n";
				}
			} else {
				$tmptext .= '<br />'.MESSAGE_POLL_NOANSWERS;
			}
			$tmptext.="</p>\n";
		}
		if (fRules::get($user->gid,$user->currentPageId,2)) {
      $tmptext .= MESSAGE_POLL_RESULTSRESOURCE.'<br / ><textarea class="largetextarea">'.str_replace(array("<br />\r\n",'"'),array("\r\n",""),$tmptext).'</textarea>';
    }
	} else {
		$tmptext = MESSAGE_POLL_NOTPUBLIC;
	}
} else $tmptext = MESSAGE_POLL_NORESULTS;
$TOPTPL->addTab(array("MAINHEAD"=>LABEL_POLL_RESULTS,"MAINDATA"=>$tmptext));
//---administrace ankety
if (fRules::get($user->gid,$user->currentPageId,2)) {
	require(ROOT.ROOT_CODE."poll.edit.php");
}
?>