<?php
class rh_diar_kalendar {

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
	//TODO: refactor links - fajax
	static function show($year='',$month='') {
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
				
				$tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
				$tpl->loadTemplatefile('sidebar.calendar.tpl.html');
				
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