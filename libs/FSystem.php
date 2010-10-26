<?php
class FSystem {

	static function superVars($data) {
		//TODO: do supervariable some coolway
		//TODO: from userVO load custom skin name previously: URL_CSS . SKIN_DEFAULT
		$superVars = array('SKINURL'=>'http://fotobiotic.net/css/skin/default',
		'URL_JS'=>URL_JS);
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

	static function tplParseBlockFromVars($tpl, $vars, $block='__global__') {
		foreach($vars as $k=>$v) $varArr[strtolower($k)] = $v;
		if (isset($tpl->blocklist[$block]) && !empty($vars)) {
			if(preg_match_all("{{([A-Za-z0-9]*)}}", $tpl->blocklist[$block], $arr)){
				foreach($arr[1] as $vartoset){
					$vartosetLower = strtolower($vartoset);
					if(isset($varArr[$vartosetLower])) $tpl->setVariable($vartoset,$varArr[$vartosetLower]);
				}
			}
		}
		$tpl->parse($block);
		return $tpl;
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
		if($newPageId == HOME_PAGE && empty($pageParam)) $newPageId = '';

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
				$pageVO  = new PageVO($newPageId,true);
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
				$textLink = substr($pageId,$pos+1);
				//TODO: security check if textlink match with pageid -  otherwise do redirect
				$pageId = substr($pageId,0,$pos);
			}
			//---slice pageid on fiveid and params
			if(isset($pageId{5})) {
				if($pageId{5}==';') {
					$getArr = explode(";",substr($pageId,5));
					foreach ($getArr as $getVar) {
						$getVarArr = explode("=",$getVar);
						$_GET[$getVarArr[0]] = $getVarArr[1];
					}
				} else {
					$user = FUser::getInstance();
					$user->pageParam = substr($pageId,5);
					$pageId = substr($pageId,0,5);
				}
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
				$objectKey = array_search('object',$safe->deleteTags);
				array_splice($safe->deleteTags, $objectKey, 1);
				$objectKey = array_search('embed',$safe->deleteTags);
				array_splice($safe->deleteTags, $objectKey, 1);
				$objectKey = array_search('name',$safe->attributes);
				array_splice($safe->attributes, $objectKey, 1);
				if(FRules::getCurrent(2)) {
					$objectKey = array_search('iframe',$safe->deleteTags);
					array_splice($safe->deleteTags, $objectKey, 1);
				}
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
			if(strlen($word)>$i && strpos($word,'http:')===false) {
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
		return str_replace(array($br."\r\n",$br."\n"),array("\n","\n"),$text);
	}

	static function textToTextarea($text) {
		return htmlspecialchars(FSystem::textinsBr2nl($text));
	}

	static function checkDate($date) {
		$date = FSystem::textins($date,array('plainText'=>1));
		$arr = explode(' ',$date);//get time part
		$date = $arr[0];
		$time = FSystem::isTime($arr[1]) ? ' '.$arr[1] : '';
			
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
		$text = strtolower($text);
		$arr = explode('.',$text);
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
		//mozna pridat pred i a k ze ma byt [&?|]
		//TODO: fix first,2,3 link rewrite to item
		//TODO: remove fitemsrenderer::proccessItemEnclosure and process here
		//TODO: keep data/cache and rewrite to new structure

		$text = ' '.$text;

		$regList = array(
		"/(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|>|\<|$|\.\s)<\s*img\s*src=\"(http:[^\"]+\.[jpeg|jpg|png|gif]+)\".*>([^>]*)/i"
		,"/(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|>|\<|$|\.\s)<\s*a\s*href=\"(http:[^\"]+\.[jpeg|jpg|png|gif]+)\"\s*>([^>]*)<\/a>/i"
		
		,"/<img src=\"http:\/\/[0-9a-zA-Z.\/]*\/data\/cache\/[0-9a-zA-Z-]*\/([0-9a-zA-Z]*)-[a-zA-Z0-9-_]*\/([^\"]*+)\"[^<]+?>/i"
		,"/<\s*a\s*href=\"[^\"]+\/data\/cache\/[^\"]+\/([a-zA-Z0-9]{5})-[^\"]+\/([0-9a-zA-Z.]*\.jpg)\"\s*>[^>]*<\/a>/i"
		,"/<\s*a\s*href=\"[^\"]+\/obr\/[^\"]+([a-zA-Z0-9]{5})\/([0-9a-zA-Z.]*\.jpg)\"\s*>[^>]*<\/a>/i"
		
		,"#(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|>|\<|$|\.\s)((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#i"
		,"/<\s*a\s*href=\"http:[^\"]+[&?|]i=([0-9]*)[^\"]*\"\s*>[^>]*<\/a>/i"
		,"/<\s*a\s*href=\"http:[^\"]+[&?|]k=([a-zA-Z0-9]{5})[^\"]*\"\s*>[^>]*<\/a>/i"
		);


		$r=0;
		foreach($regList as $regex) {
			if(preg_match_all($regex , $text, $matches, PREG_OFFSET_CAPTURE)) {
				$offset = 0;
				$matchNum = count($matches[0]);
				for($x=0;$x<$matchNum;$x++) {
					switch($r) {
						case 2:
						case 3:
						case 4:
							$fi = new FItems();
							$fi->setWhere("sys_pages_items.enclosure='".$matches[2][$x][0]."'");
							$arr = $fi->getList();
							if(!empty($arr)) {
								$replaceText = $arr[0]->render();
								$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
							}

							break;
						case 6:
							$item = new ItemVO((int)$matches[1][$x][0],true);
							if($item->itemId > 0) {
								$replaceText = $item->render();
								$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
							}
							break;
						case 7:
							//page link
							$userId = FUser::logon();
							$fPages = new FPages('', $userId);
							$fPages->setWhere("sys_pages.pageId='".$matches[1][$x][0]."'");
							$arr = $fPages->getContent();
							if(!empty($arr)) {
								$replaceText = FPages::printPagelinkList($arr,array('inline'=>1));
								$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
							}
							break;
						case 5:
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
						case 0:
						case 1:
							$pos = false;
							if(!empty($matches[3][$x][0])) $pos = strpos($matches[2][$x][0],$matches[3][$x][0]);
							if($r==7 || $pos!==false) { //only if text of link is link itself
								$urlEncoded = base64_encode(str_replace("\n","",$matches[2][$x][0]));
								$replaceText = '<a href="'.$matches[2][$x][0].'" rel="lightbox-page"><img src="'.FConf::get("galery","targetUrlBase").'300/prop/remote/'.md5(FConf::get('image_conf','salt').$urlEncoded).'/'.$urlEncoded.'" /></a>';
								$text = FSystem::strReplace($text,$matches[0][$x][1]+$offset,strlen($matches[0][$x][0]),$replaceText);
								$offset += strlen($replaceText) - strlen($matches[0][$x][0]);
							}
							break;
					}
				}
			}
			$r++;
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
}