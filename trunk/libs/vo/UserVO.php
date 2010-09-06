<?php
class UserVO extends Fvob {
	//---token is changed every check
	//---if true user can NOT work in multiple windows
	//---use true for webservice - more secure
	var $strictLogin = false;

	var $table = 'sys_users';
	var $primaryCol = 'userId';

	var $columns = array('userId'=>'userId',
	'skinId'=>'skinId',
	'name'=>'name',
	'password'=>'password',
	'ipcheck'=>'ipcheck',
	'dateCreated'=>'dateCreated',
	'dateUpdated'=>'dateUpdated',
	'dateLastVisit'=>'dateLastVisit',
	'email'=>'email',
	'icq'=>'icq',
	'info'=>'info',
	'avatar'=>'avatar',
	'zbanner'=>'zbanner',
	'zavatar'=>'zavatar',
	'zforumico'=>'zforumico',
	'zgalerytype'=>'zgalerytype',
	'deleted'=>'deleted',
	'hit'=>'hit');

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
	var $deleted = 0;
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
	
	var $requestId;
	var $requestUserId;
	var $requestMessage;
	
	var $activityPageId;
	
	//client
	var $clientWidth=0;
	var $clientHeight=0;

	function UserVO($userId=0, $autoLoad = false) {
		$this->userId = $userId;
		if($autoLoad == true) {
			$this->load();
		}
	}

	function load() {
		$vo = new FDBvo( $this );
		$vo->addJoinAuto('sys_skin','skinId',array('name as skinName'));
		$vo->load();
	}

	function save(){
		$vo = new FDBvo( $this );
		$vo->addIgnore('dateLastVisit');
		if($this->userId>0) {
			$vo->addIgnore('dateCreated');
			$vo->notQuote('dateUpdated');
			$this->dateUpdated = 'now()';
			$vo->addIgnore('name');
		} else {
			$vo->addIgnore('dateUpdated');
			$vo->notQuote('dateCreated');
			$this->dateCreated = 'now()';
		}
		if(!empty($this->passwordNew)) {
			$this->password = $this->passwordNew;
		} else {
			$vo->addIgnore('password');
		}
		$this->userId = $vo->save();
		$vo->vo = false;
		$vo = false;
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
		$this->info = $xml->asXML();
	}

	function hasNewMessages(){
		$dot = "select userIdFrom from sys_users_post where readed=0 AND userIdFrom!='".$this->userId."' AND userId='".$this->userId."' order by dateCreated desc";
		$npost = FDBTool::getCol($dot);
		if(count($npost)>0) {
			$this->newPost = count($npost);
			$this->newPostFrom = FUser::getgidname($npost[0]);
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
	
	function isRequest($userId) {
		$cnt = FDBTool::getOne("select count(1) from sys_pages_items where typeId='request' and userId='".$userId."' and addon='".$this->userId."'");
		return ($cnt>0) ? true : false;
	}

	function addFriend($userIdArr) {
		if($this->userId > 0) {
			if(!is_array($userIdArr)) $userIdArr = array($userIdArr);
			foreach ($userIdArr as $userId) {
				if($userId != $this->userId) {
					FDBTool::query("insert into sys_users_friends (userId,userIdFriend,dateCreated) values ('" . $this->userId . "','" . $userId . "',NOW())");
				}
			}
			$this->getFriends(0,true);
		}
	}

	function removeFriend($userId) {
		if($this->userId > 0) {
			FDBTool::query("delete from sys_users_friends where userId='".$this->userId."' and userIdFriend='".$userId."'");
			FDBTool::query("delete from sys_users_friends where userId='".$userId."' and userIdFriend='".$this->userId."'");
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

			$q = "SELECT if(f.userIdFriend=".$this->userId.",f.userId,f.userIdFriend),
			if(f.userIdFriend=".$this->userId.",s2.name,s1.name) as fname
			FROM sys_users_friends as f 
			join sys_users as s1 on f.userIdFriend = s1.userId 
			join sys_users as s2 on f.userId = s2.userId 
			WHERE f.userId = ".$this->userId." or f.userIdFriend = ".$this->userId." ORDER BY fname";
			
			
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
	
	function loadFriends() {
		
		$arr = $this->getFriends();
		
		$vo = new FDBvo( $this );
		$vo->VO = 'UserVO';
		$vo->addJoinAuto('sys_skin','skinId',array('name as skinName'));
						
		$vo->setWhere("sys_users.userId in (".implode(',',$arr).")");
		$vo->setOrder('sys_users.name');
		return $vo->get();
		
	}
	
	function loadOnlineFriends() {
		$vo = new FDBvo( $this );
		$vo->VO = 'UserVO';
		$vo->addJoinAuto('sys_skin','skinId',array('name as skinName'));
		
		$vo->addJoin('join sys_users_friends as f on f.userIdFriend=sys_users.userId');
		
		$vo->addSelect('l.dateUpdated as activity,l.location as activityPageId');
		$vo->addJoin('join sys_users_logged as l on l.userId = sys_users.userId');
				
		$vo->addWhere("f.userId = ".$this->userId);
		$vo->addWhere("l.userId != ".$this->userId);
		$vo->addWhere("subdate(NOW(),interval ".USERVIEWONLINE." minute) < l.dateUpdated"); 

		$vo->setOrder('sys_users.dateLastVisit');
		return $vo->get();
	}
	
	function loadRequests() {
		
		$vo = new FDBvo( $this );
		$vo->addJoinAuto('sys_skin','skinId',array('name as skinName'));
		
		$vo->addSelect('i.itemId as requestId,i.userId as requestUserId,i.text as requestMessage');
		$vo->addJoin('join sys_pages_items as i on i.userId=sys_users.userId');
		
		$vo->VO = 'UserVO';
		
		$vo->setWhere("i.typeId = 'request'");
		$vo->setWhere("i.addon = '".$this->userId."'");
		$vo->setOrder('sys_users.name');
		
		return $vo->get();
		
	}
}