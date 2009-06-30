<?php
class FLeftPanelPlugins {

	static function pocket() {
		$user = FUser::getInstance();
		$fPocket = new FPocket($user->userVO->userId);
		return $fPocket->show();
	}

	static function pageCategories() {
		$user = FUser::getInstance();
		$cache = FCache::getInstance('f',86400);
		if(false===($tmptext = $cache->getData(($user->userVO->userId*1).'-user', 'pagescategories'))) {
			$arr = FDBTool::getAll("select categoryId,name from sys_pages_category where typeId = '".$user->pageVO->pageId."' order by ord,name");
			$tmptext = '';
			if(!empty($arr)) {
					
				$tpl = new FTemplateIT('sidebar.page.categories.tpl.html');
				foreach ($arr as $category) {
					$tpl->setCurrentBlock('item');
					$tpl->setVariable('PAGEID',$user->pageVO->pageId);
					$tpl->setVariable('CATEGORYID',$category[0]);
					$tpl->setVariable('NAME',$category[1]);
					$tpl->setVariable('SUM',FDBTool::getOne("select count(1) from sys_pages_items where categoryId='".$category[0]."'"));
					$tpl->parseCurrentBlock();
				}
				$tmptext = $tpl->get();
			}
			$cache->setData($tmptext);
		}
		return $tmptext;
	}

	static function bookedRelatedPagesList() {
		$user = FUser::getInstance();
		$cache = FCache::getInstance('f',86400);
		if(false === ($tmptext = $cache->getData($user->pageVO->pageId.'-page-'.($user->userVO->userId*1).'-user','bookedpagesrelated'))) {
			$fPages = new FPages('',$user->userVO->userId);
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0,sum(f1.book) as booksum');
			$fPages->addJoin('join sys_pages_favorites as f1 on p.pageId = f1.pageId');
			$fPages->addJoin("join sys_pages_favorites as f2 on f1.userId=f2.userId and f2.pageId='".$user->pageVO->pageId."' and f2.book = '1'");
			$fPages->addWhere("f1.book=1 and f1.pageId!='".$user->pageVO->pageId."'");
			$fPages->setGroup('f1.pageId');
			$fPages->setOrder('booksum desc');
			$fPages->setLimit(0,10);
			$arr = $fPages->getContent();
			$tmptext = '';
			if(!empty($arr)) {
				$tmptext = FPages::printPagelinkList($arr);
			}
			$cache->setData($tmptext);
		}
		return $tmptext;
	}

	static function relatedPagesList() {
		$user = FUser::getInstance();
		$cache = FCache::getInstance('f',86400);
		if(false === ($tmptext = $cache->getData($user->pageVO->pageId.'-page-'.($user->userVO->userId*1).'-user','pagesrelated'))) {
			$fPages = new FPages('',$user->userVO->userId);
			$fPages->addJoin('join sys_pages_relations as r on p.pageId = r.pageIdRelative');
			$fPages->addWhere('r.pageId="'.$user->pageVO->pageId.'"');

			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0');

			$arr = $fPages->getContent();
			$tmptext = '';
			if(!empty($arr)) {
				$tmptext = FPages::printPagelinkList($arr);
			}
			$cache->setData($tmptext);
		}
		return $tmptext;
	}

	static function rh_posta_kdo() {
	$cache = FCache::getInstance('s',86400);
if(false === ($tmptext = $cache->getData('who','post'))) {
		$user = FUser::getInstance();

		$arrPost = array();


		$dot = "SELECT count(p.postId),userIdFrom,u.name
      	FROM sys_users_post as p join sys_users as u on u.userId=p.userIdFrom 
      	WHERE p.userId=".$user->userVO->userId." AND userIdFrom!=".$user->userVO->userId." 
      	GROUP BY userIdFrom ORDER BY u.name";
		$arr = FDBTool::getAll($dot,'received','post','s');
		foreach($arr as $row) {
			$arrPost[$row[1]] = array('received'=>$row[0],'sent'=>0,'name'=>$row[2]);
		}
			
		$dot = "SELECT count(p.postId),userIdTo,u.name
      	FROM sys_users_post as p join sys_users as u on u.userId=p.userIdTo 
      	WHERE p.userId=".$user->userVO->userId." AND userIdTo!=".$user->userVO->userId." 
      	GROUP BY userIdTo ORDER BY u.name";
		$arr = FDBTool::getAll($dot,'send','post','s');
		foreach($arr as $row) {
			if(isset($arrPost[$row[1]])) {
				$arrPost[$row[1]]['sent']=$row[0];
			} else {
				$arrPost[$row[1]] = array('received'=>0,'sent'=>$row[0],'name'=>$row[2]);
			}
		}

		$tmptext = '';
		if(!empty($arr)) {
			$tpl = new FTemplateIT('sidebar.users.tpl.html');
			foreach ($arrPost as $userId=>$userPost) {
				$tpl->setCurrentBlock('user');
				$tpl->setVariable('AVATAR',FAvatar::showAvatar($userId));
				$tpl->setVariable('USERLINK',FUser::getUri('m=user-postFilter&d=user:'.$userId.';s:all','fpost'));
				$tpl->setVariable('USERNAME',$userPost['name']);
				$tpl->setVariable('RECEIVEDLINK',FUser::getUri('m=user-postFilter&d=user:'.$userId.';s:received','fpost'));
				$tpl->setVariable('SENTLINK',FUser::getUri('m=user-postFilter&d=user:'.$userId.';s:sent','fpost'));
				$tpl->setVariable('RECEIVED',$userPost['received']);
				$tpl->setVariable('SENT',$userPost['sent']);
				$tpl->parseCurrentBlock();
			}
			$tmptext = $tpl->get();
		}
		$cache->setData($tmptext);
		}
		return($tmptext);
	}

	static function rh_login() {
		$user = FUser::getInstance();
		if($user->idkontrol) {
			$tpl = new FTemplateIT('sidebar.user.logged.tpl.html');
			$tpl->setVariable('AVATAR',FAvatar::showAvatar(-1,array('noTooltip'=>1)));
			$tpl->setVariable('NAME',$user->userVO->name);
			$tpl->setVariable('ONLINE',FSystem::getOnlineUsersCount());
			$recentEvent = $user->userVO->getDiaryCnt();
			if($recentEvent>0) $tpl->setVariable('DIARY',$recentEvent);
			if($user->userVO->hasNewMessages()) {
				$tpl->setVariable('NEWPOST',$user->userVO->newPost);
				$tpl->setVariable('NEWPOSTFROMNAME',$user->userVO->newPostFrom);
			}
		} else {

			$tpl = new FTemplateIT('sidebar.user.login.tpl.html');
			$tpl->setVariable('FORMACTION',$user->getUri());
			if(REGISTRATION_ENABLED == 1) $tpl->touchBlock('reglink');
		}
			
		$ret = $tpl->get();
		return $ret;
	}

	static function rh_datum() {
		/*
		 global $conf,$user,$DAYS;
		 if(!$ret = $user->cacheGet('datelefthand')) {
		 include(ROOT.$conf['language']['path'].'calendar.php');
		 $tpl = new FTemplateIT('sidebar.today.tpl.html');
		 $tpl->setVariable('DAYWORD',$DAYS[Date("D")]);
		 $tpl->setVariable('DATE',Date('d.'.'m.'.'Y'),$ret);
		 $tpl->setVariable('TIME',date("H:i"),$ret);
		 $tpl->setVariable('DAYINYEAR',(Date('z')+1),$ret);
		 $tpl->setVariable('NAMEDAY',$mesic[(int)Date("m")][Date("j")],$ret);
		 $ret = $tpl->get();
		 $user->cacheSave($ret);
		 }
		 return($ret);
		 */
	}

	static function rh_akce_rnd() {
		$cache = FCache::getInstance('f',86400);
		if(false === ($data = $cache->getData(((FUser::logon()>0)?('1'):('0')).'-logged','eventtip')) ) {
		  $itemRenderer = new FItemsRenderer();
		  $itemRenderer->setCustomTemplate('sidebar.event.tpl.html');
			$fItems = new FItems('event',false,$itemRenderer);
			$fItems->addWhere('dateStart >= NOW() or (dateEnd is not null and dateEnd >= NOW())');
			$fItems->setOrder('rand()');
			$fItems->getList(0,1);
			$data = '';
			if(!empty($fItems->data)) {
				$data = $fItems->render();
			}
			$cache->setData($data);
		}
		return $data;
	}

	//---odpid je nastaveno jen kdyz se hlasuje
	static function rh_anketa($ankid=0,$odpid=0) {

		$user = FUser::getInstance();
		$cache = FCache::getInstance('f',86400);

		if($user->idkontrol) { ///anketa je jen pro registrovany

			if(isset($_GET['poll']) && $user->idkontrol) {
				$arrGet = explode(";",$_GET['poll']);
				if($ankid==0) $ankid = FDBTool::getOne("SELECT pollId FROM sys_poll WHERE activ=1 AND pageId='".$user->pageVO->pageId."'");
				if($arrGet[0]==$ankid) $odpid = $arrGet[1];
			}
			if ($odpid > 0) {
				$cache->invalidateGroup('poll');
			}

			if(false ===($data = $cache->getData($user->pageVO->pageId.'-page-'.($user->userVO->userId*1).'-user','poll'))) {
				$data = '';
				$arrVoted = array();

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
							
						$tpl = new FTemplateIT('sidebar.poll.tpl.html');
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

					$cache->setData($data);
				}
			}
			return $data;
		}
	}

	static function rh_galerie_rnd(){
		$cache = FCache::getInstance('f',180);
		if(false === ($data = $cache->getData((FUser::logon()>0)?('member'):('nonmember'),'fotornd'))) {

			$itemRenderer = new FItemsRenderer();
			$itemRenderer->openPopup = true;
			$itemRenderer->showPageLabel = true;
			$itemRenderer->showTooltip = true;
			$itemRenderer->showTag = true;
			$itemRenderer->showText = true;

			$fItems = new FItems('galery',false,$itemRenderer);
			$fItems->thumbInSysRes = true;
			$total = $fItems->getCount();
			$data = $fItems->render(rand(0,$total),1);
			$cache->setData($data);
		}
		return $data;
	}

	static function rh_audit_popis(){
		$cache = FCache::getInstance('f',86400);
		$user = FUser::getInstance();
		if($user->pageAccess==true) {
			if(false === ($ret = $cache->getData($user->pageVO->pageId.'-page-'.($user->userVO->userId*1).'-user','forumdesc'))) {
				$ret['klub'] = FDBTool::getRow("SELECT userIdOwner,description FROM sys_pages WHERE pageId='".$user->pageVO->pageId."'");
				if(!empty($ret['klub'])){
					$ret['admins'] = FDBTool::getCol("SELECT userId FROM sys_users_perm WHERE rules=2 and pageId='".$user->pageVO->pageId."'");
				}
				$cache->setData($ret);
			}
			$klub = $ret['klub'];
			$admins = $ret['admins'];
			$ret = '';
			if(!empty($klub)) {
				$tpl = new FTemplateIT('sidebar.page.description.tpl.html');
				$tpl->setVariable('DESCRIPTION',$klub[1]);
				$tpl->setVariable('OWNERAVATAR',FAvatar::showAvatar($klub[0]));
				if(!empty($admins))
				foreach ($admins as $adm) {
					$tpl->setCurrentBlock('otheradminsavatars');
					$tpl->setVariable('SMALLADMINAVATAR',FAvatar::showAvatar($adm));
					$tpl->parseCurrentBlock();
				}
				$ret = $tpl->get();
			}
			return $ret;
		}
	}

	static function rh_logged_list(){
		$cache = FCache::getInstance('l',10);
		if(false === ($ret = $cache->getData('loggedlist'))) {
			$ret = '';
			$user = FUser::getInstance();
			$arrpra = FDBTool::getAll("SELECT f.userIdFriend,SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateUpdated)) as casklik FROM sys_users_logged as l INNER JOIN sys_users_friends as f ON f.userIdFriend=l.userId  WHERE subdate(NOW(),interval ".USERVIEWONLINE." minute)<l.dateUpdated AND f.userId=".$user->userVO->userId." AND f.userIdFriend!='".$user->userVO->userId."' GROUP BY f.userIdFriend ORDER BY casklik");
			if (count($arrpra)>0){
				$tpl = new FTemplateIT('sidebar.users.tpl.html');
				foreach ($arrpra as $pra){
					$kde = FUser::getLocation($pra[0]);
					$tpl->setCurrentBlock('user');
					$tpl->setVariable('AVATAR',FAvatar::showAvatar($pra[0]));
					$tpl->setVariable('USERLINK',FUser::getUri('who='.$pra[0],'finfo'));
					$tpl->setVariable('USERNAME',FUser::getgidname($pra[0]));
					$tpl->setVariable('USERLOCATIONLINK',FUser::getUri('',$kde['pageId'],$kde['param']));
					$tpl->setVariable('USERLOCATIONLONG',$kde['name']);
					$tpl->setVariable('USERLOCATIONSHORT',$kde['nameshort']);
					$tpl->setVariable('USERACTIVE',substr($pra[1],3,5));
					$tpl->parseCurrentBlock();
				}
				$ret = $tpl->get();

			}
			$cache->setData($ret);
		}
		return($ret);
	}

	//TODO: refactor links - fajax
	static function rh_diar_kalendar($year='',$month='') {
		$dden = 1;
		if(!empty($_REQUEST['ddate'])) {
			list($drok,$dmesic,$dden)=explode("-",$_REQUEST['ddate']);
		}
		if($year!='' || $month!='')  {
			$drok = $year;
			$dmesic = $month;
		} 
			
		if(empty($drok) || !checkdate($dmesic,$dden,$drok)) {
			$dmesic = date("m");
			$drok = date("Y");
			$dden = date("j");
		}
			
		//---cache by drok,dmesic
		$cache = FCache::getInstance('f',3600);
		$user = FUser::getInstance();
		$data = $cache->getData($user->pageVO->pageId.'-page-'.($user->userVO->userId*1).'-user-'.$drok.$dmesic,'calendarlefthand');

		if(false===$data) {

			$cisden=array("Mon"=>"1","Tue"=>"2","Wed"=>"3","Thu"=>"4","Fri"=>"5","Sat"=>"6","Sun"=>"7");
			$scas=mktime(0,0,0,$dmesic,1,$drok);
			$dentydnu=$cisden[date("D",$scas)];
			$dnumesice=date("t",$scas);
			$z=1;
			$den=1;
			$hor=7;
			$ver=6;
			$ver=ceil((($dentydnu-1)+$dnumesice)/$hor);

			if(($dmesic-1)<1) {
				$monthbefore=12;
				$yearbefore=$drok-1;
			} else {
				$monthbefore=sprintf("%02d",($dmesic-1));
				$yearbefore=$drok;
			}
			if(($dmesic+1)>12) {
				$monthafter='01';
				$yearafter=$drok+1;
			} else {
				$monthafter=sprintf("%02d",($dmesic+1));
				$yearafter=$drok;
			}
			if(!checkdate($monthafter,$dden,$yearafter)) $dayafter='01'; else $dayafter = $dden;
			if(!checkdate($monthbefore,$dden,$yearbefore)) $daybefore='01'; else $daybefore = $dden;

			if($user->pageVO->typeId == 'top') {
				$arrUsedPlugins = array(
  	    	'diaryItems',
  	    	'diaryRecurrenceItems',
  	    	'events',
  	    	'blogItems',
  	    	'galeryItems',
  	    	'forums'
  	    	);
  	    	$userPageId = false;
			} elseif($user->pageVO->typeId == 'event') {
				$arrUsedPlugins = array(
  	    	'diaryItems',
  	    	'diaryRecurrenceItems',
  	    	'events',
				);
				$userPageId = false;
			} else {
				$userPageId = true;
				//---just items for given page
				$typeId = $user->pageVO->typeId;
				if($typeId=='blog') $arrUsedPlugins = array('blogItems');
			}
			$arrQ = array();
			if(!empty($arrUsedPlugins)) {
				foreach ($arrUsedPlugins as $pluginName) {
					$arrTmp = FCalendarPlugins::$pluginName($drok,$dmesic,$user->userVO->userId,($userPageId==true)?($user->pageVO->pageId):(''));
					if(is_array($arrTmp) && !empty($arrTmp)) $arrQ = array_merge($arrQ,$arrTmp);
				}
				$arrEventsForDay = array();
				$arrEventForDayKeys = array();
				foreach ($arrQ as $row){
					$arrEventsForDay[$row[0]][] = array('link'=>$row[1],'id'=>$row[2],'name'=>$row[3],'dateiso'=>$row[4],'datelocal'=>$row[5]);
				}
				$arrEventForDayKeys = array_keys($arrEventsForDay);
				$tpl = new FTemplateIT('sidebar.calendar.tpl.html');
				$tpl->setVariable('CURRENTMONTH',FLang::$MONTHS[$dmesic]);
				$tpl->setVariable('CURRENTYEAR',$drok);
				for ($x=1;$x<=$hor;$x++) {
					$tpl->setCurrentBlock('daysheader');
					$tpl->setVariable('DAYSHORTCUT',FLang::$DAYSSHORT[$x]);
					$tpl->parseCurrentBlock();
				}
				for ($y=0;$y < ($ver);$y++) {
					for ($x=0;$x<$hor;$x++) {
						$tpl->setCurrentBlock('column');

						if($z>=$dentydnu && $den<=$dnumesice) {
							if(date("j") == $den && $dmesic == date("m")) $tpl->touchBlock('dayCurrent');
							if(in_array($den,$arrEventForDayKeys)) {
								$tpl->touchBlock('dayEvent');
								$tpl->setVariable('TAGDAYID','day'.$den);
							}
							$tpl->setVariable('DIARYURL',sprintf("?k=fdiar&ddate=%04d-%02d-%02d",$drok,$dmesic,$den));
							$tpl->setVariable('DAY',$den);
							$den++;
						} else {
							$tpl->touchBlock('dayblank');
						}
						$z++;

						$tpl->parseCurrentBlock();
					}
					$tpl->setCurrentBlock('row');
					$tpl->parseCurrentBlock();
				}
				$tpl->setVariable('PREVIOUSMONTHURL',FUser::getUri(sprintf("ddate=%04d-%02d-%02d",$yearbefore,$monthbefore,$daybefore)));
				$tpl->setVariable('XYEARPREV',$yearbefore);
				$tpl->setVariable('XMONTHPREV',$monthbefore);
				$tpl->setVariable('PREVIOUSMONTH',FLang::$MONTHS[$monthbefore]);
				$tpl->setVariable('NEXTMONTHURL',FUser::getUri(sprintf("ddate=%04d-%02d-%02d",$yearafter,$monthafter,$dayafter)));
				$tpl->setVariable('XYEARNEXT',$yearafter);
				$tpl->setVariable('XMONTHNEXT',$monthafter);
				$tpl->setVariable('NEXTMONTH',FLang::$MONTHS[$monthafter]);

				foreach ($arrEventsForDay as $k=>$day) {
					foreach ($day as $event) {
						$tpl->setCurrentBlock('event');
						$tpl->setVariable('EVENTLINK',$event['link']);
						$tpl->setVariable('EVENTLABEL',$event['name']);
						$tpl->setVariable('STARTDATETIMEISO',$event['dateiso']);
						$tpl->setVariable('STARTDATETIMELOCAL',$event['datelocal']);
						$tpl->parseCurrentBlock();
					}
					$tpl->setCurrentBlock('eventday');
					$tpl->setVariable('DAYID','day'.($k*1));
					$tpl->parseCurrentBlock();
				}
				$data = $tpl->get();
				$cache->setData( $data );
			}
				
			return $data;
		}
	}

}