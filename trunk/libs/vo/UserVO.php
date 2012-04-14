<?php
class UserVO extends Fvob {
	//---token is changed every check
	//---if true user can NOT work in multiple windows
	//---use true for webservice - more secure
	public $strictLogin = false;

  public function getTable(){ return 'sys_users'; }
	public function getPrimaryCol() { return 'userId'; }
	public function getColumns() { return array('userId'=>'userId',
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
	'deleted'=>'deleted',
	'hit'=>'hit');
	}

	public $userId = 0;
	public $name;
	public $password;
	public $passwordNew;
	public $ipcheck = true;
	public $dateCreated;
	public $dateUpdated;
	public $dateLastVisit;
	public $email = '';
	public $icq = '';
	//---additional user information XML structure
	public $info = "<user><personal><www/><motto/><place/><food/><hobby/><about/><HomePageId/></personal><webcam /></user>";
	public $avatar;
	public $deleted = 0;
	public $hit;

	//---security
	public $idlogin = '';
	public $ip = '';

	//---user messages
	//---new post alerting
	public $newPost = 0;
	public $newPostFrom = '';
	
	public $requestId;
	public $requestUserId;
	public $requestMessage;
	
	public $activityPageId;
	
	//client
	public $clientWidth=0;
	public $clientHeight=0;
	
	//total items previous login num
	public $itemsLastNum=0;
		
	function save(){
		$this->saveIgnore = array('dateLastVisit');
		if(!empty($this->passwordNew)) {
			$this->password = $this->passwordNew;
		} else {
			$this->saveIgnore[] = 'password';
		}
		parent::save();
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

	function hasNewMessages() {
		if($this->userId==0) return false;
		$dot = "select userIdFrom from sys_users_post where readed=0 AND userIdFrom!='".$this->userId."' AND userId='".$this->userId."' order by dateCreated desc";
		$npost = FDBTool::getCol($dot);
		if(!empty($npost)) {
			$this->newPost = count($npost);
			$this->newPostFrom = FUser::getgidname($npost[0]);
			return true;
		} else {
			$this->newPost = 0;
			$this->newPostFrom = '';
			return false;
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
		$vo->setWhere("sys_users.userId in (".implode(',',$arr).")");
		$vo->setOrder('sys_users.name');
		return $vo->getContent();
	}
	
	function loadOnlineFriends() {
		$vo = new FDBvo( $this );
		$vo->VO = 'UserVO';
		$vo->addJoin('join sys_users_friends as f on f.userIdFriend=sys_users.userId');
		$vo->addSelect('l.dateUpdated as activity,l.location as activityPageId');
		$vo->addJoin('join sys_users_logged as l on l.userId = sys_users.userId');
		$vo->addWhere("f.userId = ".$this->userId);
		$vo->addWhere("l.userId != ".$this->userId);
		$vo->addWhere("subdate(NOW(),interval ".USERVIEWONLINE." second) < l.dateUpdated"); 
		$vo->setOrder('sys_users.dateLastVisit');
		$vo->autojoinSet(true);
		return $vo->getContent();
	}
	
	function loadRequests() {
		$vo = new FDBvo( $this );
		$vo->autojoinSet(true);
		$vo->addSelect('i.itemId as requestId,i.userId as requestUserId,i.text as requestMessage');
		$vo->addJoin('join sys_pages_items as i on i.userId=sys_users.userId');
		$vo->VO = 'UserVO';
		$vo->setWhere("i.typeId = 'request' and i.addon = '".$this->userId."'");
		$vo->setOrder('sys_users.name');
		return $vo->getContent();
	}
}