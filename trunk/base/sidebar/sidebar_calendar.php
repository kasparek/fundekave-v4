<?php
class sidebar_calendar {

	static function show($year='',$month='',$coreOnly=false) {
		
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
  	    	'diaryRecurrenceItems',
  	    	'events',
  	    	'blogItems',
  	    	'galeryItems',
  	    	//'forums'
  	    	);
  	    	$userPageId = false;
			} elseif($user->pageVO->typeId == 'event') {
				$arrUsedPlugins = array(
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
				
				$outList = array();
				foreach($arrQ as $item) {
					$outList[] = '<div class="event hidden" data-date="'.$item[2].'" data-repeat="'.(!empty($item[4])?$item[4]:'').'" data-id="'.$item[0].'">'
					.($coreOnly ? '' : '<span class="date">'.$item[3].'</span>')
					.$item[1].'</div>';
				}
				
				if(!empty($outList)) {
					$data = implode("\n",$outList);
				}
				$cache->setData( $data );
			}
		}
		if($coreOnly===false && !empty($data)) $data = '<div id="calendar-inline">'.$data.'</div>';
		return $data;
	}

}