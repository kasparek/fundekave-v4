<?php
class FSystem {
	
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
		if($text=='') return '';

		$user = FUser::getInstance();
		if($paramsArr['formatOption']==0 || $user->idkontrol==false) {
			$text = strip_tags($text);
		}

		if($paramsArr['formatOption']==1) {
			require_once('HTML/BBCodeParser.php');
			$config = parse_ini_file(ROOT.CONFIGDIR.'BBCodeParser.ini', true);
			$parser = new HTML_BBCodeParser($config['HTML_BBCodeParser']);
			$parser->setText($text);
			$parser->parse();
			$text = $parser->getParsed();

		}

		if($paramsArr['formatOption'] < 2) {
			require_once('PEAR.php');
			require_once('HTML/Safe.php');
			$safe = new HTML_Safe();
			if($user->idkontrol && $paramsArr['formatOption']>0) {
				$objectKey = array_search('object',$safe->deleteTags);
				array_splice($safe->deleteTags, $objectKey, 1);
				$objectKey = array_search('embed',$safe->deleteTags);
				array_splice($safe->deleteTags, $objectKey, 1);
				$objectKey = array_search('name',$safe->attributes);
				array_splice($safe->attributes, $objectKey, 1);
			}
			$text = str_replace('\\','',$text);
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
			if(strlen($word)>$i) $arrRep[$word] = wordwrap( $word , $i , $wrap , 1);
		}
		if(!empty($arrRep)) {
			foreach ($arrRep as $k=>$v) {
				$strParam = str_replace($k,$v,$strParam);
			}
		}
		return $strParam;
	}
	
	static function textinsBr2nl($text,$br='<br />') {
		return str_replace(array($br."\r\n",$br."\n"),array("\n","\n"),$text);
	}
	
	static function textToTextarea($text) {
		return htmlspecialchars(FSystem::textinsBr2nl($text));
	}
	
	//---kontrola vkladaneho datumu
	static function den($date) {
		list($day,$month,$year)=Explode(".",$date);
		if(isset($year)) {
			if(checkdate($month,$day,$year)) {
				return $year.'-'.$month.'-'.$day;
			}
		}
	}
	
	static function switchDate($date) {
		$arr = explode('.',$date);
		if(count($arr)>1) {
			$date = $arr[2].'-'.$arr[1].'-'.$arr[0];
		}
		return $date;
	}
	
	static function isDate($datein) {
		$ret=false;
		$arr = explode(" ",$datein);
		$da = explode("-",$arr[0]);
		if(count($da)==3){
			if(checkdate(($da[1]*1),($da[2]*1),($da[0]*1))) $ret=true;
		}
		return $ret;
	}
	
	static function isTime($time) {
		$arrTime = explode(':',$time);
		if (count($arrTime) >= 2) {
			if($arrTime[0] < 24 && $arrTime[1] < 60 && $arrTime[0] >= 0 && $arrTime[1] >= 0) return true;
		}
	}
	
	function ip2num($ip) {
		$arip=explode(".",$ip);
		$numip=sprintf ("%03d%03d%03d%03d", $arip[0], $arip[1], $arip[2],$arip[3]);
		return($numip);
	}
	
	function getUserIp() {
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
		$url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
		$url = strtolower($url);
		$url = preg_replace('~[^-a-z0-9_]+~', '', $url);
		return $url;
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
		
}