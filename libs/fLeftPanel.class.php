<?php
class fLeftPanel {
    static function pocket() {
        global $user;
        $fPocket = new fPocket($user->gid);
        return $fPocket->show();
    }
    
    static function bookedRelatedPagesList() {
        global $db,$user;
        if(!$tmptext = $user->cacheGet('bookedpagesrelated')) {
            $fPages = new fPages('',$user->gid,$db);
                        
            $fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0,sum(f1.book) as booksum');
            
            $fPages->addJoin('join sys_pages_favorites as f1 on p.pageId = f1.pageId');
            $fPages->addJoin("join sys_pages_favorites as f2 on f1.userId=f2.userId and f2.pageId='".$user->currentPageId."' and f2.book = '1'");
            $fPages->addWhere("f1.book=1 and f1.pageId!='".$user->currentPageId."'");
            $fPages->setGroup('f1.pageId');
            $fPages->setOrder('booksum desc');
            $fPages->setLimit(0,10);
            $arr = $fPages->getContent();
            $tmptext = '';
            if(!empty($arr)) {
                $tmptext = fPages::printPagelinkList($arr);
            }
            $user->cacheSave($tmptext);
        }
        return $tmptext;
    }
    static function relatedPagesList() {
      global $user,$db;
       if(!$tmptext = $user->cacheGet('pagesrelated')) {
        $fPages = new fPages('',$user->gid,$db);
        $fPages->addJoin('join sys_pages_relations as r on p.pageId = r.pageIdRelative');
        $fPages->addWhere('r.pageId="'.$user->currentPageId.'"');
        
        $fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0');
        
        $arr = $fPages->getContent();
        $tmptext = '';
        if(!empty($arr)) {
            $tmptext = fPages::printPagelinkList($arr);
        }
        $user->cacheSave($tmptext);
      }
      return $tmptext;
    }
    static function rh_posta_kdo() {
    	global $db,$user;
      if(!$serializedArr = $user->cacheGet('postwho')) {
        
        $dot = "SELECT count(p.postId),userIdFrom,i.name 
      	FROM sys_users_post AS p LEFT JOIN sys_users AS i ON i.userId=p.userIdFrom
      	WHERE p.userId=".$user->gid." AND p.userIdFrom!=".$user->gid." AND i.name is not null GROUP BY userIdFrom ORDER BY i.name";
      	$arr = $db->getAll($dot);
      	
        $user->cacheSave(serialize($arr));
      } else {
        $arr = unserialize($serializedArr);
      }
      
      $tmptext = '';
      if(!empty($arr)) { 
          $tmptext='<div id="postRecipientsList">
          <ul>';
      	foreach ($arr as $row) {
      		$tmptext .= '<li>'.$user->showAvatar($row[1]).'<a class="recLink" href="?k=fpost&filtr=1&prokoho='.$row[2].'">'.$row[2].' ['.$row[0].']</a><br /></li>';
      	}
      	$tmptext.='</ul></div>';
      }
      return($tmptext);
    }
    static function rh_login() {
    	Global $user;
    	if($user->idkontrol) {
    	    $tpl = new fTemplateIT('sidebar.user.logged.tpl.html');
			$tpl->setVariable('AVATAR',$user->showAvatar(-1,array('noTooltip'=>1)));
			$tpl->setVariable('NAME',$user->gidname);
			$tpl->setVariable('ONLINE',fSystem::getOnlineUsersCount());
			$recentEvent = $user->getDiaryCnt();
			if($recentEvent>0) $tpl->setVariable('DIARY',$recentEvent);
			if($user->gnpost()) {
			    $tpl->setVariable('NEWPOST',$user->newPost);
			    $tpl->setVariable('NEWPOSTFROMNAME',$user->newPostFrom);
			}
    	} else {
    		
    		$tpl = new fTemplateIT('sidebar.user.login.tpl.html');
    		$tpl->setVariable('FORMACTION',$user->getUri());
    		if(REGISTRATION_ENABLED == 1) $tpl->touchBlock('reglink');
    	}
    	
    	$ret = $tpl->get();
    	return $ret;
    }
    static function rh_datum() {
      global $conf,$user,$DAYS;
      if(!$ret = $user->cacheGet('datelefthand')) {
      	include(ROOT.$conf['language']['path'].'calendar.php');
      	$tpl = new fTemplateIT('sidebar.today.tpl.html');
      	$tpl->setVariable('DAYWORD',$DAYS[Date("D")]);
      	$tpl->setVariable('DATE',Date('d.'.'m.'.'Y'),$ret);
      	$tpl->setVariable('TIME',date("H:i"),$ret);
      	$tpl->setVariable('DAYINYEAR',(Date('z')+1),$ret);
      	$tpl->setVariable('NAMEDAY',$mesic[(int)Date("m")][Date("j")],$ret);
      	$ret = $tpl->get();
      	$user->cacheSave($ret);
      }
    	return($ret);
    }
    static function rh_akce_rnd() {
      global $db,$conf,$user;
      if(!$data = $user->cacheGet('eventtip')) {
        $fItems = new fItems();
        $fItems->initData('event',false,true);
        $fItems->addWhere('dateStart >= NOW()');
        $fItems->setOrder('rand()');
        $fItems->getData(0,1);
        $row = $fItems->pop();
        
      	if(!empty($row)) {
      	    $tpl = new fTemplateIT('sidebar.event.tpl.html');
      		if($row['enclosure']!="") {
      	    $flyerFilenameThumb = fEvents::thumbUrl($row['enclosure']);
            $flyerFilename = fEvents::flyerUrl($row['enclosure']);
      			if(file_exists($flyerFilename)){
      				$arr = getimagesize($flyerFilename);
              $tpl->setVariable('FLYERURL',$flyerFilename.'?height='.($arr[1]+20).'&width='.($arr[0]+20));
      				$tpl->setVariable('FLYERNAME',$row['addon']);
      				$tpl->setVariable('FLYERTHUMBURL',$flyerFilenameThumb);
      			}
      		}
      		
      		$tpl->setVariable('NAME',$row['addon']);
      		$tpl->setVariable('DATELOCAL',(($row['startTime']!='00:00:00')?(' '.$row['startTime']):('')).$row['startDateLocal']);
      		$tpl->setVariable('DATEISO',$row['startDateIso']);
      		if($row['location']!='') $tpl->setVariable('PLACE',$row['location']);
      		$tpl->setVariable('EVENTURL','?i='.$row['itemId']);
      		$data = $tpl->get();
      		$user->cacheSave($data);
      	}
      }
      return $data;
    }
    //---odpid je nastaveno jen kdyz se hlasuje
    static function rh_anketa($ankid=0,$odpid=0,$oUser=false,$calledFromXajax=false) {
    	global $db,$user;
    	if($user->idkontrol) { ///anketa je jen pro registrovany
    	if(!is_object($user) && $oUser) $user = $oUser;
    	
    	if(isset($_GET['poll']) && $user->idkontrol) {
        $arrGet = explode(";",$_GET['poll']);
        if($ankid==0) $ankid = $db->getOne("SELECT pollId FROM sys_poll WHERE activ=1 AND pageId='".$user->currentPageId."'");
        if($arrGet[0]==$ankid) $odpid = $arrGet[1];
      }
    	if ($odpid > 0) {
    	  $user->cacheRemove('poll');
    	}
    	
    	if(!$data = $user->cacheGet('poll')) {
      	$data = '';
      	$arrVoted = array();
 	
      	if($ankid == 0) $do=$db->getRow("SELECT pollId,question,votesperuser FROM sys_poll WHERE activ=1 AND pageId='".$user->currentPageId."'");
      	else $do=$db->getRow("SELECT pollId,question,votesperuser FROM sys_poll WHERE pollId=".$ankid);
      	if(!empty($do))	{
      		$voted=false;
      		$arrVoted = $db->getCol("SELECT pollAnswerId FROM sys_poll_answers_users WHERE pollId=".$do[0]." AND userId=".$user->gid);
      		
      		if(($do[2]-count($arrVoted))<1) $voted = true;
      		//---write wote
      		if (!empty($odpid) && $user->idkontrol && !$voted){
      			$db->query("INSERT INTO sys_poll_answers_users (pollId,pollAnswerId,userId) VALUES ('".$ankid."','".$odpid."','".$user->gid."')");
      			$arrVoted = $db->getCol("SELECT pollAnswerId FROM sys_poll_answers_users WHERE pollId=".$do[0]." AND userId=".$user->gid);
      		}
      		$restVotes = $do[2]-count($arrVoted);
      		if($restVotes<1) {
      			$restVotes = 0;
      			$voted = true;
      		}
      		if(!empty($do)) {
      			//odpovedi
      			$vv=$db->getAll("SELECT pollAnswerId, answer FROM sys_poll_answers WHERE pollId = ".$do[0]." ORDER BY ord");
      			if($voted || !empty($arrVoted)) {
      				//pocet odpovedi
      				$pocet=$db->getOne("SELECT count(1) FROM sys_poll_answers_users WHERE pollId=".$do[0]);
      				//klik
      				$vk=$db->getAll("SELECT pollAnswerId,count(1) AS soucet FROM sys_poll_answers_users WHERE pollId = ".$do[0]." GROUP BY pollAnswerId ORDER BY pollAnswerId");
      				foreach($vk as $row) $sc[$row[0]]=$row[1];
      			}
      			/* ........... viditelna cast ........*/
      			
      			$tpl = new fTemplateIT('sidebar.poll.tpl.html');
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
      				    $tpl->setVariable('ANSWERURL','?k='.$user->currentPageId.'&poll='.$do[0].':'.$odp[0]);
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
      		
      		$user->cacheSave($data);
      	}
      }
      if($calledFromXajax==false && !empty($data)) $data = '<div id="poll">'.$data.'</div>';
      return $data;
      }
    }
    static function rh_galerie_rnd(){
    	global $user;
  	  if(!$data = $user->cacheGet('fotornd')) {
  	    $fItems = new fItems();
  	    
  	    $fItems->openPopup = true;
        $fItems->showPageLabel = false;
        $fItems->showTooltip = true;
  		  $fItems->showTag = true;
  		  $fItems->showText = true;
  		  $fItems->thumbInSysRes = true;
  	    
  	    $fItems->initData('galery',false,true);
  	    
        $total = $fItems->getCount();

  	    //$fItems->initData('galery',$user->gid,true);
  	    

        $fItems->setLimit(rand(0,$total),1);
        $fItems->getData();
        
        $fItems->parse();
      	if(!$data = $fItems->show()) $data='';
      	
      	$user->cacheSave($data);
    	}
    	return $data;
    }
    static function rh_audit_popis(){
    	global $db,$user;
    	if(!$ret = $user->cacheGet('forumdesc')) {
      	$ret['klub'] = $db->getRow("SELECT userIdOwner,description FROM sys_pages WHERE pageId='".$user->currentPageId."'");
      	if(!DB::iserror($ret['klub'])){
      		$ret['admins'] = $db->getCol("SELECT userId FROM sys_users_perm WHERE rules=2 and pageId='".$user->currentPageId."'");
      	}
      	$user->cacheSave(serialize($ret));
    	} else {
        $ret = unserialize($ret);
      }
    	$klub = $ret['klub'];
      $admins = $ret['admins'];
    	$ret = '';
      if(!empty($klub)) {
        $tpl = new fTemplateIT('sidebar.page.description.tpl.html');
    		$tpl->setVariable('DESCRIPTION',$klub[1]);
    		$tpl->setVariable('OWNERAVATAR',$user->showAvatar($klub[0]));
        if(!empty($admins))
    		foreach ($admins as $adm) {
          $tpl->setCurrentBlock('otheradminsavatars');
          $tpl->setVariable('SMALLADMINAVATAR',$user->showAvatar($adm));
          $tpl->parseCurrentBlock();
        }
        $ret = $tpl->get();
      }
    	return $ret;
    }
    static function rh_logged_list(){
    	global $db,$user;
    	if(!$ret = $user->cacheGet('loggedlist')) {
    	  $ret = '';
      	$arrpra=$db->getAll("SELECT f.userIdFriend,SEC_TO_TIME(TIME_TO_SEC(now())-TIME_TO_SEC(l.dateUpdated)) as casklik FROM sys_users_logged as l INNER JOIN sys_users_friends as f ON f.userIdFriend=l.userId  WHERE subdate(NOW(),interval ".USERVIEWONLINE." minute)<l.dateUpdated AND f.userId=".$user->gid." AND f.userIdFriend!='".$user->gid."' GROUP BY f.userIdFriend ORDER BY casklik");
      	if (count($arrpra)>0){
      		$ret.='<p class="loggedUsersList">
          <ul>';
      		foreach ($arrpra as $pra){
      		    $kde = $user->getLocation($pra[0]);
      			$username = $user->getgidname($pra[0]);
      			$ret.='<li><a href="?k=fpost&who='.$pra[0].'">'.$username.'</a>
      			<a href="?k='.$kde['pageId'].$kde['param'].'" title="'.$kde['name'].'">'.$kde['nameshort'].' ['.substr($pra[1],3,5).']</a></li>';
      		}
      	 $ret.='</ul>
         </p>';
      	}
      	$user->cacheSave($ret);
    	}
    	return($ret);
    }

    static function rh_diar_kalendar($year='',$month='') {
    	global $db,$user,$MONTHS,$DAYSSHORT;
    	$dden = 1;
    	if(!empty($_REQUEST['ddate'])) {
    		list($drok,$dmesic,$dden)=explode("-",$_REQUEST['ddate']);
    	} 
    	if($year!='' || $month!='')  {
    	  $drok = $year;
    	  $dmesic = $month;
    	  $xajax = true;
    	} else $xajax = false;
    	
    	if(empty($drok) || !checkdate($dmesic,$dden,$drok)) {
    		$dmesic = date("m");
    		$drok = date("Y");
    		$dden = date("j");
    	}
    	
      //---cache by drok,dmesic
      $data = $user->cacheGet('calendarlefthand',$drok.$dmesic);
      
      if(!$data) {
      
        $cisden=array("Mon"=>"1","Tue"=>"2","Wed"=>"3","Thu"=>"4","Fri"=>"5","Sat"=>"6","Sun"=>"7");
      	$scas=mktime(0,0,0,$dmesic,1,$drok);
      	$dentydnu=$cisden[date("D",$scas)];
      	$dnumesice=date("t",$scas);
      	$z=1;
      	$den=1;
      	$hor=7;
      	$ver=6;
      	$ver=ceil((($dentydnu-1)+$dnumesice)/$hor);
      
      	$getpodm=BASESCRIPTNAME.'?k='.$user->currentPageId;
      
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
      	
      	if($user->currentPage['typeId']=='top') {
  	    	$arrUsedPlugins = array(
  	    	'diaryItems',
  	    	'diaryRecurrenceItems',
  	    	'events',
  	    	'blogItems',
  	    	'galeryItems',
  	    	'forums'
  	    	);
  	    	$userPageId = false;
  	    } elseif($user->currentPage['typeId']=='event') {
  	        $arrUsedPlugins = array(
  	    	'diaryItems',
  	    	'diaryRecurrenceItems',
  	    	'events',
  	    	);
  	        $userPageId = false;
      	} else {
      	  $userPageId = true;
      		//---just items for given page
      		$typeId = $user->currentPage['typeId'];
      		if($typeId=='blog') $arrUsedPlugins = array('blogItems');
      	}
      	$arrQ = array();
      	foreach ($arrUsedPlugins as $pluginName) {
      		$arrTmp = fCalendarPlugins::$pluginName($drok,$dmesic,$user->gid,($userPageId==true)?($user->currentPageId):(''));
      		if(!empty($arrTmp)) $arrQ = array_merge($arrQ,$arrTmp);
      	}
      	$arrEventsForDay = array();
      	$arrEventForDayKeys = array();
      	foreach ($arrQ as $row){
      		$arrEventsForDay[$row[0]][] = array('id'=>$row[2],'name'=>$row[3],'link'=>$row[1]);
      	}
      	$arrEventForDayKeys = array_keys($arrEventsForDay);
      	$tpl = new fTemplateIT('sidebar.calendar.tpl.html');
      	$tpl->setVariable('CURRENTMONTH',$MONTHS[$dmesic]);
      	$tpl->setVariable('CURRENTYEAR',$drok);
      	for ($x=1;$x<=$hor;$x++) {
      	    $tpl->setCurrentBlock('daysheader');
      	    $tpl->setVariable('DAYSHORTCUT',$DAYSSHORT[$x]);
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
      	$tpl->setVariable('PREVIOUSMONTHURL',$user->getUri(sprintf("ddate=%04d-%02d-%02d",$yearbefore,$monthbefore,$daybefore)));
      	$tpl->setVariable('XYEARPREV',$yearbefore);
      	$tpl->setVariable('XMONTHPREV',$monthbefore);
      	$tpl->setVariable('PREVIOUSMONTH',$MONTHS[$monthbefore]);
      	$tpl->setVariable('NEXTMONTHURL',$user->getUri(sprintf("ddate=%04d-%02d-%02d",$yearafter,$monthafter,$dayafter)));
      	$tpl->setVariable('XYEARNEXT',$yearafter);
      	$tpl->setVariable('XMONTHNEXT',$monthafter);
      	$tpl->setVariable('NEXTMONTH',$MONTHS[$monthafter]);
      	
      	foreach ($arrEventsForDay as $k=>$day) {
          foreach ($day as $event) {
            $tpl->setCurrentBlock('event');
            $tpl->setVariable('EVENTLINK',$event['link']);
            $tpl->setVariable('EVENTLABEL',$event['name']);
            $tpl->parseCurrentBlock();
          }
          $tpl->setCurrentBlock('eventday');
          $tpl->setVariable('DAYID','day'.($k*1));
          $tpl->parseCurrentBlock();
        }
      	$data = $tpl->get();
      	
      	$user->cacheSave($data);
      	
      }
    	
    	if($xajax==true) return $data;
    	else return '<div id="fcalendar">'.$data.'</div>';
    }

}