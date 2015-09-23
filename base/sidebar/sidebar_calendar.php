<?php
class sidebar_calendar {

	static function show($options=null) {
		if($options===null) $options = new stdClass();

		//SELECT count(1),DATE_FORMAT(dateContent,"%Y-%m-%d") as date FROM `sys_pages` WHERE typeId='galery' group by date

		$user = FUser::getInstance();

		$drok = $user->year;
		$dmesic = $user->month;
		if(empty($drok)) $drok = date("Y");
		if(empty($dmesic)) $dmesic = date("m");
				
		//---cache by drok,dmesic
		$cache = FCache::getInstance('f',3600);
		$data = $cache->getData($user->pageVO->pageId.'-page-'.($user->categoryVO?$user->categoryVO->categoryId:'0').'-cat-'.($user->userVO->userId*1).'-user-'.$drok.$dmesic,'calendarlefthand');

		if(false===$data) {
			$userPageId = false;
			if($user->pageVO->typeIdChild == 'galery') {
				$arrUsedPlugins = array('galeryPageCount');
			} elseif($user->pageVO->typeId == 'top') {
				//$arrUsedPlugins = array('diaryRecurrenceItems','events','blogItems','galeryPageCount');
			} elseif($user->pageVO->typeId == 'event') {
				//$arrUsedPlugins = array('diaryRecurrenceItems','events');
			} else {
				$userPageId = true;
				//---just items for given page
				$typeId = $user->pageVO->typeId;
				//if($typeId=='blog') $arrUsedPlugins = array('diaryRecurrenceItems','events','blogItems');
				//if($typeId=='forum') $arrUsedPlugins = array('diaryRecurrenceItems','events');
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
					.(!empty($options->coreOnly) ? '' : '<span class="date">'.$item[3].'</span>')
					.$item[1].'</div>';
				}
				
				if(!empty($outList)) {
					$data = implode("\n",$outList);
				}
				$cache->setData( $data );
			}
		}
		$minViewMode = 1;
		if(isset($options->minViewMode)) $minViewMode = $options->minViewMode;
		if($user->year && $minViewMode>1) {
			$minViewMode=1;
		}
		if($user->month && $minViewMode>0) {
			$minViewMode=0;
		}
		if(empty($options->coreOnly) && !empty($data)) {
			$data = ($user->categoryVO?'<div class="text-center">Kategorie <strong>'.$user->categoryVO->name.'</strong></div>':'').'<div id="calendar-inline" data-minviewmode="'.$minViewMode.'" data-dateset="'.$user->inDate().'">'.$data.'</div>';
		}
		
		return $data;
	}

}