<?php
class sidebar_calendar {

	static function show($year='',$month='',$coreOnly=false) {
		
		$dden = 1;
		$date = null;
		if(!empty($_REQUEST['date'])) {
			$date = FSystem::checkDate($_REQUEST['date']);
			if($date) {
				list($drok,$dmesic,$dden)=explode("-",$date);
			}
		}
		
		if($year!='' || $month!='')  {
			$drok = $year;
			$dmesic = $month;
		} 
			
		if(empty($drok) || !checkdate($dmesic,$dden,$drok)) {
			$dmesic = date("m");
			$drok = date("Y");
		}
			
		//---cache by drok,dmesic
		$cache = FCache::getInstance('f',3600);
		$user = FUser::getInstance();
		$data = $cache->getData($user->pageVO->pageId.'-page-'.($user->userVO->userId*1).'-user-'.$drok.$dmesic,'calendarlefthand');

		if(false===$data) {
			if($user->pageVO->typeId == 'top') {
				$arrUsedPlugins = array('diaryRecurrenceItems','events','blogItems','galeryItems');
				$userPageId = false;
			} elseif($user->pageVO->typeId == 'event') {
				$arrUsedPlugins = array('diaryRecurrenceItems','events');
				$userPageId = false;
			} else {
				$userPageId = true;
				//---just items for given page
				$typeId = $user->pageVO->typeId;
				if($typeId=='blog') $arrUsedPlugins = array('diaryRecurrenceItems','events','blogItems');
				if($typeId=='galery') $arrUsedPlugins = array('galeryItems');
				if($typeId=='forum') $arrUsedPlugins = array('diaryRecurrenceItems','events');
			}
			$arrQ = array();
			if(!empty($arrUsedPlugins)) {
				foreach ($arrUsedPlugins as $pluginName) {
					$arrTmp = FCalendarPlugins::$pluginName($drok,$dmesic,(int) $user->userVO->userId,($userPageId==true)?($user->pageVO->pageId):(''));
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
		if($coreOnly===false && !empty($data)) $data = '<div id="calendar-inline" data-dateset="'.($date?$date:'').'">'.$data.'</div>';
		return $data;
	}

}