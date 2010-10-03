<?php
class FProfiler {
	private static $logList;
	private static $starttimeList;
	private static $lasttimeList;
	
	private static $handleIfEmpty;

	static function init($filename,$flush=true) {
		if(!is_array(self::$logList)) self::$logList = array();
		$handle = count(self::$logList);
		self::$logList[] = $filename;
		if(file_exists(self::$logList[$handle])) unlink(self::$logList[$handle]);
		FProfiler::write('INITIALIZED',$handle);
		return $handle;
	}

	static function write($comment='',$handle=-1,$timeStart=-1) {
		list($usec, $sec) = explode(" ",microtime());
		$now = ((float)$usec + (float)$sec);
		if($timeStart>-1) self::$starttimeList[$handle] = $timeStart;
		//initialize
		if($handle==-1) {
			if(!empty(self::$handleIfEmpty)) {
				$handle = self::$handleIfEmpty;
			} else {
				//DEFAULT PROFILE LOG
				if(!is_array(self::$logList)) self::$logList = array();
				self::$handleIfEmpty = count(self::$logList);
				//delete profile file
				self::$logList[] = FConf::get('settings','logs_path').'System-profile-times.log';
				if(file_exists(self::$logList[self::$handleIfEmpty])) unlink(self::$logList[self::$handleIfEmpty]);
				self::$starttimeList[self::$handleIfEmpty] = self::$lasttimeList[self::$handleIfEmpty] = $now;
			}
		}
		//write log entry
		$fh = fopen(self::$logList[$handle], "a");
		fwrite($fh,date(DATE_ATOM)
		.';runtime='.( round($now-self::$starttimeList[$handle],4) )
		.';timelast='.( round($now-self::$lasttimeList[$handle],4) )
		.';mem='.round(memory_get_usage()/1024)
		.'/'.round(memory_get_peak_usage()/1024)
		."\n".$comment."\n\n");
		fclose($fh);
		self::$lasttimeList[$handle] = $now;
	}
}