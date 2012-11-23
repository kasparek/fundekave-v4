<?php
class FSystem {
	
	static function recaptchaGet($error) {
		require_once(ROOT."ext/recaptchalib.php");
		return recaptcha_get_html("6LexXNkSAAAAAE_BDWQHhapdx-XPHItdWgBvDTSm", $error);
		return '';
	}
	
	static function recaptchaCheck() {
		if ($_POST["recaptcha_response_field"]) {
			require_once(ROOT."ext/recaptchalib.php");
			$resp = recaptcha_check_answer ("6LexXNkSAAAAAHke6ktw0hSwYha8x4N4Bn9M2vFm",$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);
			return $resp->is_valid ? true : $resp->error;
		}
		return false;
	}
  
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
		$domains = array('fundekave.net','iyobosahelpinghand.com','awake33.com','eboinnaija.fundekave.net','upsidedown.fundekave.net','sail.awake33.com');
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
		'STATIC_DOMAIN'=>STATIC_DOMAIN,
    'URL_CSS'=>(strpos(URL_CSS,'http://')===false)?STATIC_DOMAIN.URL_CSS:URL_CSS,
		'URL_SKIN'=>((strpos(URL_CSS,'http://')===false)?STATIC_DOMAIN.URL_CSS:URL_CSS).'skin/'.SKIN,
		'URL_JS'=>(strpos(URL_JS,'http://')===false)?STATIC_DOMAIN.URL_JS:URL_JS,
		'ASSETS_URL'=>(strpos(URL_ASSETS,'http://')===false)?STATIC_DOMAIN.URL_ASSETS:URL_ASSETS
		);
		foreach($superVars as $k=>$v) {
			$data = str_replace('[['.$k.']]',$v,$data);
		}
		return $data;
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
			require_once('pear/HTML/Safe.php');
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
			//$br='<br />';
			//$text = str_replace(array($br."\r\n",$br."\n"),array("\r\n","\r\n"),$text);
			//$text = nl2br($text);
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
    return $text;
    //return str_replace(array($br."\r\n",$br."\n"),array("&#10;","&#10;"),$text);
	}

	static function textToTextarea($text) {
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
    if(empty($text)) return $text;
    //replace all plain text URLs with html
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace=false;
    $dom->loadHTML('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="posttextprocessing">'.$text.'</div></body></html>');

    $x = new DOMXPath($dom);
    $nodes = $x->query("//text()[not(ancestor::a)]");

    $linkSeen=array();
    foreach($nodes as $node) {
      $nodeText = $node->nodeValue;

      if(preg_match_all("#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#i",$nodeText,$matches, PREG_OFFSET_CAPTURE)) {
        //remove old node
        $lastPos=0;
        foreach($matches[0] as $url) {
          if(!isset($linkSeen[$url[0]])) {
            if($url[1]>$lastPos) {
              $pretext = $dom->createTextNode(mb_substr($nodeText,$lastPos,$url[1]-$lastPos));
              $node->parentNode->insertBefore($pretext, $node);
            }
            $a = $dom->createElement("a");
            $a->nodeValue = $url[0];
            $a->setAttribute('href',$url[0]);
            $node->parentNode->insertBefore($a, $node);
            $lastPos = $url[1]+mb_strlen($url[0]);
            $linkSeen[$url[0]]=1;
          }
        }
        if($lastPos<mb_strlen($nodeText)) {
          $aftertext = $dom->createTextNode(mb_substr($nodeText,$lastPos));
          $node->parentNode->insertBefore($aftertext, $node);
        }
        $node->parentNode->removeChild($node);
      }
    }

    $tags=array();
    $results = $dom->getElementsByTagName('img');
    foreach ($results as $result) $tags[]=$result;
    $results = $dom->getElementsByTagName('a');
    foreach ($results as $result) $tags[]=$result;
    
    if(!empty($tags))
    foreach($tags as $tag) {
      $try=false;
      if($tag->tagName=='img') {
        $url = $tag->getAttribute('src');
        if($tag->parentNode->tagName!='a') $try=true;
      } else {
        $url = $tag->getAttribute('href');
        if($tag->nodeValue==$url) $try=true;
      }
      if($try) {
        $local=false;
        //check if url is internal or external
        //page
        if(preg_match("/.*[&?|]k=([a-zA-Z0-9]{5}).*/i", $url, $matches)) {
          $local=true;
          //it's local page
          $pageVO = new PageVO($matches[1]);
          $tag->nodeValue = $pageVO->get('name');
        } else {
          if(preg_match("/.*[&?|]i=([0-9]{1,7}).*/i", $url, $matches)) {
            //we have itemId
            $itemVO=new ItemVO($matches[1]);
            if($itemVO->load()) {
              $local=true;
              if($itemVO->typeId=='galery') {
                while($tag->firstChild) $tag->removeChild($tag->firstChild);
                $img = $dom->createElement("img");
                $tag->appendChild($img);
                $img->setAttribute('src',$itemVO->thumbUrl);
              } else {
                $tag->nodeValue = $itemVO->get('addon');
              }
            }
          } else {
            //item
            $regItemList=array(
              "/.*_([a-zA-Z0-9]{5})\/([0-9a-zA-Z._-]+\.[jpeg|jpg|png|gif]+)$/i" //http://fotobiotic.net/image/170x170/crop/dany/20120303_pangaimotu-island_zAV4q/dsc07869.jpg
              ,"/.*obr\/.+([a-zA-Z0-9]{5})\/([0-9a-zA-Z._-]+\.[jpeg|jpg|png|gif]+)/i"
              ,"/.*data\/cache\/img\/([a-zA-Z0-9]{5})-.*\/([0-9a-zA-Z-_.]+\.[jpeg|jpg|png|gif]+)/i"); //sample http://fundekave.net/data/cache/img/cKFbk-awake-work-in-progress/img_0111.jpg
            $i=0;
            foreach($regItemList as $regex) {
              if(preg_match($regex, $url, $matches)) {
                //we have pageId and enclosure
                $itemVO=new ItemVO();
                $itemVO->pageId=$matches[1];
                $itemVO->enclosure=$matches[2];
                if($itemVO->load()) {
                  $local=true;
                  //item load suceess
                  $img = $dom->createElement("img");
                  $img->setAttribute('src',$i==0?$url:$itemVO->thumbUrl);
                  $img->setAttribute('class','hentryimage');
                  if($tag->tagName=='a') {
                    $tag->setAttribute('rel','lightbox-page');
                    $tag->setAttribute('title',$itemVO->pageVO->get('name'));
                    $tag->setAttribute('href',$itemVO->getImageUrl(null,'800x800/prop'));
                    while($tag->firstChild) $tag->removeChild($tag->firstChild);
                    $tag->appendChild($img);
                  } else {
                    //img
                    $a = $dom->createElement("a");
                    $tag->parentNode->replaceChild($a,$tag);
                    $a->setAttribute('href',$itemVO->getImageUrl(null,'800x800/prop'));
                    $a->setAttribute('title',$itemVO->pageVO->get('name'));
                    $a->setAttribute('rel','lightbox-page');
                    $a->appendChild($img);
                  }
                }
                $i++;
                break;
              }
            }
          }
        }
        if(!$local) {
          //external images with thumb
          //if image make thumb
          if($tag->tagName=='img' || preg_match("/(?i)\.(jpeg|jpg|png|gif)$/i", $url, $matches)) {
            $urlEncoded = base64_encode($url);
            $img = $dom->createElement("img");
			$img->setAttribute('src',FConf::get("galery","targetUrlBase").'300/prop/remote/'.md5(ImageConfig::$salt.$urlEncoded).'/'.$urlEncoded);
            //$img->setAttribute('class','hentryimage');
            if($tag->tagName=='img') {
              $a = $dom->createElement("a");
              $tag->parentNode->replaceChild($a,$tag);
              $a->setAttribute('href',$url);
              $a->setAttribute('rel','lightbox-page');
              $a->appendChild($img);
            } else {
              $tag->setAttribute('rel','lightbox-page');
              while($tag->firstChild) $tag->removeChild($tag->firstChild);
              $tag->appendChild($img);
            }
          } else {
            //try get title
            $youtubeId=false;
            $vimeoId=false;
            if(preg_match("/http.*www.youtube.com.*v=([A-Za-z0-9-]+)/i",$url,$matches)) $youtubeId=$matches[1];
            if(preg_match("/.*youtu.be\/([A-Za-z0-9-]+)/i",$url,$matches)) $youtubeId=$matches[1];
            if(preg_match("/.*vimeo.com\/([0-9]+)$/i",$url,$matches)) $vimeoId=$matches[1];
            if($youtubeId || $vimeoId) {
              $iframe = $dom->createElement("iframe");
              $iframe->setAttribute('allowfullscreen','allowfullscreen');
              $iframe->setAttribute('frameborder','0');
              $iframe->setAttribute('width','560');
              $iframe->setAttribute('height','315');
              $iframe->setAttribute('src',$vimeoId?'http://player.vimeo.com/video/'.$vimeoId.'?title=0&amp;byline=0&amp;portrait=0':'http://www.youtube.com/embed/'.$youtubeId);
              $tag->parentNode->replaceChild($iframe,$tag);
            } else {
              //$pageContent = FSystem::curl_get_file_contents($url);
              $pageContent=false;
              if($pageContent!==false) {
                if(preg_match("/\<title\>(.*)\<\/title\>/i",$pageContent,$matches)) {
                  $title = trim($matches[1]);
                  if(!preg_match("/^[0-9]{3} .*/i",$title))
                    $tag->nodeValue=$matches[1];
                }
              }
              
            } 
          }
        }
      }
    }
     
  $textElement = $dom->getElementById('posttextprocessing');
  $text = $dom->saveXML($textElement);
  $text = substr($text,29,strlen($text)-35);

  
    //add line breaks
    $textArr = explode("\n",$text);
    $textArrLen = count($textArr);
    
    for($i=0;$i<$textArrLen;$i++) {
      $thisWord = trim($textArr[$i]);
      $nextWord = $i+1<$textArrLen ? trim($textArr[$i+1]) : false;
      $addBr = true;
      $regexBegin = "/^(<p|<div|<img)/i";
      $regexEnd = "/(\/p>|div>|<br>|<br \/>)$/i";
      if($nextWord!==false) {
        if(preg_match($regexBegin,$nextWord)) {
          $addBr=false;
        }
               
        if($addBr) {
          if(preg_match($regexEnd,$thisWord)) {
            $addBr=false;
          }
        }
          
        if($addBr){
          $textArr[$i] .= "<br />";
        }
      } 
    }
    
    $text = implode("\n",$textArr);
    
    return trim($text);
  }
      
  static function curl_get_file_contents($URL) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);
        return $contents ? $contents : false;
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


