<?php
class FProfiler {
	static function getmicrotime(){
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

	static function profile($comment='',$group=false) {
		$arr = array('group'=>$group,'comment'=>$comment,'time'=>FProfiler::getmicrotime(),'memUsage'=>round(memory_get_usage()/1024),'memPeak'=>round(memory_get_peak_usage()/1024));
		$cache = FCache::getInstance('l');
		$cachedArr = $cache->getData('profile','FSystem');
		if($cachedArr===false) $cachedArr = array();
		$cachedArr[] = $arr;
		$cache->setData($cachedArr);
	}
	
	static function profileLog() {
		$cache = FCache::getInstance('l');
		$statArr = $cache->getdata('profile','FSystem');
		$text = '';
		$total = 0;
		$startTime = $statArr[0]['time'];
		$lastTime = $startTime;
		$lastMemUsage = 0;
		foreach($statArr as $profil) {
			if($profil['group']==true) {
				if(!isset($groupLastTime)) $groupLastTime = $lastTime;
				$profil['timeDif'] = round($profil['time']-$groupLastTime,4);
				$arrGroup[$profil['comment']][] = $profil;
				$groupLastTime = $profil['time'];
			} else {
				$text .= round($profil['time']-$lastTime,4) . ' :: ' . round($profil['time']-$startTime,4)
				. ' :: ' . ($profil['memUsage']-$lastMemUsage)
				. ' :: ' . $profil['comment'] 
				. ' :: ' . $profil['memUsage']. ' :: ' .$profil['memPeak']. "\n";
				$lastTime = $profil['time'];
				$lastMemUsage = $profil['memUsage'];
			}
		}
		$text .= "\n---GROUPED----\n\n";
		
		if(isset($arrGroup)) {
			foreach($arrGroup as $k=>$v) {
				$times = 0;
				$timeTotal = 0;
				foreach($v as $profil) {
					$timeTotal = $timeTotal + $profil['timeDif'];
					$times++;
				}
				$time = round($timeTotal/$times,4);
				$text .= $time
				. ' :: ' . $k 
				. "\n";
			}
		}
		$total = $lastTime-$startTime;
		file_put_contents(ROOT.'tmp/FSystem-profile-times.log','Total time:'.$total."\n".$text);
		$cache->invalidatedata('profile','FSystem');
	}
}