<?php
class fSystem {
    static function &initPager($totalItems=0,$perPage=20,$inputParams=array()) {
        global $conf;
        $params = $conf['pager'];
		$params['prevImg'] = PAGER_PREVIOUS;
		$params['nextImg'] = PAGER_NEXT;
		$params['totalItems'] = $totalItems;
		$params['perPage'] = $perPage;
		if(!empty($inputParams)) $params = array_merge($params,$inputParams);
		$pager =& new fPager($params);
		return $pager;
    }
    function grndbanner($kam=0) {
    	Global $db,$user;
    	if(empty($user->strictBanner)) {
        	if($user->zbanner == 0 && $user->strictBannerAllClicked == 0) {
    	        $dot = "select b.bannerId,b.imageUrl,b.linkUrl,
    	        if(h.dateCreated is null or date_format(max(h.dateCreated),'%d') != date_format(now(),'%d'),0,1)
    	        from sys_banner as b left join sys_banner_hit as h on b.bannerId=h.bannerId and h.userId='".$user->gid."'
    	        where b.dateFrom <= NOW() AND b.dateTo > NOW() 
    	        and b.strict=1 
    	        group by b.bannerId";
    	        $arr = $db->getAll($dot);
    	        $user->strictBannerAllClicked = 1;
    	        foreach ($arr as $row) {
    	        	if($row[3]==0) {
    	        	    $user->strictBanner[] = $row;
    	        	    $user->strictBannerAllClicked = 0;
    	        	}
    	        }
        	} elseif($user->zbanner == 1) {
        	    
            	    $dot = "SELECT bannerId,imageUrl,linkUrl FROM sys_banner WHERE dateFrom <= NOW() AND dateTo > NOW() ORDER BY RAND()";
                	$user->strictBanner = $db->getAll($dot);
        	    
        	}
    	}
    	
    	if(!empty($user->strictBanner)) {
        	
            if($user->zbanner == 0)
               $banner = array_shift($user->strictBanner);
             else
                $banner = $user->strictBanner[rand(0,count($user->strictBanner)-1)];
            
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
      global $db,$user;
      $bid = $bannerId * 1;
      $db->query("UPDATE banner SET hit = (hit+1) WHERE id='".$bid."'");
      if($user->idkontrol) {
          $db->query("insert into sys_banner_hit (bannerId,userId,dateCreated) values ('".$bid."','".$user->gid."',now())");
          if($user->zbanner == 1)  {
              if(count($user->strictBanner > 1)) {
                  foreach ($user->strictBanner as $banner) {
                  	if($banner[0]!=$bid) $newBannArr[] = $banner;
                  }
                  $user->strictBanner = $newBannArr;
              }
              else $user->strictBanner = 1;
          }
      }
      header("Location: http://".str_replace("http://","",$db->getOne("SELECT linkUrl FROM sys_banner WHERE bannerId='".$bid."'")));
    }
    /*.......generate MENU............*/
    function topmenu(){
    	global $user,$db;
    	$x=0;
    	$user->topmenu = array();
    	$arrmenu=$db->getAll("SELECT pageId,text FROM sys_menu ".((!$user->idkontrol)?("WHERE public=1"):(''))." ORDER BY ord");
    	if($user->idkontrol) {
    	    $arrmenu[]=array('elogo',LABEL_LOGOUT);
    	}
    	foreach ($arrmenu as $ro) {
    		$menuItems[] = array("LINK"=>'?k='.$ro[0],"ACTIVE"=>(($user->currentPageId==$ro[0])?(1):(0)),"TEXT"=>$ro[1]);
    		$user->topmenu[] = $ro[0];
    	}
    	return $menuItems;
    }
    function secondaryMenu($menu) {
    	global $db,$user;
    	$ret=array();
    	$arrmnuTmp=$db->getAll("SELECT s.pageId, s.name 
    	FROM sys_menu_secondary as s 
    	INNER JOIN sys_pages as p ON p.menuSecondaryGroup=s.menuSecondaryGroup 
    	WHERE ".(($user->idkontrol)?(''):("s.public=1 AND "))." p.pageId='".$user->currentPageId."' ORDER BY s.ord,s.name");
    	if(!empty($arrmnuTmp)) {
	    	foreach ($arrmnuTmp as $row) {
	    		$arrmnu[]=array('pageId'=>$row[0],'name'=>$row[1],'typ'=>0,'opposite'=>0);
	    	}
    	} else $arrmnu = array();
    	if(!empty($user->systemmenu)) $arrmnu = array_merge($user->systemmenu,$arrmnu);
    	if(!empty($user->usrmenu)) $arrmnu = array_merge($arrmnu,$user->usrmenu);
    	
    	if(count($arrmnu)>0){
    		$x=0;
    		foreach ($arrmnu as $mnu){
    			$idlnk=""; $x++;
    			if (!empty($who)) $idlnk.='&who='.$user->whoIs;
                $button = array("LINK"=>(($mnu['typ']==1)?($mnu['pageId'].$idlnk):(BASESCRIPTNAME.'?k='.$mnu["pageId"].$idlnk)),
    			"ACTIVE"=>((preg_match("/".$user->currentPageId.$user->currentPageParam."$/",$mnu["pageId"]))?(1):(0)),
          "TEXT"=>$mnu["name"],"OPPOSITE"=>(($mnu['opposite']==1)?(1):(0)));    			
    			if(!empty($mnu['click'])) $button['CLICK'] = $mnu['click'];
    			if(!empty($mnu['id'])) $button['ID'] = $mnu['id'];
    			$ret[] = $button;
    		}
    	}
    	
    	return($ret);
    }
    function secondaryMenuAddItem($link,$text,$click='',$opposite='0',$buttonId='') {
        global $user;
        $button = array('pageId'=>$link,'typ'=>1,'name'=>$text,'opposite'=>$opposite,'id'=>$buttonId);
        if(!empty($click)) $button['click'] = $click;
        $user->systemmenu[] = $button;
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
      
      global $user;
        $text = trim($text);
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
        if($paramsArr['formatOption']<2) {
            require_once('PEAR.php');
            require_once('HTML/Safe.php');
            $safe = new HTML_Safe();
            $text = str_replace('\\','',$text);
            $text = $safe->parse($text);
        }
        if($endOfLine==1) {
          $br='<br />';
          $text = str_replace(array($br."\r\n",$br."\n"),array("\r\n","\r\n"),$text);
          $text = nl2br($text);
        }
        elseif($endOfLine==2) $text = fSystem::textinsBr2nl($text);
        if($breakLong==1) $text = fSystem::wordWrap($text);
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
        return htmlspecialchars(fSystem::textinsBr2nl($text));
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
    function isDate($datein) {
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
        if (!is_uploaded_file($file["tmp_name"])) fError::addError(ERROR_UPLOAD_NOTLOADED);
    	else if($file['size'] > $size) fError::addError(ERROR_UPLOAD_TOBIG);
    	else if (!in_array($file['type'],$types)) fError::addError(ERROR_UPLOAD_NOTALLOWEDTYPE);
    	else if(file_exists($kam.'/'.$file["name"]) && $rewrite==false) fError::addError(ERROR_UPLOAD_FILEEXISTS);
    	else if(!fSystem::checkFilename($file['name'])) fError::addError(ERROR_UPLOAD_NOTALLOWEDFILENAME);
    	else if (!$res = move_uploaded_file($file["tmp_name"], $kam.'/'.$file["name"])) fError::addError(ERROR_UPLOAD_NOTSAVED);
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
        $arrFiles = fSystem::fileList($dir,$type);
    	$ret='<select name="'.$name.'" size="1" class="'.$class.'">'.(($empty)?('<option></option>'):(''));
    	foreach ($arrFiles as $file) $ret.='<option value="'.$file.'"'.(($sel==$file)?(' selected="selected"'):('')).'>'.$file.'</option>';
    	$ret.='</select>';
    	return $ret;
    }
    //--REMOVE HERE
    function skinrol($name,$sel="") {
    	global $db;
    	$vys=$db->getAll("SELECT skinId,name FROM sys_skin");
    	$ret='<select name="'.$name.'" size="1" class="tlacitko">';
    	foreach ($vys as $row) $ret.='<option value="'.$row[0].'"'.(($sel==$row[0])?(' selected'):('')).'>'.$row[1].'</option>';
    	$ret.='</select>';
    	return $ret;
    }
    
    function rolPublic($name,$sel,$class='tlacitko'){
    	global $ARRPUBLIC;
    	$ret='<select name="'.$name.'"'.(($class)?(' class="'.$class.'"'):('')).'>';
    	foreach($ARRPUBLIC as $k=>$v) $ret.='<option value="'.$k.'"'.(($sel==$k)?(' selected="selected"'):('')).'>'.$v.'</option>';
    	$ret.='</select>';
    	return($ret);
    }
    function getmicrotime(){
       list($usec, $sec) = explode(" ",microtime());
       return ((float)$usec + (float)$sec);
    }
    static function profile($comment='') {
        global $debugTime,$start;
        echo ((!empty($comment))?('<br>'.$comment):('')).'<br>memory peak:'.round(memory_get_peak_usage()/1024).'_usage:'.round(memory_get_usage()/1024).'<br>';
        echo $debugTime = fSystem::getmicrotime()-$start.'<br>';
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
    	return strtr(iconv(CHARSET, 'US-ASCII//TRANSLIT', $text),
            ' ,;:?*#!�$%&/(){}<>=`�|\\\'"',
            '----------------------------');
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
                      if (!fSystem::rm_recursive($filepath.'/'.$sf)) {
                          fError::addError($filepath.'/'.$sf.' could not be deleted.');
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
        $options = '';
        if(!empty($arr)) {
            if(is_array($arr[0])) {
                foreach ($arr as $row) {
                	$newArr[$row[0]] = $row[1];
                }
                $arr = $newArr;
            }
            if($firstEmpty==true) $options .= '<option value="">'.$firstText.'</option>';
            foreach ($arr as $k=>$v) {
                $options .= '<option value="'.$k.'"'.(($k==$selected)?(' selected="selected"'):('')).'>'.$v.'</option>';
            }
        }
        return $options;
    }
    //---static system support functions
    static function getOnlineUsersCount() {
		Global $db;
		return($db->getOne("select count(1) from sys_users_logged where subdate(NOW(),interval ".USERVIEWONLINE." minute)<dateUpdated"));
	}
}