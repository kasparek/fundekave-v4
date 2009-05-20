<?php
class FUser {
  var $userVO;
  var $loginVO;
  var $pageVO;
  var $pageParam;
  var $itemVO;
  
    //---user access
    var $idkontrol = false;
    //---userID
	var $gid = 0;
	var $idlogin = '';
	var $idloginInDb = '';
	var $lo = 'fdk4.salt';
	//--page informations
	var $currentPageId = '';
	var $currentItemId = 0;
	var $currentItem = array();
	var $currentPageParam;
	var $pageParamNeededPermission = array(
	'e'=>2, //edit (galery,forum,blog)
	'u'=>4, //event - podle majitele - nebo ten kdo ma dve pro stranku
	'h'=>1, //home - u klubu - home z XML
	's'=>1, //statistika - vestinou u klubu, muze byt kdekoliv
	'p'=>1, //anketa nastaveni
	'sa'=>3, //super admin - nastavovani prav a ostatniho nastaveni u kazdy stranky .. uzivatel musi mit prava 2 ke strance sadmi
	);
	var $currentPage;
	var $currentPageAccess = false;
	var $favorite = 0;
	var $favoriteCnt = 0;
	//---logged user personal information
	var $gidname = '';
	var $newPassword = '';
	var $galtype=0;
	var $skin = 0;
	var $skinName = '';
	var $skinDir = '';
	var $homePageId = '';
	var $email = '';
	var $icq = '';
	var $zbanner = 1;
	var $zidico = 1;
	var $zaudico = 1;
	var $ip = '';
  	var $ipcheck = true;
  	var $ico = AVATAR_DEFAULT;
  	
  	var $dateCreated;
  	var $dateLast;
  	//---used when looking after someone informations
	var $whoIs=0; 
	//---new post alerting
	var $newPost = 0;
	var $newPostFrom = '';
	//---used as array for banners displaying
	var $strictBanner = array();
	var $strictBannerAllClicked = 0;
	//---manu buttons cache
	var $systemmenu = array();
    //---cache for other users informations
	var $arrUsers = array();
	//---cache for permissions
	var $arrRulez = array();
	//---items per page
	var $auditPerPage = FORUM_PERPAGE;
	var $postPerPage = POST_PERPAGE;
	//---cache for filtering
	var $arrFilter = array();
	//---cache for galery search results
	var $galSearchCache = array();
	//---cache for logged friends - id, names
	var $arrFriends = array();
	//---used for cacheing tooltips from other libraries (galery)
    var $arrTooltips = array();
    
    //---cache search in pages
    var $pagesSearch = array();
    var $itemsSearch = array();

    //---additional user information XML structure
	var $xmlProperties = "<user><personal><www/><motto/><place/><food/><hobby/><about/><HomePageId/></personal><webcam /></user>";
	
	public $initialized;
	
	private static $instance;
	
	static function &getInstance() {
		if (!isset(self::$instance)) {
			if(isset($_SESSION["user"])) {
				if( !empty($_SESSION["user"]->initialized) ) {
					self::$instance = &$_SESSION["user"];
		     	}
			} 
		}
		if (!isset(self::$instance)) {
			self::$instance = &new FUser();
			self::$instance->initialized = true;
			$_SESSION["user"] = &self::$instance;
		}
		return self::$instance;
	}
	
	function myDestructor() {
	  //called before the session is written
	  $this->arrCachePerLoad = array();
	    $this->systemmenu = array();
	    $this->arrFriends = array();
	    //$this->arrUsers = array();
	    $this->arrTooltips = array();
	    
	    $this->cacheLite = false;
	    $this->cacheLiteCurrentConf = '';
	}
	function __destruct() {
	    $this->myDestructor();
	}
	

	function getPageParam($paramName) {
	  $xml = new SimpleXMLElement($this->currentPage['pageParams']);
	  $result = $xml->xpath($paramName);
	  if(isset($result[0])) return (String) $result[0];
	}
	function refresh() {
		global $db;
				
		if($this->idkontrol===true) { //refresh
			$vid = $db->getRow("SELECT 
            u.name, u.skinId, u.avatar, u.zbanner, u.zavatar,
            u.zforumico, s.name, s.dir, u.ipcheck, u.zgalerytype, 
            u.info, u.email, u.icq ,u.dateCreated, u.dateLastVisit 
            FROM sys_users as u 
            LEFT JOIN sys_skin as s ON s.skinId = u.skinId 
            WHERE u.userId='".$this->gid."'");
			if(!DB::iserror($vid)) {
				$this->gidname = $vid[0];
				$this->skin = $vid[1];
				if(!empty($vid[2])) $this->ico = $vid[2]; else $this->ico = AVATAR_DEFAULT;
				$this->zbanner = $vid[3];
				$this->zidico = $vid[4];
				$this->zaudico = $vid[5];
				$this->skinName = $vid[6];
				$this->skinDir = $vid[7];
				$this->ipcheck = ($vid[8]==1)?(true):(false);
				$this->galtype = $vid[9];
				$this->xmlProperties = $vid[10];
				$this->homePageId = '';
				$this->email = $vid[11]; 
				$this->icq = $vid[12];
				$this->dateCreated = $vid[13];
				$this->dateLast = $vid[14];
			}
			
		}
	}
	
	function login($name,$pass){
	    $name = trim($name);
	    $pass = trim($pass);
		if (!empty($name) && !empty($pass) && $this->idkontrol==false){
			$db = FDBConn::getInstance();
			//---login query
			$dot = "SELECT u.userId FROM sys_users as u WHERE (deleted is null or deleted=0) and u.name='".$name."' and (u.password='".$pass."' or u.password='".md5($pass)."')";
      $gid = $db->getOne($dot);
			if(!empty($gid)) {
			   $this->userVO = new UserVO();
				$this->userVO->userId = $gid;
				$this->userVO->load();
				$this->userVO->idlogin = md5( $pass . $this->lo . fSystem::getmicrotime() );
				$this->smazoldid($gid);
				
				$db->query('insert into sys_users_logged (userId,loginId,dateCreated,dateUpdated,location,ip) values 
				("'.$gid.'","'.$this->userVO->idlogin.'",NOW(),NOW(),"'.$this->page->pageId.'","'.fSystem::getUserIp().'")');
				
				$this->idkontrol = true;
				
				
				$cache = FCache::getInstance( 's' );
				$cache->invalidate();
				
				$this->arrUsers = array();
        $this->cacheRemove(array('forumdesc', 'loggedlist', 'postwho'));
    
				$this->refresh();
				
				fForum::afavAll($this->gid); //----srovnani-seznamu-klubu-----
			    $this->strictBanner = array();
			    $this->strictBannerAllClicked = 0;
				$this->diarPrip(); //---remind from diary
				
			} else {
				fError::addError(ERROR_LOGIN_WRONGUSERORPASS);
			}
			
			fHTTP::redirect($this->getUri());
		}
	}
	function check($ipkontrol=true) {
		
		
		
		if(isset($_POST['lgn'])) $this->login($_POST['fna'],$_POST['fpa']);
		//---ip address checking
		if($this->idkontrol === true) { //---check only if user was logged
		
		$vid = $db->getRow("SELECT ul.ip, ul.loginId, ul.invalidatePerm, pf.book, pf.cnt
            FROM sys_users_logged as ul on ul.userId = '".$this->gid."' 
            LEFT JOIN sys_pages_favorites as pf on pf.pageId = p.pageId and pf.userId=ul.userId and p.pageId = '".$this->page->pageId."'");
                    $this->ip = $vid[20];
                    $this->idloginInDb = $vid[21];
                    if($vid[22] == 1) FRules::invalidate();
				    $this->favorite = $vid[23]*1;
				    $this->favoriteCnt = $vid[24]*1;
		
    		if(($ipkontrol === false || $this->ip == fSystem::getUserIp()) && ($this->idlogin == $this->idloginInDb)) {
    			$this->idloginInDb = 'chOK';
    		} else {
    		  //---user was logged but is lost - do logout acction
    		  $localUri = $this->getUri();
    		  
    		  $this->smazoldid($this->gid);
    		    global $user;
    			$user = new fUser();
    			
    			fError::addError(ERROR_USER_KICKED);
    			//---do redirect
    			fHTTP::redirect($this->getUri());
    			
    		}
		}
		return($this->idkontrol);
	}
	function invalidatePermissions() {
    $db = FDBConn::getInstance();
    $db->query("update `sys_users_logged` set invalidatePerm=1");
  }
	function kde($xajax=false) {
	 if(!$this->userVO) $this->userVO = new UserVO();
	
		$this->systemmenu = array();
	  $this->arrFriends = array();
		$this->arrCachePerLoad = array();
		$this->arrTooltips = array();
		$this->arrUsers['tooltips'] = array();
		
    //---security check
		//---logout action
		if( $this->page->pageId == 'elogo') {
		    if($this->idkontrol === true) {

    			$this->smazoldid($this->userVO->userId);
    			$this->user = new FUserVO(); 
    			
    			$cache = FCache::getInstance( 's' );
				$cache->invalidate();
    			    			
    			fError::addError(MESSAGE_LOGOUT_OK);
    			fHTTP::redirect('index.php');
        }  
		}
		
		//---try load current page
			    if($this->page) {
  	    $this->page->load();
	    }
	    
		//---if page not exists redirect to error
		if(empty($this->page->pageId)) {
		    $this->currentPageAccess = false;
		    fError::addError(ERROR_PAGE_NOTEXISTS);
		} else {
		  //---check if user is logged
		  $this->check($this->userVO->ipcheck);
		  //---check permissions needed for current page
		  $permissionNeeded = 1;
		  if(!empty($this->currentPageParam)) 
    		  if(isset($this->pageParamNeededPermission[$this->currentPageParam])) 
		          $permissionNeeded = $this->pageParamNeededPermission[$this->currentPageParam];
		  $permPage = $this->currentPageId;
		  if($permissionNeeded==3) {
		      $permPage = 'sadmi';
		      $permissionNeeded = 1;
		  }
		  if($permissionNeeded==4) {
		      ///check for i owner - permneeded=1 or permneeded= 2
		      $permissionNeeded = 2;
		      if(!empty($this->currentItemId)) {
		          $userIdOwner = fItems::getItemUserId($this->currentItemId);
		        if($this->gid == $userIdOwner) {
		            $permissionNeeded = 1;
		        }
		      } 
		  }
		  //check if user have access to page with current permissions needed - else redirect to error
		  if(!fRules::get($this->gid,$permPage,$permissionNeeded)) {
    		  $this->currentPageAccess = false;
		      fError::addError(ERROR_ACCESS_DENIED);
		  } else $this->currentPageAccess = true;
		
		  //logged user function
		  if($this->idkontrol === true) {
		    //---update user information
			$db->query("update sys_users_logged set invalidatePerm=0,dateUpdated = NOW(), 
			location = '".$this->currentPageId."', 
			params = '".$this->currentPageParam."'    
			where loginId='".$this->idlogin."'");
			
			$db->query("update sys_users set dateLastVisit = now(),hit=hit+1 where userId='".$this->gid."'");
			
			if($xajax === false) $db->query("INSERT INTO sys_pages_counter (`pageId` ,`typeId` ,`userId` ,`dateStamp` ,`hit`) VALUES ('".$this->currentPageId."', '".$this->currentPage['typeId']."', '".$this->gid."', NOW( ) , '1') on duplicate key update hit=hit+1");

		  }
		}
	}
	function setWhoIs($userId) {
	    if($this->isUserIdRegistered($userId)) $this->whoIs = $userId; else $this->whoIs=0;
	}
	
	function obliben($pageId=0,$userId=0) {
		global $db;
		if((empty($idusr) || $userId==$this->userId) && ($pageId==0 || $pageId==$this->currentPageId)) {
      return $this->favorite;
    } else {
		  return($db->getOne("select count(1) from sys_pages_favorites where book='1' AND pageId = '".$pageId."' AND userId = '".$userId."'"));
		}
	}
	/*.......smaze stary nebo stejny id z logged............*/
	function smazoldid($userId = 0,$casout = USERLIFETIME){
		$db = FDBConn::getInstance();
		$db->query("delete from sys_users_logged where DATE_ADD(dateUpdated,INTERVAL " . $casout." MINUTE) < NOW()");
		if ($userId > 0) $db->query("delete from sys_users_logged where userId='" . $userId . "'");
	}
	function getXMLVal($branch,$node,$default='') {
	    $xml = new SimpleXMLElement($this->xmlProperties);
	    if(isset($xml->$branch)) {
	       if(isset($xml->$branch->$node)) {
	           return $xml->$branch->$node;
	       }
	    }
	    return $default;
	}
	function setXMLVal($branch,$node,$value) {
	    $xml = new SimpleXMLElement($this->xmlProperties);
	    $xml->$branch->$node = $value;
	    $this->xmlProperties = $xml->asXML();
	}
	function infowrt(){
		Global $db;
		$sUser = new fSqlSaveTool('sys_users','userId');
		$sUser->addCol('email',$this->email);
		
		$sUser->addCol('info',$this->xmlProperties);
		
		$sUser->addCol('skinId',$this->skin);
		$sUser->addCol('icq',$this->icq);
		$sUser->addCol('zbanner',$this->zbanner);
		$sUser->addCol('zforumico',$this->zaudico);
		$sUser->addCol('zavatar',$this->zidico);
		$sUser->addCol('zgalerytype',$this->galtype);
		$sUser->addCol('avatar',$this->ico);
		if(!empty($this->newPassword)) $sUser->addCol('password',$this->newPassword);
		$sUser->addCol('userId',$this->gid);
		$sUser->addCol('dateUpdated','now()',false);
		$dot = $sUser->buildUpdate();
		$db->query($dot);
		//--refresh user information in session
		$this->refresh();

	}
	
	function register(){
		$reservedUsernames = array('admin','administrator','test','aaa','fuvatar','config');
		if(isset($_REQUEST["addusr"]) && $this->gid==0) {
				global $db;
				$jmenoreg = trim($_REQUEST["jmenoreg"]);
				$pwdreg1 = trim($_REQUEST["pwdreg1"]);
				$pwdreg2 = trim($_REQUEST["pwdreg2"]);
				if(strlen($jmenoreg)<2) fError::addError(ERROR_REGISTER_TOSHORTNAME);
				elseif(strlen($jmenoreg)>10) fError::addError(ERROR_REGISTER_TOLONGNAME);
				elseif (!fSystem::checkUsername($jmenoreg)) fError::addError(ERROR_REGISTER_NOTALLOWEDNAME);
				elseif($this->isUsernameRegistered($jmenoreg) || in_array($jmenoreg,$reservedUsernames)) fError::addError(ERROR_REGISTER_NAMEEXISTS);
				if($jmenoreg==$pwdreg1) fError::addError(ERROR_REGISTER_PASSWORDNOTSAFE);
				if(strlen($pwdreg1)<2) fError::addError(ERROR_REGISTER_PASSWORDTOSHORT);
				if($pwdreg1!=$pwdreg2) fError::addError(ERROR_REGISTER_PASSWORDDONTMATCH);
				if(!fError::isError()){
				    $dot = 'insert into sys_users (name,password,dateCreated,skinId,info) 
					values ("'.$jmenoreg.'","'.md5($pwdreg1).'",now(),1,"'.$this->xmlProperties.'")';
				    
					if($db->query($dot)) {
						$newiduser = $db->getOne("SELECT LAST_INSERT_ID()");
						fError::addError(MESSAGE_REGISTER_SUCCESS);
						//---oznameni o registraci
						$this->sendSAMessage(array('NEWUSERID'=>$newiduser,'NEWUSERNAME'=>$jmenoreg),MESSAGE_USER_NEWREGISTERED);
						fHTTP::redirect(BASESCRIPTNAME."?".ESID);
					}
				}
				
			fHTTP::redirect(BASESCRIPTNAME."?k=roger".ESID);
		}
	}
	function parseMessage($arrVars,$template) {
	    $message = $template;
	    if(!empty($arrVars)) foreach ($arrVars as $k=>$v) $message = str_replace('{'.$k.'}',$v,$message);
	    $message = str_replace('\"','"',$message);
	    $message = str_replace('"','\"',$message);
	    return $message;
	}
	function sendSAMessage($arrVars,$template) {
	    global $db;
	    $arr = $db->getCol('select userId from sys_users_perm where rules=2 and pageId="sadmi"');
	    if(!empty($arr)) {
	        $message = $this->parseMessage($arrVars,$template);
    	    foreach ($arr as $userId)  $this->send($userId,$message);
	    }
	}
	/**
	 * return username by userId
	 *
	 * @param int $gid
	 * @return String
	 */
	function getgidname($gid){
		if(!empty($gid)) {
			if(empty($this->arrUsers['username'][$gid])) {
				global $db;
				$this->arrUsers['username'][$gid] = $db->getOne("SELECT name FROM sys_users WHERE userId = '".$gid."'");
			}
			return $this->arrUsers['username'][$gid];
		}
	}
	function gnpost($gid=0){
		Global $db;
		if($gid==0) $gid=$this->gid;
		$dot = "select userIdFrom from sys_users_post where readed=0 AND userIdFrom!='".$gid."' AND userId='".$gid."' order by dateCreated desc";
		$npost = $db->getCol($dot);
		
		if(count($npost)>0) {
			$this->newPost = count($npost);
			$this->newPostFrom = $this->getgidname($npost[0]);
			return(true);
		} else {
			$this->newPost = 0;
			$this->newPostFrom = '';
			return(false);
		}
	}
	function isOnline($userId){
		Global $db;
		if(!isset($this->arrCachePerLoad['onlineUsers'][$userId])) {
		  return $this->arrCachePerLoad['onlineUsers'][$userId] = $db->getOne("select count(1) from sys_users_logged where subdate(NOW(),interval ".USERVIEWONLINE." minute)<dateUpdated AND userId=".$userId);
		} else {
		    return $this->arrCachePerLoad['onlineUsers'][$userId];
		}
	}
	function get($userId) {
	 global $db;
	    $arr = $db->getRow("SELECT u.userId,u.name,u.email,u.icq,u.info,
            date_format(u.dateCreated,'%H:%i:%S %d.%m.%Y') as dateCreatedCz,
            date_format(u.dateUpdated,'%H:%i:%S %d.%m.%Y') as dateUpdatedCz 
            FROM sys_users as u 
            WHERE u.userId = '".$userId."'");
	    if(!empty($arr)) {
	        $xml = new SimpleXMLElement($arr[4]);
            $arr['personal'] = $xml->personal[0];
	        return $arr;
	    }
	}
	function getLocation($userId) {
		global $db;
		if(!isset($this->arrCachePerLoad['onlineUserLocation'][$userId])) {
		    $query = "SELECT s.location,s.params,ss.nameshort,ss.name 
    		FROM sys_users_logged as s join sys_pages as ss on s.location=ss.pageId and s.userId='".$userId."'";
		    $rid = $db->getRow($query);
		    if (!empty($rid)) {
		      return $this->arrCachePerLoad['onlineUserLocation'][$userId] = array('pageId'=>$rid[0],'param'=>$rid[1],'nameshort'=>$rid[2],'name'=>$rid[3]);
		    } else $this->arrCachePerLoad['onlineUserLocation'][$userId] = false;
		} else return $this->arrCachePerLoad['onlineUserLocation'][$userId];
	}
	static function isUserIdRegistered($userId) {
		$cache = FCache::getInstance('l');
		if( $ret = $cache->getData($name,'isId') !== false ) {
		  $db = FDBConn::getInstance();
		  $ret = $db->getOne("select count(1) from sys_users where userId='".$userId."'");
		  $cache->setData($ret);
		}
		return $ret;
	}
	static function isUsernameRegistered($name){
		$cache = FCache::getInstance('l');
		if( $ret = $cache->getData($name,'isReg') !== false ) {
		  $db = FDBConn::getInstance();
		  $ret = $db->getOne("select count(1) from sys_users where (deleted=0 or deleted is null) and name like '".$name."'");
		  $cache->setData($ret);
		}
		return $ret;
	}
	/**
	 * load userId if username exists
	 *
	 * @param unknown_type $name
	 * @return unknown
	 */
	static function getUserIdByName($name){
	 $db = FDBConn::getInstance();
	 $cache = FCache::getInstance('l');
		if($id = $cache->getData($name,'idByName')===false)) {
		  $id = $db->getOne("select userId from sys_users where deleted=0 and name='".$name."'");
		  $cache->setData($id,$name,'idByName');
		}
		return $id;
	}
	function getDiaryCnt($usrid=0) {
		global $db;
		if(empty($usrid)) $usrid = $this->gid;
		return $db->getOne("select count(1) from sys_users_diary where (userId='".$usrid."' or eventForAll=1) and year(dateEvent)=year(now()) and month(dateEvent)=month(now()) and dayofmonth(dateEvent)=dayofmonth(now())");
	}
	function pocitadlo(){
		$db = FDBConn::getInstance();
		$hits = $db->getOne("select sum(hit) from sys_users");
		return $hits;
	}
	function statAudit($aud,$count=true){
		Global  $db;
		if($count) $str=$db->getOne("select count(1) from sys_pages_items where pageId='".$aud."' AND userId='".$this->gid."'");
		else $str="ins+1";
		$db->query("update sys_pages_counter set ins=".$str." WHERE pageId='".$aud."'and dateStamp=now() AND userId='".$this->gid."'");
	}
	///----FRIENDS managments
	function pritel($userId) {
		Global  $db;
		$arr = $this->getFriends($this->gid);
		return(in_array($userId,$arr));
	}
	function addpritel($book_idpra) {
		Global  $db;
		if(!is_array($book_idpra)) $book_idpra = array($book_idpra);
		foreach ($book_idpra as $friendId) {
			$db->query("insert into sys_users_friends (userId,userIdFriend,dateCreated) values ('" . $this->gid . "','" . $friendId . "',NOW())");
			$this->arrFriends[$this->gid][$friendId] = $friendId;
		}
	}
	function delpritel($unbook_id) {
		global $db;
		if($this->idkontrol) {
			$db->query('delete from sys_users_friends where userId='.$this->gid.' and userIdFriend='.$unbook_id);
			//$this->getFriends(0,true);
			unset($this->arrFriends[$this->gid][$unbook_id]);
		}
	}
	function getFriends($userId=0,$refresh=false) {
		if(empty($userId)) $userId = $this->gid;
		if($refresh || !isset($this->arrFriends[$userId])) {
			$db = FDBConn::getInstance();
			$arr = $db->getAll("SELECT p.userIdFriend,s.name 
			FROM sys_users_friends as p left join sys_users as s on p.userIdFriend = s.userId 
			WHERE p.userId = ".$userId." ORDER BY s.name");
			
			if(!empty($arr)) 
				foreach ($arr as $row) {
					$this->arrFriends[$userId][$row[0]] = $row[0];
					$this->arrUsers['username'][$row[0]] = $row[1];
				}
			else $this->arrFriends[$userId] = array();
		}
		return $this->arrFriends[$userId];
	}
	function koment($idusr,$koho) {
		if($idusr!=0) {
			global $db;
			if($v_id=$db->getOne("SELECT comment FROM sys_users_friends WHERE userId=".$koho." AND userIdFriend = ".$idusr)) return($v_id);
		}
	}
	
	function getAvatarUrl($userId=-1){
		$picname = WEB_REL_AVATAR . AVATAR_DEFAULT;
		if($userId==-1) $picname = WEB_REL_AVATAR . $this->ico; //---myself
		elseif($userId > 0) {
            if(!isset($this->arrUsers['avatarUrl'][$userId])) {
			   global $db;
			   $userAvatar = WEB_REL_AVATAR . $db->getOne("SELECT avatar FROM sys_users WHERE userId = '".$userId."'");
               if(file_exists($userAvatar) && !is_dir($userAvatar)) $picname = $this->arrUsers['avatarUrl'][$userId] = $userAvatar;
            } else $picname = $this->arrUsers['avatarUrl'][$userId];
		}
		return($picname);
	}
	/**
	 * Enter description here...
	 *
	 * @param int $userId
	 * @param array $paramsArr
	 * @return html formated avatar
	 */
	function showAvatar($userId=-1,$paramsArr = array()){
        if(isset($paramsArr['class'])) $class = $paramsArr['class'];
        $showName = (isset($paramsArr['showName']))?(true):(false);
        $noTooltip = (isset($paramsArr['noTooltip']))?(true):(false);
        
	    $this->arrUsers['avatar'] = array();
	    
	 $avatarUserId = ($userId==-1)?($this->gid):($userId);
	 if(!isset($this->arrUsers['avatar'])) $this->arrUsers['avatar'] = array();
	 if(!isset($this->arrUsers['avatar'][$avatarUserId])) $this->arrUsers['avatar'][$avatarUserId] = false;
    
	 if(!$ret = $this->arrUsers['avatar'][$avatarUserId]) {
	   $tpl = new fTemplateIT('user.avatar.tpl.html');
	   
     if($userId==-1 ) $avatarUserName = $this->gidname;
     elseif($userId > 0) $avatarUserName = $this->getgidname($avatarUserId);
     else $avatarUserName = '';
	
     if($showName) $tpl->setVariable('USERNAME',$avatarUserName);
     if($this->zidico==1) {
      $tpl->setVariable('AVATARURL',$this->getAvatarUrl(($userId==-1)?(-1):($avatarUserId)));
      $tpl->setVariable('AVATARUSERNAME',$avatarUserName);
      if(isset($class)) $tpl->setVariable('AVATARCLASS',$class);
     }
    
     if($this->idkontrol && $avatarUserId>0) {
      $avatarUrl = BASESCRIPTNAME.'?k=finfo&who='.$avatarUserId;
      if($showName) {
        $tpl->setVariable('NAMEURL',$avatarUserName);
        if($noTooltip==false) $tpl->setVariable('NAMECLASS','supernote-hover-avatar'.$avatarUserId);
        $tpl->touchBlock('linknameend');
      }
      if($this->zidico) {
        $tpl->setVariable('AVATARLINK',$avatarUrl);
        if($noTooltip==false) $tpl->setVariable('AVATARLINKCLASS','supernote-hover-avatar'.$avatarUserId);
        $tpl->touchBlock('linkavatarend');
      }
    
     }
      			
  			$tpl->parse('useravatar');
  			
  			$ret = $this->arrUsers['avatar'][$avatarUserId] = $tpl->get('useravatar');
		}
		
		if($noTooltip==false && $this->idkontrol==true && $avatarUserId > 0 && !isset($this->arrUsers['tooltips'][$avatarUserId])) {
		  
			$avatarUserName = ($userId==-1)?($this->gidname):($this->getgidname($userId));
     		if(!isset($tpl)) $tpl = new fTemplateIT('user.avatar.tpl.html');
			///TOOLTIP - generated just once
  			
	      $tpl->setVariable('TOOLTIPID','supernote-note-avatar'.$avatarUserId);
	      $tpl->setVariable('TIPCLASS','snp-mouseoffset notemenu');
	      $tpl->setVariable('TIPUSERNAME',$avatarUserName);
	      
	      $arrLinks = array(
	        array('url'=>'?k=finfo&who='.$avatarUserId,'text'=>LABEL_INFO),
	        array('url'=>'?k=fpost&who='.$avatarUserId,'text'=>LABEL_POST),
	      );
				if($avatarUserId!=$this->gid) $arrLinks[] = array('url'=>'#','id'=>'avbook'.$avatarUserId,'click'=>"xajax_user_switchFriend('".$avatarUserId."','avbook".$avatarUserId."');return(false);",'text'=>(($this->pritel($avatarUserId))?(LABEL_FRIEND_REMOVE):(LABEL_FRIEND_ADD)));

  			
  			foreach ($arrLinks as $tip) {
		        $tpl->setCurrentBlock('tip');
		        $tpl->setVariable('TIPURL',$tip['url']);
		        if(isset($tip['id'])) $tpl->setVariable('TIPID',$tip['id']);
		        if(isset($tip['click'])) $tpl->setVariable('TIPCLICK',$tip['click']);
		        $tpl->setVariable('TIPTEXT',$tip['text']);
		        $tpl->parseCurrentBlock();
		      }
		     $tpl->parse('tooltip');
		     $this->arrUsers['tooltips'][$avatarUserId] = $tpl->get('tooltip');
 		}
		return $ret;
	}
	function send($komu,$zprava,$odkoho=LAMA_USER) {
		//odkoho=75 id lama
		$db = FDBConn::getInstance();
		
		$dot = "insert into sys_users_post (userId,userIdTo,userIdFrom,dateCreated,text,readed,postIdFrom) 
		values (".$komu.",".$komu.",".$odkoho.",NOW(),'".$zprava."',0,null)";
		$db->query($dot);
		$maxid = $db->getOne("SELECT LAST_INSERT_ID()");
		$dot = "insert into sys_users_post (userId,userIdTo,userIdFrom,dateCreated,postIdFrom,text,readed) 
		values (".$odkoho.",".$komu.",".$odkoho.",NOW(),".$maxid.",'".$zprava."',0)";
		$db->query($dot);
		$this->cacheRemove('postwho');
	}
   function deletePost($messageId) { //--might be array or not
       global $db;
	if(!is_array($messageId)) $messageId[] = $messageId; 
	 $db->query("delete from sys_users_post where postId in (" . implode(',',$messageId).")");
	 $this->cacheRemove('postwho');
  }
  
  //---get post
  function getPost($from,$perpage,$count=false) {
    $db = FDBConn::getInstance();
    
    $base = ' FROM sys_users_post WHERE userId='.$this->gid;
    
    if($filterText = $this->filterGet($this->currentPageId,'text')) $base.=" AND lower(text) LIKE '%".strtolower($filterText)."%'";
    if($filterUsername = $this->filterGet($this->currentPageId,'username')) {
    	$filterUserId = $this->getUserIdByName($filterUsername);
    	if($filterUserId > 0) $base.=" AND (userIdTo='".$filterUserId."' OR userIdFrom='".$filterUserId."')";
    }
	   
	   $d_post = "SELECT postId,userId,userIdTo,userIdFrom,
    date_format(dateCreated,'%H:%i:%S %d.%m.%Y'),text,readed,date_format(dateCreated,'%Y-%m-%dT%T')".$base." ORDER BY dateCreated DESC";
	 if($count==true) return $db->getOne('select count(1) '.$base);
     else {
     	$arr = $db->getAll($d_post.' limit '.$from.','.$perpage);
     	foreach ($arr as $row) {
     		$arrRet[] = array('postId'=>$row[0],'userId'=>$row[1],'userIdTo'=>$row[2],
     		'userIdFrom'=>$row[3],'datumcz'=>$row[4],'text'=>$row[5],'readed'=>$row[6],'datum'=>$row[7]); 
     	}
     	return $arrRet;
     }
  }
	
	function diarPrip(){
		$sentCount = 0;
		$fQuery = new fQueryTool('sys_users_diary');
		$fQuery->setSelect("diaryId, name, date_format(dateEvent,'{#date_local#}'), everyday, reminder, userId, date_format(dateEvent,'{#date_iso#}')");
		$fQuery->setWhere("DATE_SUB(dateEvent,INTERVAL (reminder-1) DAY)<=NOW() AND reminder != 0");
		$arr = $fQuery->getContent();
		if(!empty($arr)){
		  $db = FDBConn::getInstance();
			foreach($arr as $row){
				if($row[3]==1) $newprip=$row[4]-1; else $newprip=0;
				$dot = "UPDATE sys_users_diary SET reminder=$newprip WHERE diaryId=".$row[0];
				$db->query($dot);
				$arrVars = array('LINK'=>BASESCRIPTNAME.'?k=fdiar&ddate='.$row[6],'NAME'=>$row[1],'DATE'=>$row[2]);
				$message = $this->parseMessage($arrVars,MESSAGE_DIARY_REMINDER);
				$this->send($row[5],$message);
				$sentCount++;
			}
		}
		return $sentCount;
	}
	static function getSkinCSSFilename() {
		$skin = SKIN_DEFAULT;
		//---TODO: from userVO load custom skin name
		//if(is_dir(WEB_REL_CSS.$this->skinDir) $skin = $this->skinDir;
		return(WEB_REL_CSS.$skin);
	}
	
	static function getUri($otherParams='',$pageId='',$pageParam=false) {
	   $user = FUser::getInstance();
	   $pageParam = ($pageParam===false)?($user->currentPageParam):($pageParam);
	    if(empty($pageId) && $user->itemVO->itemId>0) $params[] = 'i='.$user->itemVO->itemId;
	    if(!empty($pageId)) $params[] = 'k='.$pageId.$pageParam;
	    elseif(!empty($user->pageVO->pageId)) $params[] = 'k='.$user->pageVO->pageId.$pageParam;
	    if($otherParams!='') $params[] = $otherParams;
	    $parStr = implode("&",$params);
		return BASESCRIPTNAME.(strlen($parStr)>0)?('?'.$parStr):('');
	}
		
    //---fuvatar support functions--------------------------------------
    function fuvatarAccess($userName) {
        global $db;
        $ret = false;
        if($userName == $this->gidname) $ret = true;
        else {
            $row = $db->getRow('select userId,info from sys_users where name="'.$userName.'"');
            if(!empty($row)) {
                $xml = new SimpleXMLElement($row[1]);
                switch ($xml->webcam->public) {
                    case 0:
                        $ret = true;
                        break;
                    case 1:
                        if($this->gid > 0) $ret = true;
                        break;
                    case 2:
                        $arr = $this->getFriends($row[0]);
                        if(!empty($arr)) {
                            if(in_array($this->gid,$arr)) $ret = true;
                        }
                        break;
                    case 3:
                        $chosen = $xml->webcam->chosen;
                        if(!empty($chosen)) {
                            $arrChosen = explode(',',$chosen);
                            if(in_array($this->gid,$arrChosen)) $ret = true;
                        }
                        break;
                }
            }
        }
        return $ret;
    }
    function updateAvatarFromWebcam($filename) {
        if($this->getXMLVal('webcam','avatar') == 1) {
            //---RESIZE
            $resizeParams = array('quality'=>80,'crop'=>1,'width'=>AVATAR_WIDTH_PX,'height'=>AVATAR_HEIGHT_PX);
            $iProc = new fImgProcess($filename,WEB_REL_AVATAR . $this->ico,$resizeParams);
        }
    } 
}