<?php
class FProfiler {
	private static $logList;
	private static $starttimeList;
	private static $lasttimeList;
	private static $truncateList;
	
	private static $handleIfEmpty=-1;
	
	static function init($filename,$flush=true) {
		if(!is_array(self::$logList)) self::$logList = array();
		$handle = count(self::$logList);
		self::$logList[] = $filename;
		self::$truncateList[$handle] = $flush;
		list($usec, $sec) = explode(" ",microtime());
		self::$starttimeList[$handle] = self::$lasttimeList[$handle] = ((float)$usec + (float)$sec);
		FProfiler::write('INITIALIZED',$handle);
		return $handle;
	}

	static function write($comment='',$handle=-1,$timeStart=-1) {
		list($usec, $sec) = explode(" ",microtime());
		$now = ((float)$usec + (float)$sec);
		if($timeStart>-1) self::$starttimeList[$handle] = $timeStart;
		//initialize
		if($handle==-1) {
			if(self::$handleIfEmpty>-1) {
				$handle = self::$handleIfEmpty;
			} else {
				//DEFAULT PROFILE LOG
				if(!is_array(self::$logList)) self::$logList = array();
				$handle = self::$handleIfEmpty = count(self::$logList);
				//delete profile file
				self::$logList[] = FConf::get('settings','logs_path').'time.log';
				self::$truncateList[$handle] = true;
				self::$starttimeList[$handle] = self::$lasttimeList[$handle] = $now;
			}
		}
				
		//write log entry
		$fh = fopen(self::$logList[$handle].(!empty($_REQUEST['m'])?'.'.$_REQUEST['m'].'.log':''), "ab+" );
		if(!$fh) {
			FError::write_log('FProfiler::write - CANNOT OPEN LOG TO WRITE - '.self::$logList[$handle]);
			return;
		}
		if(self::$truncateList[$handle]) {
			ftruncate($fh,0);
			self::$truncateList[$handle]=false;
		}
		$data = date(DATE_ATOM)
		.';runtime='.( round($now-self::$starttimeList[$handle],4) )
		.';timelast='.( round($now-self::$lasttimeList[$handle],4) )
		.';mem='.round(memory_get_usage()/1024)
		.'/'.round(memory_get_peak_usage()/1024)
		."\n".$comment."\n\n";
		fwrite($fh,$data);
		fclose($fh);
		self::$lasttimeList[$handle] = $now;
	}
}