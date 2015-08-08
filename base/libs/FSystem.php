<?php
class FSystem {
	
	static function recaptchaGet($error) {
		require_once(ROOT."ext/recaptchalib.php");
		return recaptcha_get_html("6LexXNkSAAAAAE_BDWQHhapdx-XPHItdWgBvDTSm", $error);
		return '';
	}
	
	static function recaptchaCheck($data) {
		if ($data["recaptcha_response_field"]) {
			require_once(ROOT."ext/recaptchalib.php");
			$resp = recaptcha_check_answer ("6LexXNkSAAAAAHke6ktw0hSwYha8x4N4Bn9M2vFm",$_SERVER["REMOTE_ADDR"],$data["recaptcha_challenge_field"],$data["recaptcha_response_field"]);
			return $resp->is_valid ? true : $resp->error;
		}
		return false;
	}
  
	//---close resources
	static function fin($msg='',$log=false) {
		FSystem::superInvalidateFlush();
		$db = FDBConn::getInstance();
		$db->close();
		if($log) FError::write_log($log);
		die($msg);
	}

	static function strlenSort($a,$b){
		if($a==$b)return 0;
		$la=strlen($a);
		$lb=strlen($b);
		if($la==$lb)return 0;
		if($la>$lb)return 1;
		return -1;
	}

	private static $invalidate = array();

	static function superInvalidate($grp,$id='') {
		$serialized = $grp.($id!=''?'|'.$id:'');
		if(!in_array($serialized,self::$invalidate)) self::$invalidate[] = $serialized;
	}

	static function superInvalidateFlush() {
		if(empty(self::$invalidate)) return;
		FProfiler::write('FSystem::superInvalidateFlush start 1');
		$cacheDir = FConf::get('settings','tmp') . FConf::get('settings','cache_path');
		$cacheDir = rtrim($cacheDir, "/");
		//remove trailing slash
		$ff = new FFile();
		$ff->rename($cacheDir, $cacheDir.'_'.date("U").'_delete_');
		//$ff->rm_recursive($cacheDir);
		FProfiler::write('FSystem::superInvalidateFlush complete');
	}

	static function superInvalidateHandle($grps){
		if(empty($grps))return;
		$cache = FCache::getInstance('f');
		if(!is_array($grps)) $grps = explode(';',$grps);
		foreach($grps as $grp) {
			$inv = explode('|',$grp);
			if(count($inv)>1) $cache->invalidateData($inv[1],$inv[0]);
			else $cache->invalidateGroup($inv[0]);
		}
	}
	
	private static $superVars = array();

	static function addSuperVar($key,$val) {
		self::$superVars['[['.$key.']]'] = $val;
	}
	
	static function superVars($data) {
		$superVars = array();
		$settings = FConf::get('settings');
		foreach($settings as $k=>$v) $superVars['[['.strtoupper($k).']]'] = $v;
		$defined = FConf::get('phpdefined');
		foreach($defined as $k=>$v) $superVars['[['.strtoupper($k).']]'] = $v;
		
		$superVars['[[URL_CSS]]'] = (strpos(URL_CSS,'http://')===false)?STATIC_DOMAIN.URL_CSS:URL_CSS;
		$superVars['[[URL_JS]]'] = (strpos(URL_JS,'http://')===false)?STATIC_DOMAIN.URL_JS:URL_JS;
		$superVars['[[ASSETS_URL]]'] = (strpos(URL_ASSETS,'http://')===false)?STATIC_DOMAIN.URL_ASSETS:URL_ASSETS;
		
		$superVars = array_merge($superVars,self::$superVars);
		return str_replace(array_keys($superVars),$superVars,$data);
	}

	//TEMPLATE HELPER
  static function isTpl($templatefile,$root='') {
    if($root == '') $root = ROOT.ROOT_TEMPLATES;
    if(file_exists($root.$templatefile)) return true;
    return false;
  }
	static function tpl($templatefile,$root = '', $removeUnknownVariables=true, $removeEmptyBlocks=true){
		if($root == '') $root = ROOT.ROOT_TEMPLATES;
		$tpl = new FHTMLTemplateIT($root);
    if(FSystem::isTpl(HOME_PAGE.'.'.$templatefile,$root)) {
      $templatefile = HOME_PAGE.'.'.$templatefile; 
    }
		$tpl->loadTemplatefile($templatefile, $removeUnknownVariables, $removeEmptyBlocks);
		return $tpl;
	}

	static function tplExist($template) {
		return file_exists(ROOT.ROOT_TEMPLATES.$template);
	}

	/**
	 * Build local path for redirects, buttons, etc.
	 * @param $otherParams
	 * @param $pageId
	 * @param $pageParam
	 * @param array $options - scriptname, short
	 * @return string - URL
	 */
	static function getUri($otherParams='', $pageId='', $pageParam=false, $options=array()) {
		$arrAcnchor = explode('#',$otherParams);
		$otherParams = $arrAcnchor[0];
		$anchor = '';
		if(isset($arrAcnchor[1])) $anchor = '#' . $arrAcnchor[1];
		$user = FUser::getInstance();
		$pageParam = ($pageParam===false)?($user->pageParam):($pageParam);

		$newPageId = '';
		if($user->pageVO) $newPageId = $user->pageVO->pageId;
		if(!empty($pageId)) $newPageId = $pageId;
		if(!isset($options['short']) && $newPageId == HOME_PAGE && empty($pageParam)) $newPageId = '';

		if(preg_match("/i=([0-9]*)/" , $otherParams)) {
			if(empty($pageParam)) $newPageId = '';
		} else {
			if( empty($pageId) && $user->itemVO ) {
				$params['i'] = $user->itemVO->itemId;
				if(empty($pageParam)) $newPageId = '';
			}
		}
		if(!empty($newPageId)) {
			if(!isset($options['short']) && empty($pageParam)) {
				$pageVO  = FactoryVO::get('PageVO',$newPageId,true);
				$safeName = FText::safetext($pageVO->name);
			}
			if(isset($options['name'])) $safeName = FText::safetext($options['name']);
			$params['k'] = $newPageId . $pageParam . ((!empty($safeName))?('-'.$safeName):(''));
			$params = array_reverse($params);
		}
		if(!empty($otherParams)) {
			$op = explode('&',$otherParams);
			while($p = array_shift($op)) {
				$eqpos = strpos($p,'=');
				$params[substr($p,0,$eqpos)] = substr($p,$eqpos+1);
			}
		}
		$parStr = '';
		$script = BASESCRIPTNAME;
		$rewrite = false;
		if(!empty($params)) {
			while($k = key($params)) {
				$v = array_shift($params);
				if($rewrite) {
					if($k=='i') {
						$script = 'item-'.$v.'.html';
					} elseif($k=='k') {
						$c='';
						if(isset($params['c'])) {
							$c='-category-'.$params['c'];
							unset($params['c']);
						}
						$script = 'page-'.$v.$c.'.html';
					} else {
						$parStr.= $k.'='.$v.($params?'&':'');
					}
				} else {
					$parStr.= $k.'='.$v.($params?'&':'');
				}
			}
			$parStr = ($parStr!=''?'?'.$parStr:'');
		}
		$url = $script . $parStr . $anchor;
		return $url;
	}

	static function processK($pageId) {
		if(isset($pageId{5})) {
			//---remove the part behind - it is just nice link
			if(false!==($pos=strpos($pageId,'-'))) {
				$pageId = substr($pageId,0,$pos);
			}
			//---slice pageid on fiveid
			if(isset($pageId{5})) {
				$user = FUser::getInstance();
				$user->pageParam = substr($pageId,5);
				$pageId = substr($pageId,0,5);
			}
		}
		return $pageId;
	}

	static function checkDate($date) {
		$date = FText::preProcess($date,array('plainText'=>1));
		$arr = explode(' ',$date);//get time part
		$date = $arr[0];
		$time = '';
		if(isset($arr[1])) $time = FSystem::isTime($arr[1]) ? ' '.$arr[1] : '';
			
		if(strpos($date,'.')) {
			$arr = explode('.',$date);
			if(count($arr)!=3) return null;
			$date = $arr[2].'-'.$arr[1].'-'.$arr[0];
		}
		$date .= $time;
		$dateList = explode('-',$date);
		$dateListLen = count($dateList);
		if($dateListLen>3) return null;
		else if($dateListLen==3) $dateTest = $date;
		else if($dateListLen==2) $dateTest = $date.'-01';
		else if($dateListLen==1) $dateTest = $date.'-01-01';
		if(strtotime($dateTest)===false) return null;
		return $date;
	}

	static function isTime($time) {
		$arrTime = explode(':',$time);
		if (count($arrTime) == 2 || count($arrTime) == 3) {
			if($arrTime[0] > 23 && $arrTime[0]<0) return false;
			if($arrTime[1] > 60 && $arrTime[1]<0) return false;
			if(isset($arrTime[2])) {
				if($arrTime[2] > 60 && $arrTime[2]<0) return false;
			}
		}
		return false;
	}

	function ip2num($ip) {
		$arip=explode(".",$ip);
		$numip=sprintf ("%03d%03d%03d%03d", $arip[0], $arip[1], $arip[2],$arip[3]);
		return($numip);
	}

	static function getUserIp() {
		return $_SERVER["REMOTE_ADDR"]
		."@" .(isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : '')
		."@" .(isset($_SERVER["HTTP_FORWARDED"]) ? $_SERVER["HTTP_FORWARDED"] : '')
		."@" .(isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"] : '')
		."@" .(isset($_SERVER["X_HTTP_FORWARDED_FOR"]) ? $_SERVER["X_HTTP_FORWARDED_FOR"] : '');
	}

	static function array_neighbor($key, $arr, $consecutively = false) {
		//$keys = array_keys($arr); --- when key is key
		$keys = $arr; //--- when key is value
		$keyIndexes = array_flip($keys);
		$return = array();
		//--- previous
		if (isset($keys[$keyIndexes[$key]-1])) {
			$return['prev'] = $keys[$keyIndexes[$key]-1];
		} else {
			if($consecutively) $return['prev'] = $keys[sizeof($keys)-1]; else $return['prev'] = 0; //--- if not previous return last
		}
		//--- next
		if (isset($keys[$keyIndexes[$key]+1])) {
			$return['next'] = $keys[$keyIndexes[$key]+1];
		} else {
			if($consecutively) $return['next'] = $keys[0]; else $return['next'] = 0; //--- if not next return first
		}
		return $return;
	}
        
  static function curl_get_file_contents($URL) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);
        return $contents ? $contents : false;
  }

	static function positionProcess($dataStr) {
		$positionData = trim($dataStr);
		if(empty($positionData)) return null;
		$posList = explode("\n",$positionData);
		foreach($posList as $pos) {
			$latLng = explode(',',$pos);
			if(count($latLng)==2) {
				$lat = trim($latLng[0])*1;
				$lng = trim($latLng[1])*1;
				if($lat!=0 && $lng!=0) $dataChecked[] = $lat.','.$lng;
			}
		}
		if(empty($dataChecked)) return null;
		return implode(';',$dataChecked);
	}

	static function journeyLength($data) {
		$dataList = explode(';',$data);
		$distance = 0;
		for($i=1;$i<count($dataList);$i++) {
			list($lat1,$lng1) = explode(',', $dataList[$i-1]);
			list($lat2,$lng2) = explode(',', $dataList[$i]);
			$distance += FSystem::distance($lat1,$lng1,$lat2,$lng2);
		}
		return round($distance,2);
	}


	static function distance($lat1, $lon1, $lat2, $lon2) {
		$earth_radius = 3440;
		$delta_lat = $lat2 - $lat1 ;
		$delta_lon = $lon2 - $lon1 ;
		/*
		 //Spherical Law of Cosines
		 $distance  = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($delta_lon)) ;
		 $distance  = acos($distance);
		 $distance  = rad2deg($distance);
		 $distance  = $distance * 60 * 1.1515;
		 */
		//Haversine Formula
		$alpha    = $delta_lat/2;
		$beta     = $delta_lon/2;
		$a        = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($beta)) * sin(deg2rad($beta)) ;
		$c        = asin(min(1, sqrt($a)));
		$distance = 2*$earth_radius * $c;
		return $distance;
	}

	static function isRobot() {
		if(empty($_SERVER['HTTP_USER_AGENT'])) return false;
		$bot_list = array('google',"yahoo","teoma", "alexa", "froogle", "gigabot", "inktomi",
"looksmart", "url_spider_sql", "firefly", "nationaldirectory",
"ask jeeves", "tecnoseek", "infoseek", "webfindbot", "girafabot",
"crawler", "www.galaxy.com", "googlebot", "scooter", "slurp",
"msnbot", "appie", "fast", "webbug", "spade", "zyborg", "rabaz",
"baiduspider", "feedfetcher-google", "technoratisnoop", "rankivabot",
"mediapartners-google", "sogou web spider", "webalta crawler","google","rambler","abachobot","accoona",
"acoirobot",'msnbot','rambler','yahoo','abachobot','accoona','acoirobot','aspseek','croccrawler','dumbot','fast-webcrawler','geonabot','lycos','msrbot','altavista','idbot','estyle','scrubby'
);
$lowerAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
foreach ($bot_list as $bot)	if(strpos($lowerAgent,$bot)!==false) return true;
return false;
	}
}