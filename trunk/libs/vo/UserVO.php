<?php
class UserVO extends FDBvo {

	//---token is changed every check
	//---if true user can NOT work in multiple windows
	//---use true for webservice - more secure
	var $strictLogin = false;

	var $tableDef = 'CREATE TABLE sys_users (
       userId MEDIUMINT unsigned NOT NULL PRIMARY KEY
     , skinId SMALLINT unsigned DEFAULT null
     , name VARCHAR(20) NOT NULL
     , password VARCHAR(32) NOT NULL
     , ipcheck BOOLEAN 
     , dateCreated DATETIME NOT NULL
     , dateUpdated DATETIME default null
     , dateLastVisit DATETIME default null
     , email VARCHAR(100) DEFAULT null
     , icq VARCHAR(20) DEFAULT null
     , info TEXT
     , avatar VARCHAR(100) DEFAULT null
     , zbanner TINYINT  
     , zavatar TINYINT  
     , zforumico TINYINT  
     , zgalerytype TINYINT  
     , deleted TINYINT  
     , hit INT  
)  ;';

	var $userId = 0;
	var $skinId;
	var $name;
	var $password;
	var $passwordNew;
	var $ipcheck = true;
	var $dateCreated;
	var $dateUpdated;
	var $dateLastVisit;
	var $email = '';
	var $icq = '';
	//---additional user information XML structure
	var $info = "<user><personal><www/><motto/><place/><food/><hobby/><about/><HomePageId/></personal><webcam /></user>";
	var $avatar = AVATAR_DEFAULT;
	var $zbanner = 1;
	var $zavatar = 1;
	var $zforumico = 1;
	var $zgalerytype = 0;
	var $deleted;
	var $hit;

	//---security
	var $idlogin = '';
	var $ip = '';

	//---skin info
	var $skin = 0;
	var $skinName = '';
	var $skinDir = '';

	//---user messages
	//---new post alerting
	var $newPost = 0;
	var $newPostFrom = '';


	function PageVO() {
		 
		parent::__construct();

	}

	function loadVO() {
		$this->setSelect( implode(',',$this->_cols) );
		$this->setWhere($this->primaryCol ."='".$recordId."'");
		$this->addJoinAuto('sys_skin','skinId','name');
		$this->load();
	}

	function saveVO(){
		$this->queryReset();
		$sUser->addCol('email',$this->email);
		$sUser->addCol('info',$this->info);
		$sUser->addCol('skinId',$this->skin);
		$sUser->addCol('icq',$this->icq);
		$sUser->addCol('zbanner',$this->zbanner);
		$sUser->addCol('zforumico',$this->zforumico);
		$sUser->addCol('zavatar',$this->zavatar);
		$sUser->addCol('zgalerytype',$this->zgalerytype);
		$sUser->addCol('avatar',$this->avatar);
		if(!empty($this->newPassword)) $sUser->addCol('password',$this->newPassword);
		$sUser->addCol('userId',$this->userId);
		$sUser->addCol('dateUpdated','now()',false);
		$this->save();
		
		$cache = FCache::getInstance( 's' );
		$cache->setData($this->userVO,'user');
	}

	function getXMLVal($branch,$node,$default='') {
		$xml = new SimpleXMLElement($this->info);
		if(isset($xml->$branch)) {
			if(isset($xml->$branch->$node)) {
				return $xml->$branch->$node;
			}
		}
		return $default;
	}

	function setXMLVal($branch,$node,$value) {
		$xml = new SimpleXMLElement($this->info);
		$xml->$branch->$node = $value;
		$this->xmlProperties = $xml->asXML();
	}

	function hasNewMessages(){
		$db = FDBConn::getInstance();

		$dot = "select userIdFrom from sys_users_post where readed=0 AND userIdFrom!='".$this->userId."' AND userId='".$this->userId."' order by dateCreated desc";
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

	function getDiaryCnt() {
		$q = "select count(1) from sys_users_diary where (userId='".$this->userId."' or eventForAll=1) and year(dateEvent)=year(now()) and month(dateEvent)=month(now()) and dayofmonth(dateEvent)=dayofmonth(now())";
		return FDBTool::getOne($q, 'diarS', 'default', 's', 0);
	}

	//----FRIENDS managments
	function isFriend($userId) {
		if($this->userId > 0) {
			$arr = $this->getFriends($this->userId);
			return(in_array($userId,$arr));
		}
	}

	function addFriend($userIdArr) {
		if($this->userId > 0) {
			if(!is_array($userIdArr)) $userIdArr = array($userIdArr);
			foreach ($userIdArr as $userId) {
				FDBTool::query("insert into sys_users_friends (userId,userIdFriend,dateCreated) values ('" . $this->userId . "','" . $userId . "',NOW())");
				$this->getFriends(0,true);
			}
		}
	}

	function removeFriend($userId) {
		if($this->userId > 0) {
			FDBTool::query('delete from sys_users_friends where userId='.$this->userId.' and userIdFriend='.$userId);
			$this->getFriends(true);
		}
	}

	function getFriends($refresh=false) {
		if($this->userId > 0) {
			$cacheGroup = 'friends';
			$cache = FCache::getInstance('s', 0);
			if($refresh==true) {
				$cache->invalidateGroup($cacheGroup);
			}

			$q = "SELECT p.userIdFriend,s.name
			FROM sys_users_friends as p left join sys_users as s on p.userIdFriend = s.userId 
			WHERE p.userId = ".$this->userId." ORDER BY s.name";
			$arrTmp = FDBTool::getAll($q, $this->userId, $cacheGroup, 's', 0);

			$arr = array();
			if(!empty($arrTmp)) {
				foreach ($arrTmp as $row) {
					$arr[$row[0]] = $row[0];
					$cache->setData($row[1], $row[0], 'Uname');
				}
			}

			return $arr;
		}
	}
}