<?php
class FUser {

	//---security salt
	const LO = 'fdk5.salt';

	var $userVO;
	var $pageVO;
	var $itemVO;

	var $pageParam; //---replacing ->currentPageParam

	//---used when looking after someone informations
	var $whoIs = 0; //---replace with whoIsUserVO

	//---user access
	var $idkontrol = false;
	var $pageAccess = false;

	var $pageParamNeededPermission = array(
	'e'=>2, //edit (galery,forum,blog)
	'u'=>4, //event,blog - podle majitele - nebo ten kdo ma dve pro stranku
	'h'=>1, //home - u klubu - home z XML
	's'=>1, //statistika - vestinou u klubu, muze byt kdekoliv
	'p'=>1, //anketa nastaveni
	'sa'=>3, //super admin - nastavovani prav a ostatniho nastaveni u kazdy stranky .. uzivatel musi mit prava 2 ke strance sadmi
	);

	private static $instance;

	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = &new FUser();
		}
		return self::$instance;
	}

	/**
	 * check if user is logged in or not
	 * @return Number/Boolean - userId if logged in / false - nobody logged in system
	 */
	static function logon() {
		$user = FUser::getInstance();
		if($user->idkontrol === true) {
			return $user->userVO->userId;
		} else {
			return false;
		}
	}

	static function getToken($tokenizer) {
		return md5( $tokenizer . FUser::LO . FSystem::getmicrotime() );
	}

	/**
	 * Log-IN user into system
	 * @param $name - string
	 * @param $pass - string
	 * @return void
	 */
	function login($name,$pass){
		$name = trim($name);
		$pass = trim($pass);
		if (!empty($name) && !empty($pass) && $this->idkontrol==false) {
			//---login query
			$dot = "SELECT u.userId FROM sys_users as u WHERE (deleted is null or deleted=0) and u.name='".$name."' and (u.password='".$pass."' or u.password='".md5($pass)."')";
			$gid = FDBTool::getOne($dot);
			if( $gid > 0 ) {
				$this->userVO = new UserVO($gid, true);
				$this->userVO->idlogin = FUser::getToken($pass);
				$this->userVO->ip = FSystem::getUserIp();
				FUser::invalidateUsers($gid);
				//---db logon
				FDBTool::query('insert into sys_users_logged (userId,loginId,dateCreated,dateUpdated,location,ip) values
				("'.$gid.'","'.$this->userVO->idlogin.'",NOW(),NOW(),"'.$this->pageVO->pageId.'","'.$this->userVO->ip.'")');
				//---logon
				$this->idkontrol = true;
				//---session cache
				$cache = FCache::getInstance( 's' );
				$cache->invalidate();
				$cache->setData($this->userVO,'user');
				//---file cache
				$cache = FCache::getInstance( 'f' );
				$cache->invalidateData('forumdesc');
				$cache->invalidateData('loggedlist');
				$cache->invalidateData('postwho');
				FItems::afavAll($gid); //----srovnani-seznamu-klubu-----
				FMessages::diaryNotifications(); //---remind from diary
			} else {
				FError::addError(FLang::$ERROR_LOGIN_WRONGUSERORPASS);
			}
			FHTTP::redirect(FUser::getUri());
		}
	}

	/**
	 * logout current user
	 * @return void
	 */
	function logout() {
		FUser::invalidateUsers($this->userVO->userId);
		$this->idkontrol = false;
		$this->user = new UserVO();
		$cache = FCache::getInstance( 's' );
		$cache->invalidate();
	}

	/**
	 * check current user state
	 * @param $ipkontrol
	 * @return Boolean - true login / false out of system
	 */
	function check() {
		if($this->userVO->userId > 0) { //---check only if user was logged
			if($this->pageVO) {
				$q = "SELECT ul.loginId, ul.invalidatePerm, pf.book, pf.cnt
            	FROM sys_users_logged as ul  
            	LEFT JOIN sys_pages_favorites as pf on pf.userId=ul.userId and pf.pageId = '".$this->pageVO->pageId."'  
            	where ul.userId = '".$this->userVO->userId."'";
			} else {
				$q = "SELECT loginId, invalidatePerm
            	FROM sys_users_logged    
            	where userId = '".$this->userVO->userId."'";	
			}

			$vid = FDBTool::getRow($q);

			$idloginInDb = $vid[0];
			if($vid[1] == 1) FRules::invalidate();

			if($this->pageVO) {
				$this->pageVO->favorite = $vid[2]*1;
				$this->pageVO->favoriteCnt = $vid[3]*1;
			}
			
			//---ip address checking
			if(($this->userVO->ipcheck === false || $this->userVO->ip == FSystem::getUserIp()) 
				&& ($this->userVO->idlogin == $idloginInDb)) {
				//---user allright
				$this->idkontrol = true;
			} else {
				//---user was logged but is lost - do logout acction
				$this->logout();
				FError::addError(FLang::$ERROR_USER_KICKED);
				if($this->pageVO) {
					//---do redirect
					FHTTP::redirect(FUser::getUri());
				}
			}
		}
		return($this->idkontrol);
	}

	/**
	 * BASE FUNCTION to verify page access and user validation
	 * @param $xajax - only reason is when called from ajax function not to count into page statistics
	 * @return void
	 */
	function kde() {
		if(!$this->userVO) {
			//---try to load user from cache
			$cache = FCache::getInstance('s');
			if(false === ($this->userVO = $cache->getData('user'))) {
				$this->userVO = new UserVO();
			}
		}
		if($this->pageVO) {
			//---logout action
			if( $this->pageVO->pageId == 'elogo') {
				if($this->userVO->userId > 0) {
					$this->logout();
					FError::addError(FLang::$MESSAGE_LOGOUT_OK);
					FHTTP::redirect('index.php');
				}
			}
			//---try load current page
			$this->pageVO->load();
			if(empty($this->pageVO->pageId)) {
				$this->pageAccess = false;
				FError::addError(FLang::$ERROR_PAGE_NOTEXISTS);
			} else {
				$this->pageAccess = true;
			}
		}
		//---if page not exists redirect to error
		if($this->pageAccess == true) {
			//---check if user sent data to login
			if(isset($_POST['lgn'])) $this->login($_POST['fna'],$_POST['fpa']);
			//---check if user is logged
			$this->check();
			//---check permissions needed for current page
			$permissionNeeded = 1;
			if(!empty($this->pageParam)) {
				if(isset($this->pageParamNeededPermission[$this->pageParam])) {
					$permissionNeeded = $this->pageParamNeededPermission[$this->pageParam];
				}
			}
			if($this->pageVO) {
				$permPage = $this->pageVO->pageId;
				if($permissionNeeded==3) {
					$permPage = 'sadmi';
					$permissionNeeded = 1;
				}
				if($permissionNeeded==4) {
					///check for i owner - permneeded=1 or permneeded= 2
					$permissionNeeded = 2;
					if(!empty($this->itemVO->itemId)) {
						$userIdOwner = $this->itemVO->userId;
						if($this->userVO->userId == $userIdOwner) {
							$permissionNeeded = 1;
						}
					}
				}
				//check if user have access to page with current permissions needed - else redirect to error
				if(!FRules::get($this->userVO->userId,$permPage,$permissionNeeded)) {
					$this->pageAccess = false;
					FError::addError(FLang::$ERROR_ACCESS_DENIED);
				} else {
					$this->pageAccess = true;
					$cache = FCache::getInstance('s');
					$cache->setData($this->pageVO->pageId,'lastPage');
				}
			}
			//logged user function
			if($this->idkontrol === true) {
				//---update user information
				if($this->userVO->strictLogin === true) {
					$this->userVO->idlogin = FUser::getToken($this->userVO->password);
				}

				FDBTool::query("update sys_users_logged set invalidatePerm=0,dateUpdated = NOW(),
			location = '".(($this->pageVO)?($this->pageVO->pageId):(''))."', 
			params = '".$this->pageParam."'    
			where loginId='".$this->userVO->idlogin."'");
					
				FDBTool::query("update sys_users set dateLastVisit = now(),hit=hit+1 where userId='".$this->userVO->userId."'");

			}
		}
	}
	
	function pageStat() {
		FDBTool::query("INSERT INTO sys_pages_counter (`pageId` ,`typeId` ,`userId` ,`dateStamp` ,`hit`) VALUES ('".$this->pageVO->pageId."', '".$this->pageVO->typeId."', '".$this->userVO->userId."', NOW( ) , '1') on duplicate key update hit=hit+1");
	}

	function setWhoIs($userId) {
		if(FUser::isUserIdRegistered($userId)) $this->whoIs = $userId; else $this->whoIs=0;
	}

	/**
	 * Check if page is users favorite/booked
	 * @param $pageId
	 * @param $userId
	 * @return Boolean - true if page is users favorite/booked page
	 */
	function isPageFavorite($pageId=0, $userId=0) {
		if(($userId==0 || $userId==$this->userVO->userId) && ($pageId==0 || $pageId==$this->pageVO->pageId)) {
			$favorite = $this->pageVO->favorite;
		} else {
			$favorite = FDBTool::getOne("select count(1) from sys_pages_favorites where book='1' AND pageId = '".$pageId."' AND userId = '".$userId."'");
		}
		return $favorite;
	}

	/**
	 * Clean up logged users in db
	 * @param $userId - not mandatory - if specified kicks user from system
	 * @param $timeOut - lifetime of user logged in system
	 * @return void
	 */
	static function invalidateUsers($userId = 0,$timeOut = USERLIFETIME){
		FDBTool::query("delete from sys_users_logged where DATE_ADD(dateUpdated,INTERVAL " . $timeOut . " MINUTE) < NOW()");
		if ($userId > 0) FDBTool::query("delete from sys_users_logged where userId='" . $userId . "'");
	}

	/**
	 * register new user to system
	 * @return void
	 */
	function register(){
		$reservedUsernames = array('admin','administrator','test','aaa','fuvatar','config');
		if(isset($data["addusr"]) && $this->idkontrol === false) {
			$jmenoreg = trim($data["jmenoreg"]);
			$pwdreg1 = trim($data["pwdreg1"]);
			$pwdreg2 = trim($data["pwdreg2"]);
			if(strlen($jmenoreg)<2) FError::addError(FLang::$ERROR_REGISTER_TOSHORTNAME);
			elseif(strlen($jmenoreg)>10) FError::addError(FLang::$ERROR_REGISTER_TOLONGNAME);
			elseif (!FSystem::checkUsername($jmenoreg)) FError::addError(FLang::$ERROR_REGISTER_NOTALLOWEDNAME);
			elseif($this->isUsernameRegistered($jmenoreg) || in_array($jmenoreg,$reservedUsernames)) FError::addError(FLang::$ERROR_REGISTER_NAMEEXISTS);
			if($jmenoreg==$pwdreg1) FError::addError(FLang::$ERROR_REGISTER_PASSWORDNOTSAFE);
			if(strlen($pwdreg1)<2) FError::addError(FLang::$ERROR_REGISTER_PASSWORDTOSHORT);
			if($pwdreg1!=$pwdreg2) FError::addError(FLang::$ERROR_REGISTER_PASSWORDDONTMATCH);
			if(!FError::isError()){
				$dot = 'insert into sys_users (name,password,dateCreated,skinId,info)
					values ("'.$jmenoreg.'","'.md5($pwdreg1).'",now(),1,"'.$this->userVO->info.'")';
				if(FDBTool::query($dot)) {
					$newiduser = FDBTool::getOne("SELECT LAST_INSERT_ID()");
					FError::addError(FLang::$MESSAGE_REGISTER_SUCCESS);
					//---oznameni o registraci
					FMessages::sendSAMessage(array('NEWUSERID'=>$newiduser,'NEWUSERNAME'=>$jmenoreg),FLang::$MESSAGE_USER_NEWREGISTERED);
					FHTTP::redirect(FUser::getUri('',HOME_PAGE));
				}
			}
			FHTTP::redirect(FUser::getUri());
		}
	}

	/**
	 * return username by userId
	 *
	 * @param int $gid
	 * @return String
	 */
	static function getgidname($userId){
		$q = "SELECT name FROM sys_users WHERE userId = '".$userId."'";
		return FDBTool::getOne($q, $userId, 'Uname', 'l');
	}

	/**
	 * check if user is online
	 * @param $userId
	 * @return [0,1]
	 */
	function isOnline($userId){
		$q = "select count(1) from sys_users_logged where subdate(NOW(),interval ".USERVIEWONLINE." minute)<dateUpdated AND userId=".$userId;
		return FDBTool::getOne($q, $userId, 'isOn', 'l');
	}

	/**
	 * return location of specified user
	 * @param $userId
	 * @return array[pageId,param,nameshort,name]
	 */
	static function getLocation($userId) {
		$cache = FCache::getInstance('l');
		if(($loc = $cache->getData($userId, 'Uloc')) !== false) {
			$query = "SELECT s.location,s.params,ss.nameshort,ss.name
    		FROM sys_users_logged as s join sys_pages as ss on s.location=ss.pageId and s.userId='".$userId."'";
			$rid = $db->getRow($query);
			if (!empty($rid)) {
				$loc = array('pageId'=>$rid[0],'param'=>$rid[1],'nameshort'=>$rid[2],'name'=>$rid[3]);
				$cache->setData( $loc );
			} else $cache->setData( '' );
		}
		return $loc;
	}

	/**
	 * check if userId is registered
	 * @param $userId
	 * @return [0,1]
	 */
	static function isUserIdRegistered($userId) {
		$q = "select count(1) from sys_users where userId='".$userId."'";
		return FDBTool::getOne($q, $userId, 'isId', 'l');
	}

	/**
	 * check if username is registered
	 * @param $name
	 * @return [0,1]
	 */
	static function isUsernameRegistered($name){
		$q = "select count(1) from sys_users where (deleted=0 or deleted is null) and name like '".$name."'";
		return FDBTool::getOne($q, $name, 'isReg', 'l');
	}

	/**
	 * load userId if username exists
	 * @param string $name
	 * @return number UserId
	 */
	static function getUserIdByName($name){
		$q = "select userId from sys_users where deleted=0 and name='".$name."'";
		return FDBTool::getOne($q, $name, 'idByN', 'l');
	}

	/**
	 * get skin name
	 * @return string - url
	 */
	static function getSkinCSSFilename() {
		$skin = SKIN_DEFAULT;
		//---TODO: from userVO load custom skin name
		//if(is_dir(WEB_REL_CSS.$this->skinDir) $skin = $this->skinDir;
		return(WEB_REL_CSS.$skin);
	}

	/**
	 * Build local path for redirects, buttons, etc.
	 * @param $otherParams
	 * @param $pageId
	 * @param $pageParam
	 * @return string - URL
	 */
	static function getUri($otherParams='',$pageId='',$pageParam=false, $scriptName=BASESCRIPTNAME) {
		$otherParams = str_replace('&',SEPARATOR,$otherParams);
		$user = FUser::getInstance();
		$pageParam = ($pageParam===false)?($user->pageParam):($pageParam);

		$newPageId = $user->pageVO->pageId;
		if(!empty($pageId)) $newPageId = $pageId;
		if($newPageId == HOME_PAGE && empty($pageParam)) $newPageId = '';

		if( empty($pageId) && $user->itemVO->itemId > 0 ) {
			$params[] = 'i='.$user->itemVO->itemId;
			if(empty($pageParam)) $newPageId = '';
		}
		
		if(!empty($newPageId)) {
			if(empty($pageParam)) {
				$pageVO  = new PageVO($newPageId,true);
				$safeName = FSystem::safetext($pageVO->name);
			}
			$params[] = 'k=' . $newPageId . $pageParam . ((!empty($safeName))?('-'.$safeName):(''));
			$params = array_reverse($params);
		}
		if($otherParams!='') $params[] = $otherParams;
		$parStr = '';
		if(isset($params)) {
			$parStr = '?'.implode(SEPARATOR,$params);
		}
		return $scriptName . $parStr;
	}
}