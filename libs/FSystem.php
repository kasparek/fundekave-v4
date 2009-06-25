<?php
class FSystem {

	static function &initPager($totalItems=0,$perPage=20,$inputParams=array()) {
		$conf = FConf::getInstance();
		$params = $conf->a['pager'];
		$params['prevImg'] = FLang::$PAGER_PREVIOUS;
		$params['nextImg'] = FLang::$PAGER_NEXT;
		$params['totalItems'] = $totalItems;
		$params['perPage'] = $perPage;
		if(!empty($inputParams)) $params = array_merge($params,$inputParams);
		$pager =& new FPager($params);
		return $pager;
	}

	function grndbanner($kam=0) {
		$db = FDBConn::getInstance();
		$user = FUser::getInstance();
		 
		$cache = FCache::getInstance('s',0);
		$strictBanner = $cache->getData('list','banners');
		$strictBannerAllClicked = $cache->getData('allClick','banners');
		if($strictBannerAllClicked === false) $strictBannerAllClicked = 0;
		 
		if(empty($strictBanner)) {
			if($user->userVO->zbanner == 0 && $strictBannerAllClicked == 0) {
				$dot = "select b.bannerId,b.imageUrl,b.linkUrl,
    	        if(h.dateCreated is null or date_format(max(h.dateCreated),'%d') != date_format(now(),'%d'),0,1)
    	        from sys_banner as b left join sys_banner_hit as h on b.bannerId=h.bannerId and h.userId='".$user->userVO->userId."'
    	        where b.dateFrom <= NOW() AND b.dateTo > NOW() 
    	        and b.strict=1 
    	        group by b.bannerId";
				$arr = $db->getAll($dot);
				$strictBannerAllClicked = 1;
				foreach ($arr as $row) {
					if($row[3]==0) {
						$strictBanner[] = $row;
						$strictBannerAllClicked = 0;
					}
				}
			} elseif($user->userVO->zbanner == 1) {
				$dot = "SELECT bannerId,imageUrl,linkUrl FROM sys_banner WHERE dateFrom <= NOW() AND dateTo > NOW() ORDER BY RAND()";
				$strictBanner = $db->getAll($dot);
			}
			$cache->setData($strictBanner, 'list','banners');
			$cache->setData($strictBannerAllClicked, 'allClick','banners');
		}
		 
		if(!empty($strictBanner)) {
			if($user->userVO->zbanner == 0) {
				$banner = array_shift($strictBanner);
				$cache->setData($strictBanner, 'list','banners');
			} else {
				$banner = $strictBanner[rand(0,count($strictBanner)-1)];
			}
			$imgname = WEB_REL_BANNER . $banner[1];
			$imglink = 'bannredir.php?bid='.$banner[0];

			if(preg_match("/(.swf)$/",$imgname))
			$ret= '<object type="application/x-shockwave-flash" data="'.$imgname.'" width="468" height="60"><param name="movie" value="'.$imgname.'" /></object>';
			elseif(preg_match("/(jpg|gif)$/",$imgname))
			$ret= '<a href="'.$imglink.'" target="_blank"><img src="'.$imgname.'" width="468" height="60"></a>';
			else $ret = $banner[1];

			$db->query("UPDATE sys_banner SET display=display+1 WHERE bannerId=".$banner[0]);
			 
			return($ret);
		}
	}

	function bannerRedirect($bannerId) {
		$user = FUser::getInstance();
		$bid = $bannerId * 1;
		FDBTool::query("UPDATE banner SET hit = (hit+1) WHERE id='".$bid."'");
		if($user->idkontrol) {
			FDBTool::query("insert into sys_banner_hit (bannerId,userId,dateCreated) values ('".$bid."','".$user->userVO->userId."',now())");
			if($user->zbanner == 1)  {
				$cache = FCache::getInstance('s',0);
				$strictBanner = $cache->getData('list','banners');
				if(count($strictBanner > 1)) {
					foreach ($strictBanner as $banner) {
						if($banner[0]!=$bid) $newBannArr[] = $banner;
					}
					$cache->setData($newBannArr, 'list','banners');
				}
				else $cache->setData(1 , 'list','banners');
			}
		}
		header("Location: http://".str_replace("http://","",$db->getOne("SELECT linkUrl FROM sys_banner WHERE bannerId='".$bid."'")));
	}
	/*.......generate MENU............*/
	function topmenu(){
		$user = FUser::getInstance();
		 
		$q = "SELECT pageId,text FROM sys_menu ".((!$user->idkontrol)?("WHERE public=1"):(''))." ORDER BY ord";
		$arrmenu = FDBTool::getAll($q,'tMenu','default','s',0);

		if($user->idkontrol) {
			$arrmenu[]=array('elogo',FLang::$LABEL_LOGOUT);
		}
		 
		foreach ($arrmenu as $ro) {
			$menuItems[] = array("LINK"=>FUser::getUri('',$ro[0],''),"ACTIVE"=>(($user->pageVO->pageId == $ro[0])?(1):(0)),"TEXT"=>$ro[1]);
			$user->topmenu[] = $ro[0];
		}
		 
		return $menuItems;
	}
	function secondaryMenu($menu) {
		$user = FUser::getInstance();
		 
		$ret=array();

		$q = "SELECT s.pageId, s.name
      	FROM sys_menu_secondary as s 
      	INNER JOIN sys_pages as p ON p.menuSecondaryGroup=s.menuSecondaryGroup 
      	WHERE ".(($user->idkontrol)?(''):("s.public=1 AND "))." p.pageId='".$user->pageVO->pageId."' ORDER BY s.ord,s.name";
		$arrmnuTmp = FDBTool::getAll($q,$user->pageVO->pageId.'sMenu','default','s',0);
		
		if(!empty($arrmnuTmp)) {
			foreach ($arrmnuTmp as $row) {
				$arrmnu[]=array('LINK'=>FUser::getUri('',$row[0]),'TEXT'=>$row[1]);
			}
		} else $arrmnu = array();
		 
		$cache = FCache::getInstance('l');
		if(false !== ($secMenuCustom = $cache->getData('secMenu')) ) {
			$arrmnu = array_merge($secMenuCustom,$arrmnu);
		}
		
		$len = count($arrmnu);
		if($len>0){
			for($i=0;$i<$len;$i++){
				if(preg_match("/".$user->pageVO->pageId.$user->pageParam."$/",$arrmnu[$i]["LINK"])) $arrmnu[$i]['ACTIVE'] = 1;
			}
		}
		 
		return($arrmnu);
	}
	static function secondaryMenuAddItem($link,$text,$opposite='0',$buttonId='',$buttonClass='') {
		$button = array('LINK'=>$link,'TEXT'=>$text);
		if($opposite!=0) $button['OPPOSITE'] = 1;
		if($buttonId!='') $button['ID'] = $buttonId;
		if($buttonClass!='') $button['CLASS'] = $buttonClass;
		$cache = FCache::getInstance('l');
		$secMenuCustom = $cache->getData('secMenu');
		$secMenuCustom[] = $button;
		$cache->setData($secMenuCustom);
	}
	
	static function proccessItemEnclosure($enclosure) {
		$ret = false;
		if($enclosure!='') {
			if (preg_match("/(jpeg|jpg|gif|bmp|png|JPEG|JPG|GIF|BMP|PNG)$/",$enclosure)) {
				$ret = '<a href="'.$enclosure.'" rel="lightbox"><img src="' . $enclosure . '"></a>';
			} elseif (preg_match("/^(http:\/\/)/",$enclosure)) {
				$ret = '<a href="' . $enclosure . '" rel="external">' . $enclosure . '</a>';
			} else $ret = $enclosure;
		}
		return $ret;
	}

	/**
	 * option - 0 - remove html, safe text 1 - remove html, safe text, parse bb, 2- nothing - just trim
	 *
	 * @param input string $text
	 * @param numeric $option
	 * @param numeric $endOfLine - if 1 replace \n - <br />\n, 0 - do nothing, 2 - replace <br />\n - \n
	 * @param boolean $wrap - wrap long words
	 */
	function textins($text,$paramsArr=array()) {
		$breakLong = 1;
		$endOfLine = 1;

		if(!is_array($paramsArr)) $paramsArr = array();
		if(!isset($paramsArr['formatOption'])) $paramsArr['formatOption']=1;

		if(isset($paramsArr['plainText'])) {
			$paramsArr['formatOption']=0;
			$endOfLine = 0;
		}


		$text = trim($text);

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
	function wordWrap($strParam, $i=70, $wrap = "\n") {
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
	function textinsBr2nl($text,$br='<br />') {
		return str_replace(array($br."\r\n",$br."\n"),array("\n","\n"),$text);
	}
	function textToTextarea($text) {
		return htmlspecialchars(FSystem::textinsBr2nl($text));
	}
	//---kontrola vkladaneho datumu
	function den($date) {
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

	/*---------------upload--------------*/
	function upload($file,$kam='',$size=20000,$rewrite=true,$types=array("image/pjpeg","image/jpeg","image/png","image/gif")) {
		$ret = false;
		if (!is_uploaded_file($file["tmp_name"])) FError::addError(FLang::$ERROR_UPLOAD_NOTLOADED);
		else if($file['size'] > $size) FError::addError(FLang::$ERROR_UPLOAD_TOBIG);
		else if (!in_array($file['type'],$types)) FError::addError(FLang::$ERROR_UPLOAD_NOTALLOWEDTYPE);
		else if(file_exists($kam.'/'.$file["name"]) && $rewrite==false) FError::addError(FLang::$ERROR_UPLOAD_FILEEXISTS);
		else if(!FSystem::checkFilename($file['name'])) FError::addError(FLang::$ERROR_UPLOAD_NOTALLOWEDFILENAME);
		else if (!$res = move_uploaded_file($file["tmp_name"], $kam.'/'.$file["name"])) FError::addError(FLang::$ERROR_UPLOAD_NOTSAVED);
		else {
			chmod($kam.'/'.$file["name"],0777); //---upsesne ulozeno
			$ret["kam"] = $kam;
			$ret["name"] = $file["name"];
		}
		return($ret);
	}
	static function fileList($dir,$type="") {
		$arrFiles = array();
		if(is_dir($dir)) {
			$handle=opendir($dir);
			while (false!==($file = readdir($handle))) if ($file != "." && $file != ".." && ($type=="" || preg_match("/(".$type.")$/",$file))) $arrFiles[]= $file;
			closedir($handle);
		}
		return $arrFiles;
	}
	function fileCombo($name,$dir,$sel="",$type="",$empty=true,$class='tlacitko') {
		$arrFiles = FSystem::fileList($dir,$type);
		$ret='<select name="'.$name.'" size="1" class="'.$class.'">'.(($empty)?('<option></option>'):(''));
		foreach ($arrFiles as $file) $ret.='<option value="'.$file.'"'.(($sel==$file)?(' selected="selected"'):('')).'>'.$file.'</option>';
		$ret.='</select>';
		return $ret;
	}

	function getmicrotime(){
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

	static function profile($comment='') {
		global $debugTime,$start;
		echo ((!empty($comment))?('<br>'.$comment):('')).'<br>memory peak:'.round(memory_get_peak_usage()/1024).'_usage:'.round(memory_get_usage()/1024).'<br>';
		echo $debugTime = FSystem::getmicrotime()-$start.'<br>';
	}

	static function checkFilename($filename) {
		return preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9\.-]*))$/" , $filename);
	}

	static function checkDirname($dirname) {
		return preg_match("/(^[a-zA-Z0-9]+([a-zA-Z\_0-9-\/]*))$/" , $dirname);
	}

	static function checkUsername($name) {
		return preg_match("/(^[a-zA-Z0-9]+([a-zA-Z0-9]*))$/" , $name);
	}

	static function safeText($text) {
		$url = $text;
		$url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
		$url = trim($url, "-");
		$url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
		$url = strtolower($url);
		$url = preg_replace('~[^-a-z0-9_]+~', '', $url);
		return $url;
	}

	function array_neighbor($key, $arr, $consecutively = false) {
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

	static function rm_recursive($filepath) {
		if (is_dir($filepath) && !is_link($filepath)) {
			if ($dh = opendir($filepath)) {
				while (($sf = readdir($dh)) !== false) {
					if ($sf != '.' && $sf != '..') {
						if (!FSystem::rm_recursive($filepath.'/'.$sf)) {
							FError::addError($filepath.'/'.$sf.' could not be deleted.');
						}
					}
				}
				closedir($dh);
			}
			return rmdir($filepath);
		}
		if(file_exists($filepath)) return unlink($filepath);
	}
	static function getOptions($arr,$selected='',$firstEmpty=true,$firstText='') {
		if(!is_array($arr)) {
			$arr = FDBTool::getAll('select categoryId,name from sys_pages_category where typeId="'.$arr.'"');
		}
		$options = '';
		if(!empty($arr)) {
			$arrkeys = array_keys($arr);
			if(is_array($arr[$arrkeys[0]])) {
				foreach ($arr as $row) {
					$newArr[$row[0]] = $row[1];
				}
				$arr = $newArr;
			}
			if($firstEmpty==true) $options .= '<option value="">'.$firstText.'</option>';
			foreach ($arr as $k=>$v) {
				$options .= '<option value="'.$k.'"'.(($k==$selected)?(' selected="selected"'):('')).'>'.((!empty($v))?($v):($k)).'</option>';
			}
		}
		return $options;
	}
	//---static system support functions
	static function getOnlineUsersCount() {
		$q = "select count(1) from sys_users_logged where subdate(NOW(),interval ".USERVIEWONLINE." minute)<dateUpdated";
		return FDBTool::getOne($q,'uOnC','default','s',60);
	}
	
	static function makeDir($dir,$mode=0777,$recursive=true) {
		if(!file_exists($dir)) {
			return mkdir($dir, $mode, $recursive);
		}
	}
	static function fileExt($filename) {
		$arr = explode('.',$filename);
		return strtolower($arr[count($arr)-1]);
	}
}