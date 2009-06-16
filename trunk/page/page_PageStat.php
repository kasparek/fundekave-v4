<?php
include_once('iPage.php');
class page_PageStat implements iPage {

	static function process($data) {

	}

	static function build() {
		$user = FUser::getInstance();

		$tpl = new FTemplateIT('forums.stat.tpl.html');
		$tpl->setVariable('OWNERLINK','?k=33&who='.$user->pageVO->userIdOwner);
		$tpl->setVariable('OWNERNAME',FLang::$getgidname($user->pageVO->userIdOwner));
		$tpl->setVariable('MESSAGESCOUNT',$user->pageVO->cnt);
		$tpl->setVariable('DATECREATED',$user->pageVO->dateCreated);

		$dot = "select c.userId,
sum(c.hit) as hitsum,
sum(c.ins),
f.cnt,
f.book 
from sys_pages_counter as c 
left join sys_pages_favorites as f on c.pageId=f.pageId and c.userId=f.userId 
where c.pageId='".$user->pageVO->pageId."' group by c.userId order by hitsum desc";
		$arr = FDBTool::getAll($dot);

		$poz = false;
		$x=1;
		foreach ($arr as $row) {
			$tpl->setCurrentBlock('userstat');
			if($poz) $tpl->setVariable('DUMMYODD',' ');
			$tpl->setVariable('USERNUMBER',$x++);
			if($row[0]>0) {
				$tpl->setVariable('USERLINK','?k=finfo&who='.$row[0]);
				$tpl->setVariable('USERNAME',FUser::getgidname($row[0]));
			} else {
				$tpl->setVariable('DUMMYNOTREG','');
			}
			$tpl->setVariable('OWN',$row[2]);
			$tpl->setVariable('UNREADED',$user->pageVO->cnt-$row[3]);
			$tpl->setVariable('VISITS',$row[1]);
			if ($user->pageVO->userIdOwner==$row[0]) $watchin = FLang::$LABEL_OWNER;
			elseif ($row[0]==0) $watchin = FLang::$LABEL_NOTREGISTEREDUSERS;
			elseif($row[4]==1) $watchin = FLang::$LABEL_YES;
			else $watchin = FLang::$LABEL_NO;
			$tpl->setVariable('BOOKED',$watchin);
			$tpl->parseCurrentBlock();

			if($poz) $poz=false; else $poz=true;
		}

		FBuildPage::addTab(array("MAINHEAD"=>'',"MAINDATA"=>$tpl->get()));
	}
}
