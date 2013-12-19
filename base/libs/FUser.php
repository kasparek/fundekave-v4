<?php
class FUser {

	//---security salt
	const LO = 'fdk5.salt';

	var $userVO;

	var $pageId;
	var $pageParam;
	var $pageVO;

	var $itemVO;
	
	var $categoryVO;

	//---used when looking after someone informations
	var $whoIs = 0; //---replace with whoIsUserVO

	//---user access
	var $idkontrol = false;
	var $pageAccess = false;
	
	var $pageParamNeededPermission = array(
	'e'=>2, //edit (galery,forum,blog)
	'u'=>4, //event,blog - podle majitele - nebo ten kdo ma dve pro stranku
	'sa'=>3, //super admin - nastavovani prav a ostatniho nastaveni u kazdy stranky .. uzivatel musi mit prava 2 ke strance sadmi
	);

	private static $instance;

	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new FUser();
		}
		return self::$instance;
	}
	
	function setRemoteAuthToken($v) {
	  $this->userVO->idlogin = $v;
	}
	
	function getRemoteAuthToken() {
		if (!$this->idkontrol) return '';
		return $this->userVO->idlogin;
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
		return md5( $tokenizer . FUser::LO . uniqid('U') );
	}

	/**
	 * Log-IN user into system
	 * @param $name - string
	 * @param $pass - string
	 * @return void
	 */
	static function login($name,$pass,$pageId='') {
		$name = trim($name);
		$pass = trim($pass);
		if (!empty($name) && !empty($pass)) {
			//---login query
			$dot = "SELECT u.userId FROM sys_users as u WHERE (deleted is null or deleted=0) and (u.email='".$name."' or u.name='".$name."') and (u.password='".$pass."' or u.password='".md5($pass)."')";
			$gid = FDBTool::getOne($dot);
			if( $gid > 0 ) {
				$userVO = new UserVO($gid, true);
				$userVO->idlogin = FUser::getToken($pass);
				$userVO->ip = FSystem::getUserIp();
				FUser::invalidateUsers($gid);
				//---db logon
				FDBTool::query('insert into sys_users_logged (userId,loginId,dateCreated,dateUpdated,location,ip,sessId) values
				("'.$gid.'","'.$userVO->idlogin.'",NOW(),NOW(),"'.$pageId.'","'.$userVO->ip.'","'.session_id().'")');
				//user total item num
				$userVO->itemsLastNum = (int) $userVO->prop('itemsNum');
				//---session cache
				$cache = FCache::getInstance( 's' );
				$cache->invalidate();
				$pUserVO = &$cache->getPointer('user');
				$pUserVO = $userVO;
			} else {
				FError::add(FLang::$ERROR_LOGIN_WRONGUSERORPASS);
			}
			if($pageId!='') FHTTP::redirect(FSystem::getUri('',$pageId,''));
		}
	}

	/**
	 * logout current user
	 * @return void
	 */
	static function logout( $userId ) {
		FUser::invalidateUsers( $userId );
		$cache = FCache::getInstance( 's' );
		$cache->invalidate();
		//delete session
		$_SESSION = array();
		if(ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time()-42000,$params["path"], $params["domain"],$params["secure"], $params["httponly"]);
		}
	}

	/**
	 * check current user state
	 * @param $ipkontrol
	 * @return Boolean - true login / false out of system
	 */
	function check( $userVO ) {
		$ret = false;
		if($userVO->userId > 0 || !empty($userVO->idlogin)) { //---check only if user was logged
		  if($userVO->userId > 0) {
				$q = "select ul.loginId";
			} else {
			  $q = "select ul.userId";
			}
			$fajax = FAjax::getInstance();
			if( $this->pageId && empty($fajax->data['__ajaxResponse']) ) {
				$q .= ", ul.invalidatePerm, pf.book, pf.cnt FROM sys_users_logged as ul LEFT JOIN sys_pages_favorites as pf on pf.userId=ul.userId and pf.pageId = '".$this->pageId."' ";
			} else {
				$q .= ", ul.invalidatePerm FROM sys_users_logged as ul ";	
			}
			if($userVO->userId > 0) {
				$q .= "where ul.userId = '".$userVO->userId."'";
			} else {
				$q .= "where ul.loginId = '".$userVO->idlogin."'";
			}
			$vid = FDBTool::getRow($q);
			
			$idloginInDb = null;
			if(!empty($vid)) {
				if($userVO->userId > 0) {
					$idloginInDb = $vid[0];
				} else {
					if($vid[0] > 0) {
					   $userVO->userId = $vid[0];
					   $idloginInDb = $userVO->idlogin;
						 $userVO->load();
						 $userVO->idlogin = $idloginInDb; 
					}
				}
				if($vid[1] == 1) {
					FRules::invalidate();
					FactoryVO::invalidate();
				}
				if(isset($vid[2])) {
					$this->pageVO->favorite = $vid[2]*1;
					$this->pageVO->favoriteCnt = $vid[3]*1;
				}
			}

			//---ip address checking - disabled 21/9/2010 ($userVO->ipcheck === false || $userVO->ip == FSystem::getUserIp())
			if($userVO->idlogin == $idloginInDb) {
				//---user allright
				$ret = true;
			} else {
				//---user was logged but is lost - do logout acction
				if( $userVO->userId>0 ) {
					FUser::logout( $userVO->userId );
					FError::add(FLang::$ERROR_USER_KICKED);
				}
				if( $this->pageVO ) {
					//---do redirect
					FHTTP::redirect(FSystem::getUri());
				}
			}
		}
		return $ret;
	}
	
	function init() {
		if( $this->userVO===null ) {
			//---try to load user from cache
			$cache = FCache::getInstance('s');
			if(false === ($this->userVO = &$cache->getPointer('user'))) {
				$this->userVO = new UserVO();
			}
		}
	}

	/**
	 * BASE FUNCTION to verify page access and user validation
	 * @param $xajax - only reason is when called from ajax function not to count into page statistics
	 * @return void
	 */
	function kde() {
		$userId = $this->userVO->userId;
		$pageAccess = true;
		$pageId = $this->pageId;
		if($pageId) {
			//---try load current page
			$this->pageVO = FactoryVO::get('PageVO',$pageId,true);
			if( $this->pageVO->loaded !== true ) {
				$pageAccess = false;
				$pageId = $this->pageId = null;
				$this->pageVO = null;
				FError::add(FLang::$ERROR_PAGE_NOTEXISTS);
			}
		}
		
		//---page not accessible because not correct host
		if(SITE_STRICT && $userId==0) {
			if($this->pageVO && $this->pageVO->typeId!='top' && $this->pageVO->pageIdTop != SITE_STRICT) 
				$pageAccess = false;
		}
		$this->pageAccess = $pageAccess;
		
		if($pageAccess === true) { //---if page exists continue to test permissions
			//---check if user sent data to login
			if(isset($_POST['lgn']) && $this->idkontrol===false) FUser::login($_POST['fna'],$_POST['fpa'],$this->pageId);
			//---check if user is logged
			if($userId > 0 || !empty($this->userVO->idlogin)) 
				$this->idkontrol = $this->check( $this->userVO ); 
			else 
				$this->idkontrol=false;
				
			
			
			//---check permissions needed for current page
			$permissionNeeded = 1;
			if( $this->pageParam ) {
				if(isset($this->pageParamNeededPermission[$this->pageParam])) {
					$permissionNeeded = $this->pageParamNeededPermission[$this->pageParam];
				}
			}
			
			$permPage = $pageId;
			if($permissionNeeded === 3) { //exception - superadmin group
				$permPage = 'sadmi';
				$permissionNeeded = 1;
			}
			if($permissionNeeded === 4) {
				///check for i owner - permneeded=1 or permneeded= 2
				$permissionNeeded = 2;
				if( $this->itemVO ) {
					if($userId > 0 && $userId === $this->itemVO->userId) {
						$permissionNeeded = 1;
					}
				}
			}
			
			//check if user have access to page with current permissions needed - else redirect to error
			if(!FRules::get($userId,$permPage,$permissionNeeded)) {
				$pageAccess = false;
				FError::add(FLang::$ERROR_ACCESS_DENIED);
			} else {
				$pageAccess = true;
				//user has access to page, with current pageparam
				//validate access to item
				if($this->itemVO) {
					$itemAccess = false;
					if($this->itemVO->public==1) $itemAccess = true;
					else if($this->itemVO->public==2 && $userId>0) $itemAccess = true;
					else if($this->itemVO->public==0 && $userId==$this->itemVO->userId) $itemAccess = true;
					else if($this->itemVO->public==3 && $this->userVO->isFriend($this->itemVO->userId)) $itemAccess = true;
					if($itemAccess===false) {
						$pageAccess = false;
						FError::add(FLang::$ERROR_ACCESS_DENIED);
					}
				}
			}
			
			$this->pageAccess = $pageAccess;
			//logged user function
			if($this->pageAccess && $this->idkontrol === true) {
				//---update user information
				if($this->userVO->strictLogin === true) {
					$this->userVO->idlogin = FUser::getToken($this->userVO->password);
				}
				FDBTool::query("update sys_users_logged set invalidatePerm=0,dateUpdated=NOW(),location='".(($pageId)?($pageId):(''))."',params = '".$this->pageParam."' where loginId='".$this->userVO->idlogin."'");
				$fajax = FAjax::getInstance();
				FDBTool::query("update low_priority sys_users set dateLastVisit = now(),".(empty($fajax->data['__ajaxResponse']) ? 'hit=hit+1' : '')." where userId='".$userId."'");
			}
		}
	}
	
	function updateTotalItemsNum($updateMy=false) {
	   //check total items number
		//---update total items public number
		$fpages = new FPages('',$this->userVO->userId);
		$fpages->VO = null;
		$fpages->setSelect("sum(sys_pages.cnt) as sum");
		$res = $fpages->getContent();
		$totalNum = $res[0]['sum'];
		if($this->userVO->prop('itemsNum') != $totalNum) 
			$this->userVO->prop('itemsNum',$totalNum);
		if($updateMy) $this->userVO->itemsLastNum = $totalNum;
	}

	function setWhoIs($userId) {
		if(FUser::isUserIdRegistered($userId)) $this->whoIs = $userId; else $this->whoIs=0;
	}

	static function usersList( $arr, $ident='', $label='' ) {
		if(!empty($arr)) {
			$tpl = FSystem::tpl('users.list.tpl.html');
			if($label!='') $tpl->setVariable('LABEL',$label);
			foreach($arr as $userVO) {
				$tpl->setVariable('BOXID',($ident?$ident:'userlist').$userVO->userId);
				$tpl->setVariable('AVATAR',FAvatar::showAvatar($userVO->userId));
				$tpl->setVariable('NAME',$userVO->name);
				$tpl->setVariable('PROFILURL',FSystem::getUri('who='.$userVO->userId.'#tabs-profil','finfo',''));
				if(!empty($userVO->dateLastVisit)) $tpl->setVariable('ACTIVITY',$userVO->dateLastVisit);
				if(!empty($userVO->activityPageId)) {
					$pageVO = FactoryVO::get('PageVO',$userVO->activityPageId,true);
					if($pageVO->typeId!='top') {
					$tpl->setVariable('ACTIVITYURL',FSystem::getUri('',$userVO->activityPageId,''));
					$tpl->setVariable('ACTIVITYPAGENAME',$pageVO->name);
					$tpl->setVariable('ACTIVITYPAGENAMESHORT',FLang::$TYPEID[$pageVO->typeId]);
					}
				}
				if(!empty($userVO->requestId)) {
					$tpl->setVariable('REQUESTID',$userVO->requestId);
					$tpl->setVariable('REQUESTMESSAGE',$userVO->requestMessage);
					$tpl->setVariable('REQUESTACTION',FSystem::getUri('m=user-requestaccept','',''));
				}
				$tpl->parse('friendsrow');
			}
			$tpl->parse();
			return $tpl->get();
		}
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
	static function register( $data ) {
		//check captcha
		$captchaCheck = FSystem::recaptchaCheck($data);
		if($captchaCheck!==true) {
			$data['recaptchaError'] = $captchaCheck;
			FError::add(FLang::$ERROR_CAPTCHA);
		}
		$reservedUsernames = array('default','admin','administrator','test','aaa','fuvatar','config','profile','page','event','forum','blog','galery');
		$data["jmenoreg"] = FText::preProcess($data["jmenoreg"],array("plaintext"=>'1'));
		$data["pwdreg1"] = FText::preProcess($data["pwdreg1"],array("plaintext"=>'1'));
		$data["pwdreg2"] = FText::preProcess($data["pwdreg2"],array("plaintext"=>'1'));
		$data["email"] = FText::preProcess($data["email"],array("plaintext"=>'1'));
		$cache = FCache::getInstance('s');
		$cache->setData($data,'reg','form');
		$safeJmenoreg = FText::safeText($data["jmenoreg"]);
		if(strlen($data["jmenoreg"])<2) FError::add(FLang::$ERROR_REGISTER_TOSHORTNAME);
		elseif(strlen($data["jmenoreg"])>10) FError::add(FLang::$ERROR_REGISTER_TOLONGNAME);
		elseif(!FUser::checkUsername($data["jmenoreg"])) FError::add(FLang::$ERROR_REGISTER_NOTALLOWEDNAME);
		elseif(FUser::isUsernameRegistered($data["jmenoreg"]) || in_array(strtolower($data["jmenoreg"]),$reservedUsernames)) FError::add(FLang::$ERROR_REGISTER_NAMEEXISTS);
		elseif($data["jmenoreg"]!=$safeJmenoreg) FError::add(FLang::$ERROR_REGISTER_BADUSERNAME.$safeJmenoreg);
		if(strlen($data["pwdreg1"])<2) FError::add(FLang::$ERROR_REGISTER_PASSWORDTOSHORT);
		elseif($data["jmenoreg"]==$data["pwdreg1"]) FError::add(FLang::$ERROR_REGISTER_PASSWORDNOTSAFE);
		elseif($data["pwdreg1"]!=$data["pwdreg2"]) FError::add(FLang::$ERROR_REGISTER_PASSWORDDONTMATCH);
	    if(FError::is()) return;
		//validate email
		$data['email'] = trim($data['email']);
		require_once('Zend/Validate/EmailAddress.php');
		$validator = new Zend_Validate_EmailAddress();
		if(true!==$validator->isValid($data['email']))  FError::add(FLang::$ERROR_INVALID_EMAIL);
		if(FError::is()) return;
		//check if email is already registered
		$db = FDBConn::getInstance();
		if(FDBTool::getOne("select count(1) from sys_users where email='".$db->escape($data['email'])."'")) FError::add(FLang::$ERROR_USED_EMAIL);
		if(FError::is()) return;
		$userVO = new UserVO();
		$userVO->name = $data["jmenoreg"];
		$userVO->email = $data['email'];
		$userVO->passwordNew = md5($data["pwdreg1"]);
		$userVO->save();
		FUser::login($data['jmenoreg'],md5($data["pwdreg1"]),false);
		//---oznameni o registraci
		$cache->invalidateData('reg','form');
		FMessages::sendSAMessage(array('NEWUSERID'=>$userVO->userId,'NEWUSERNAME'=>$data["jmenoreg"]),FLang::$MESSAGE_USER_NEWREGISTERED);
		FError::add(FLang::$REGISTER_WELCOME,1);
		FHTTP::redirect(FSystem::getUri('',POSTREGISTRATION_PAGE));
	}

	static function checkUsername($name) {
		return preg_match("/(^[a-zA-Z0-9]+([a-zA-Z0-9]*))$/" , $name);
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
	static function isOnline( $userId ){
		$q = "select userId from sys_users_logged where subdate(NOW(),interval ".USERVIEWONLINE." second)<dateUpdated";
		$arr = FDBTool::getCol($q, 'isOn', 'user', 'l');
		return in_array($userId,$arr);
	}

	/**
	 * check if userId is registered
	 * @param $userId
	 * @return [0,1]
	 */
	static function isUserIdRegistered($userId) {
		$q = "select count(1) from sys_users where userId='".$userId."'";
		return ((FDBTool::getOne($q, $userId, 'isId', 'l')>0)?(true):(false));
	}

	/**
	 * check if username is registered
	 * @param $name
	 * @return [0,1]
	 */
	static function isUsernameRegistered($name){
		$q = "select count(1) from sys_users where (deleted=0 or deleted is null) and name like '".$name."'";
		return ((FDBTool::getOne($q, $name, 'isReg', 'l')>0)?(true):(false));;
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

}