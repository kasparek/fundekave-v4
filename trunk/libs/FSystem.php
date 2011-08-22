<?php
class FSystem {

	//---close resources
	static function fin() {
		FSystem::superInvalidateFlush();
		$db = FDBConn::getInstance();
		$db->kill();
		exit;
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
		FProfiler::write('FSystem::superInvalidateFlush start');
		if(empty(self::$invalidate)) return;
		$grpList = self::$invalidate;

		//sort grpList
		usort($grpList, "FSystem::strlenSort");
		//remove all grps unnecessary to invalidate
		$i=0;
		while($i<count($grpList)){
			$grpListNew = array();
			foreach($grpList as $grp) if($grp==$grpList[$i] || strpos($grp,$grpList[$i])!==0)$grpListNew[]=$grp;
			$grpList=$grpListNew;
			$i++;
		}
		//remove duplicates
		$grpListNew = array();
		foreach($grpList as $grp) if(!in_array($grp,$grpListNew)) $grpListNew[]=$grp;
		$grpList=$grpListNew;
			
		$grps = implode(";",$grpList);
		self::$invalidate = array();
		$domains = array('fundekave.net','iyobosahelpinghand.com','awake33.com','eboinnaija.fundekave.net','upsidedown.fundekave.net');
		$domains[]='test.fundekave.net';
		$mh = curl_multi_init();
		$curlys=array();
		//prepare curl
		foreach($domains as $dom) {
			FProfiler::write('FSystem::superInvalidateFlush curl prepare');
			$url = 'http://'.$dom.'/index.php?cron=invalidate&g='.$grps;
			if($_SERVER['HTTP_HOST']==$dom) {
				FSystem::superInvalidateHandle($grps);
				FError::write_log("cron::invalidate - LOCAL COMPLETE - ".$grps);
			} else {
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL,            $url);
				curl_setopt($curl, CURLOPT_HEADER,         1);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
				curl_multi_add_handle($mh, $curl);
				$curly[] = $curl;
				FProfiler::write('FSystem::superInvalidateFlush curl prepare complete');
			}
		}
		//execute curl
		$active = null;
		do {
			$mrc = curl_multi_exec($mh, $active);
			FProfiler::write('FSystem::superInvalidateFlush curl executed');
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		//remove handles
		foreach($curly as $id => $c) curl_multi_remove_handle($mh, $c);
		//close curl
		curl_multi_close($mh);
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

	static function superVars($data) {
		$superVars = array(
		'SKIN'=>SKIN,
		'SKINURL'=>STATIC_DOMAIN.URL_CSS.SKIN,
		'STATIC_DOMAIN'=>STATIC_DOMAIN,
		'URL_JS'=>STATIC_DOMAIN.URL_JS,
		'ASSETS_URL'=>STATIC_DOMAIN.URL_ASSETS
		);
		foreach($superVars as $k=>$v) {
			$data = str_replace('[['.$k.']]',$v,$data);
		}
		return $data;
	}

	//TEMPLATE HELPER
	static function tpl($templatefile,$root = '', $removeUnknownVariables=true, $removeEmptyBlocks=true){
		if($root == '') $root = ROOT.ROOT_TEMPLATES;
		$tpl = new FHTMLTemplateIT($root);
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
				$safeName = FSystem::safetext($pageVO->name);
			}
			if(isset($options['name'])) $safeName = FSystem::safetext($options['name']);
			$params['k'] = $newPageId . $pageParam . ((!empty($safeName))?('-'.$safeName):(''));
			$params = array_reverse($params);
		}
		if(!empty($otherParams)) {
			$op = explode('&',$otherParams);
			while($p = array_shift($op)) {
				$parr = explode('=',$p);
				$params[$parr[0]] =$parr[1];
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

	/**
	 * option - 0 - remove html, safe text 1 - remove html, safe text, parse bb, 2- nothing - just trim
	 *
	 * @param input string $text
	 * @param numeric $option
	 * @param numeric $endOfLine - if 1 replace \n - <br />\n, 0 - do nothing, 2 - replace <br />\n - \n
	 * @param boolean $wrap - wrap long words
	 */
	static function textins($text,$paramsArr=array()) {

		$breakLong = 1;
		$endOfLine = 1;

		if(!is_array($paramsArr)) $paramsArr = array();
		if(!isset($paramsArr['formatOption'])) $paramsArr['formatOption']=1;

		if(isset($paramsArr['plainText'])) {
			$paramsArr['formatOption'] = 0;
			$endOfLine = 0;
		}

		$text = trim($text);
		$text = stripslashes($text);
		if($text=='') return '';

		$user = FUser::getInstance();
		if($paramsArr['formatOption']==0 || $user->idkontrol==false) {
			$text = strip_tags($text);
		}
			
		if($paramsArr['formatOption'] < 2) {
			require_once(ROOT.'pear/HTML/Safe.php');
			$safe = new HTML_Safe();
			if($user->idkontrol && $paramsArr['formatOption']>0) {
				$safe->deleteTags = array(
        'applet', 'base',   'basefont', 'bgsound', 'blink',  'body',
        'frame',  'frameset', 'head',    'html',   'ilayer',
        'layer',  'link',     'meta', 'style',
        'title',  'script',
				);
				$safe->attributes = array('dynsrc', 'id');
			}
			$text = $safe->parse($text);
		}

		if($endOfLine==1) {
			$br='<br />';
			$text = str_replace(array($br."\r\n",$br."\n"),array("\r\n","\r\n"),$text);
			$text = nl2br($text);
		}
		elseif($endOfLine==2) $text = FSystem::textinsBr2nl($text);
			
		if($breakLong==1) $text = FSystem::wordWrap($text);

		if(isset($paramsArr['lengthLimit'])) {
			if($paramsArr['lengthLimit'] > 0) {
				if(mb_strlen($text) > $paramsArr['lengthLimit']) { $text = mb_substr($text,0,$paramsArr['lengthLimit']);
				if(isset($paramsArr['lengthLimitAddOnEnd'])) $text .= $paramsArr['lengthLimitAddOnEnd'];
				}
			}
		}

		return $text;
	}

	static function wordWrap($strParam, $i=70, $wrap = "\n") {
		$str = strip_tags($strParam);
		$str = str_replace("\n",' ',$str);
		$arr = explode(' ',$str);
		foreach ($arr as $word) {
			$word=trim($word);
			if(strlen($word)>$i && strpos($word,'http')===false) {
				$arrRep[$word] = wordwrap( $word , $i , $wrap , 1);
			}

		}
		if(!empty($arrRep)) {
			foreach ($arrRep as $k=>$v) {
				$strParam = str_replace($k,$v,$strParam);
			}
		}
		return $strParam;
	}

	static function textinsBr2nl($text,$br='<br />') {
    return str_replace(array($br."\r\n",$br."\n"),array("&#10;","&#10;"),$text);
	}

	static function textToTextarea($text) {
		//return htmlspecialchars(FSystem::textinsBr2nl($text));
    
    //$text = str_replace('<br />','<br>',$text);
    //return htmlspecialchars($text);
    return FSystem::textinsBr2nl($text);
	}

	static function checkDate($date) {
		$date = FSystem::textins($date,array('plainText'=>1));
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
		if(strtotime($date)===false) return null;
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
		$IPadresa=$_SERVER["REMOTE_ADDR"]."@";
		if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) $IPadresa.=$_SERVER["HTTP_X_FORWARDED_FOR"];
		$IPadresa.="@";
		if(isset($_SERVER["HTTP_FORWARDED"])) $IPadresa.=$_SERVER["HTTP_FORWARDED"];
		$IPadresa.="@";
		if(isset($_SERVER["HTTP_CLIENT_IP"])) $IPadresa.=$_SERVER["HTTP_CLIENT_IP"];
		$IPadresa.="@";
		if(isset($_SERVER["X_HTTP_FORWARDED_FOR"])) $IPadresa.=$_SERVER["X_HTTP_FORWARDED_FOR"];
		return($IPadresa);
	}

	/**
	 * transliterate czech diacritic to ascii
	 *
	 * @param UTF-8 $text
	 * @return ASCII string
	 */
	static function safeText($text) {
		$url = $text;
		$url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
		$url = trim($url, "-");
		$url = iconv("UTF-8", "ASCII//TRANSLIT", $url);
		$url = strtolower($url);
		$url = preg_replace('~[^-a-z0-9_]+~', '', $url);
		return $url;
	}

	static function safeFilename($text) {
		$arr = explode('.',strtolower($text));
		$extension = FSystem::safeText(array_pop($arr));
		return FSystem::safeText(implode('.',$arr)).'.'.$extension;
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

	static function postText($text) {
		$text = trim($text);
		if(empty($text)) return $text;
		$text = ' '.$text;

		$regList = array(
		'a'=>"/(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|>|\<|$|\.\s)<\s*img\s*src=\"(http.*:[^\"]+\.[jpeg|jpg|png|gif]+)\".*>([^>]*)/i"
		,'b'=>"/(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|>|\<|$|\.\s)<\s*a\s*href=\"(http.*:[^\"]+\.[jpeg|jpg|png|gif]+)\"\s*>([^>]*)<\/a>/i"
		,'i'=>"/<img.*src=\"http.*:\/\/[0-9a-zA-Z.\/]*\/image\/[0-9]+x[0-9]+\/[a-z]{4}\/.*([a-zA-Z0-9]{5})\/([0-9a-zA-Z.-_]+jpg|png|gif+)([^\"]*+)\"[^<]+?>/i"//.*>/i"
    ,'i2'=>"/http.*:\/\/[0-9a-zA-Z.\/]*\/image\/[0-9]+x[0-9]+\/[a-z]{4}\/.*([a-zA-Z0-9]{5})\/([0-9a-zA-Z.-_]+(jpeg|jpg|png|gif)$)/i"
		,'c'=>"/<img src=\"http.*:\/\/[0-9a-zA-Z.\/]*\/data\/cache\/[0-9a-zA-Z-]*\/([0-9a-zA-Z]*)-[a-zA-Z0-9-_]*\/([^\"]*+)\"[^<]+?>/i"
		,'d'=>"/<\s*a\s*href=\"[^\"]+\/data\/cache\/[^\"]+\/([a-zA-Z0-9]{5})-[^\"]+\/([0-9a-zA-Z.]*\.jpg)\"\s*>[^>]*<\/a>/i"
		,'e'=>"/<\s*a\s*href=\"[^\"]+\/obr\/[^\"]+([a-zA-Z0-9]{5})\/([0-9a-zA-Z.]*\.jpg)\"\s*>[^>]*<\/a>/i"
		,'f'=>"#(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|>|\<|$|\.\s)((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#i"
		,'g'=>"/<\s*a\s*href=\"http.*:[^\"]+[&?|]i=([0-9]*)[^\"]*\"\s*>[^>]*<\/a>/i"
		,'h'=>"/<\s*a\s*href=\"http.*:[^\"]+[&?|]k=([a-zA-Z0-9]{5})[^\"]*\"\s*>[^>]*<\/a>/i"
		);
			
		foreach($regList as $r=>$regex) {
			if(preg_match_all($regex , $text, $matches, PREG_OFFSET_CAPTURE)) {
				$offset = 0;
				$matchNum = count($matches[0]);
				for($x=0;$x<$matchNum;$x++) {
					switch($r) {
						case 'c':
						case 'd':
						case 'e':
							$fi = new FItems();
							$fi->setWhere("sys_pages_items.pageId='".$matches[1][$x][0]."' and sys_pages_items.enclosure='".$matches[2][$x][0]."'");
							$arr = $fi->getList();
							if(!empty($arr)) {
								$replaceText = $arr[0]->render();
								$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
							}

							break;
						case 'g':
							$item = new ItemVO((int)$matches[1][$x][0],true);
							if($item->itemId > 0) {
								$replaceText = $item->render();
								$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
							}
							break;
						case 'h':
							//page link
							$userId = FUser::logon();
							$fPages = new FPages('', $userId);
							$fPages->setWhere("sys_pages.pageId='".$matches[1][$x][0]."'");
							$arr = $fPages->getContent();
							if(!empty($arr)) {
								$replaceText = FPages::printPagelinkList($arr,array('inline'=>1,'noitem'=>1));
								$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
							}
							break;
						case 'f':
							$pos = strpos($text,$matches[1][$x]);
							if($matches[1][$x][0]!='"' && $matches[1][$x][0]!="'") {
								//check extension
								$ext = FFile::fileExt($matches[2][$x][0]);
								$imageExtList = array('png','gif','jpeg','jpg');
								if(in_array($ext,$imageExtList)) {
									//do image
									$urlEncoded = base64_encode(str_replace("\n","",$matches[2][$x][0]));
									$replaceText = '<a href="'.$matches[2][$x][0].'" rel="lightbox-page"><img src="'.FConf::get("galery","targetUrlBase").'300/prop/remote/'.md5(FConf::get('image_conf','salt').$urlEncoded).'/'.$urlEncoded.'" /></a>';
								} else {
									//do link
									$replaceText = '<a href="'.$matches[2][$x][0].'">'.trim($matches[2][$x][0]).'</a>';
								}
								$text = FSystem::strReplace($text,$matches[2][$x][1]+$offset,strlen($matches[2][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[2][$x][0]);
							}
							break;
						case 'a':
						case 'b':
							$pos = false;
							if(!empty($matches[3][$x][0])) $pos = strpos($matches[2][$x][0],$matches[3][$x][0]);
							if($r==7 || $pos!==false) { //only if text of link is link itself
								$urlEncoded = base64_encode(str_replace("\n","",$matches[2][$x][0]));
								$replaceText = '<a href="'.$matches[2][$x][0].'" rel="lightbox-page"><img src="'.FConf::get("galery","targetUrlBase").'300/prop/remote/'.md5(FConf::get('image_conf','salt').$urlEncoded).'/'.$urlEncoded.'" /></a>';
								$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
							}
							break;
						case 'i':
            case 'i2':
							if(strpos($matches[0][$x][0],"#norender")===false){
								$fi = new FItems();
								$fi->setWhere("pageId = '".$matches[1][$x][0]."' and sys_pages_items.enclosure='".$matches[2][$x][0]."'");
								$arr = $fi->getList();
								if(!empty($arr)) {
									$replaceText = $arr[0]->render();
									$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
									$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
								}
							}
							break;
					}
				}
			}

		}

		return trim($text);
	}

	static function strReplace($textSource, $offset, $length, $textReplace) {
		return substr($textSource,0,$offset) . $textReplace . substr($textSource,$offset+$length);
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