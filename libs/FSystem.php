<?php
class FSystem {

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
	 * @return string - URL
	 */
	static function getUri($otherParams='', $pageId='', $pageParam=false, $scriptName=BASESCRIPTNAME) {
		$arrAcnchor = explode('#',$otherParams);
		$otherParams = $arrAcnchor[0];
		$anchor = '';
		if(isset($arrAcnchor[1])) $anchor = '#' . $arrAcnchor[1];
		$otherParams = str_replace('&',SEPARATOR,$otherParams);
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
				$params[] = 'i='.$user->itemVO->itemId;
				if(empty($pageParam)) $newPageId = '';
			}
		}
		if(!empty($newPageId)) {
			if(empty($pageParam)) {
				$pageVO  = new PageVO($newPageId,true);
				$safeName = FSystem::safetext($pageVO->name);
			}
			$params[] = 'k=' . $newPageId . $pageParam . ((!empty($safeName))?('-'.$safeName):(''));
			$params = array_reverse($params);
		}
		if(!empty($otherParams)) $params[] = $otherParams;
		$parStr = '';
		if(isset($params)) {
			$parStr = '?'.implode(SEPARATOR,$params);
		}
		$url = $scriptName . $parStr . $anchor;
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

		if($paramsArr['formatOption']==1) {
			require_once(ROOT.'pear/HTML/BBCodeParser.php');
			$config = parse_ini_file(ROOT.CONFIGDIR.'BBCodeParser.ini', true);
			$parser = new HTML_BBCodeParser($config['HTML_BBCodeParser']);
			$parser->setText($text);
			$parser->parse();
			$text = $parser->getParsed();
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
		if(strpos($date,'.')) {
			$arr = explode('.',$date);
			if(count($arr)>1) {
				$date = $arr[2].'-'.$arr[1].'-'.$arr[0];
			}
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

	static function postText($text) {
		//mozna pridat pred i a k ze ma byt [&?|]
		$regList = array(
		"/<img src=\"http:\/\/[0-9a-zA-Z.\/]*\/data\/cache\/[0-9a-zA-Z-]*\/([0-9a-zA-Z]*)-[a-zA-Z0-9-_]*\/([^\"]*+)\"[^<]+?>/i"
		,"/<\s*a\s*href=\"[^\"]+\/data\/cache\/[^\"]+\/([a-zA-Z0-9]{5})-[^\"]+\/([0-9a-zA-Z.]*\.jpg)\"\s*>[^>]*<\/a>/i"
		,"/<\s*a\s*href=\"[^\"]+\/obr\/[^\"]+([a-zA-Z0-9]{5})\/([0-9a-zA-Z.]*\.jpg)\"\s*>[^>]*<\/a>/i"
		,"/<\s*a\s*href=\"http:[^\"]+[&?|]i=([0-9]*)[^\"]*\"\s*>[^>]*<\/a>/i"
		,"/<\s*a\s*href=\"http:[^\"]+[&?|]k=([a-zA-Z0-9]{5})[^\"]*\"\s*>[^>]*<\/a>/i"
		,"/<\s*a\s*href=\"(http:[^\"]+\.[jpg|png|gif]+)\"\s*>([^>]*)<\/a>/i"
		);

		$r=0;
		foreach($regList as $regex) {
			if(preg_match_all($regex , $text, $matches)) {
				$x=0;
				foreach($matches[0] as $replace) {
					switch($r) {
						case 0:
						case 1:
						case 2:
							$itemId = FDBTool::getOne("select itemId from sys_pages_items where pageId='".$matches[1][$x]."' and enclosure='".$matches[2][$x]."'");
							if($itemId > 0) {
								$item = new ItemVO($itemId,true,array('type'=>'galery','inside'=>true,'showRating'=>true,'showTag'=>true,'showCommentsNum'=>true,'showText'=>true,'openPopup'=>false));
								if($item->itemId > 0) {
									$text = str_replace($replace, $item->render(), $text);
								}
							}
							break;
						case 3:
							//item by id
							$item = new ItemVO((int)$matches[1][$x],true,array('type'=>'galery','inside'=>true,'showRating'=>true,'showTag'=>true,'showCommentsNum'=>true,'showText'=>true,'openPopup'=>false));
							if($item->itemId > 0) {
								$text = str_replace($replace, $item->render(), $text);
							}
							break;
						case 4:
							//page link
							//echo $text;print_r($matches);die();
							$userId = FUser::logon();
							$fPages = new FPages('', $userId);
							$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,p.typeId'.(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')).'');
							if(empty($userId)) {
								$fPages->addWhere('p.locked < 2');
							} else {
								$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
							}
							$fPages->setWhere("p.pageId='".$matches[1][$x]."'");
							$arr = $fPages->getContent();
							if(!empty($arr)) {
								$data = FPages::printPagelinkList($arr,array('inline'=>1));
								$text = str_replace($replace, $data, $text);
							}
							break;
						case 5:
							if(strpos($matches[1][$x],$matches[2][$x])!==false) {
								$urlEncoded = base64_encode(str_replace("\n","",$matches[1][$x]));
								$text = str_replace($replace, '<a href="'.$matches[1][$x].'" rel="lightbox"><img src="/image/300/prop/remote/'.md5(FConf::get('image_conf','salt').$urlEncoded).'/'.$urlEncoded.'" /></a>', $text);
							} else {
								$text = str_replace($replace, '<a href="'.$matches[1][$x].'" rel="lightbox">'.trim($matches[2][$x]).'</a>', $text);
							}
							break;
					}
					$x++;
				}
			}
			$r++;
		}
		return $text;
	}

}