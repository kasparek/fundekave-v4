<?php
class FRules {
	//public - 0-soukr,1-verejny,2-verejny pro registrovany,3-admin pages
	var $_table='sys_users_perm';
	var $_arrCols = array('userId','pageId','rules');
	var $_rules = array(0=>'banan',1=>'user',2=>'adm');
	var $ruleNames;
	var $_err = '';
	var $_pubTypes;
	var $ruleList = array('0'=>array(),'1'=>array(),'2'=>array());
	var $ruleText = array('0'=>'','1'=>'','2'=>'');
	var $public = 1;
	var $page = '';
	var $owner = 0;
	
	static $INITIALIZED = false;
	static $PRELOADED = false;
	static $PERMS = array();
	static $CACHE;

	function __construct($page=0,$owner=0) {
		$this->page = $page;
		$this->_pubTypes = FLang::$ARRPUBLIC;
		$this->ruleNames = FLang::$ARRPERMISSIONS;
		$this->owner = $owner;
		
		self::staticInit();
	}
	
	static function staticInit() {
		if(self::$INITIALIZED) return;
		$cache = FCache::getInstance('s');
		self::$CACHE = &$cache->getPointer('perm', 'FRules');
	}
	
	function setPageId($page) {
		$this->page = $page;
	}
	function set($usr,$page,$type){
		$db = FDBConn::getInstance();
		if($db->getOne("select count(1) from sys_users where userId='".$usr."'")==0) $this->_err='ins_noexistusr';
		if($db->getOne("select count(1) from sys_pages where pageId='".$page."'")==0) $this->_err='ins_noexistpage';
		if(!in_array($type,array_keys($this->_rules))) $this->_err='ins_badruletype';
		if(empty($this->_err)) {
			$db->query("delete from ".$this->_table." where ".$this->_arrCols[0]."='".$usr."' and ".$this->_arrCols[1]."='".$page."'");
			$db->query("insert into ".$this->_table." (".$this->_arrCols[0].",".$this->_arrCols[1].",".$this->_arrCols[2].")	values ('".$usr."','".$page."','".$type."')" ); 
		}
	}
	
	function updateAdminByPages() {
		$db = FDBConn::getInstance();
		$q="insert into sys_users_perm (userId,pageId,rules) (select userIdOwner, pageId, '2' from sys_pages) on duplicate key update rules='2';";
		$db->query($q);
	}
	
	function clear($page=0){
		if(empty($page)) $page=$this->page;
		FDBTool::query("delete from ".$this->_table." where ".$this->_arrCols[1]."='".$page."'");
	}

	static function invalidate() {
		while(self::$CACHE) array_pop(self::$CACHE);
	}

	//---END---functions from user class
	static function getCurrent($type=1) {
		if($type==0)return false;
		$user = FUser::getInstance();
		return self::get($user->userVO->userId,$user->pageVO->pageId,$type);
	}
	
	static function preload($userId) {
		if(self::$PRELOADED) return;
		self::staticInit();
		$arr = FDBTool::getAll("select s.pageId,r.userId,r.rules,s.public,s.userIdOwner from sys_pages as s left join sys_users_perm as r on r.pageId=s.pageId and r.userId='".$userId."' where s.locked<3");
		foreach($arr as $row) {
			$pageId = array_shift($row);
			self::$PERMS[$pageId] = $row;
		}
	}

	static function get($usr,$page,$type=1) {
		if($type==0) return false;
		$key = $usr.'-'.$page.'-'.$type;
		self::staticInit();
		
		if(isset(self::$CACHE[$key])) return self::$CACHE[$key];
		//---if is rules = 0 is ban
		
		if(!empty(self::$PERMS)) {
			if(!empty(self::$PERMS[$page])) $arr = self::$PERMS[$page];
		} else {
			$q = "select r.userId,r.rules,s.public,s.userIdOwner from sys_pages as s left join sys_users_perm as r on r.pageId=s.pageId and r.userId='".$usr."' where s.locked<3 and s.pageId='".$page."'";
			$arr = FDBTool::getRow($q);
		}
		$ret = false;
		if(empty($arr)) $ret = false;			
		elseif ($arr[3] == $usr) $ret = true;
		elseif ($arr[0]>0 && $arr[1]==0) $ret=false;//banned from page at any time
		elseif ($arr[0]>0 && $arr[1]>=$type) $ret=true; //if rulez for user are set and as type or higher
		elseif ($arr[2] < 3 && $type<2) { // not an admin page, just reading
			//not an owner
			if($arr[2] == 1) $ret = true; //public page
			if($arr[2] == 2 && $usr > 0) $ret = true; //for registrated page
		}
		self::$CACHE[$key] = $ret;
    	return $ret;
	}
	
	function getList($listPublic=true,$idstr=0) {
		if(!empty($idstr)) $this->page = $idstr;
		if(!empty($this->page)) {
			foreach ($this->_rules as $k=>$v) {
				$this->ruleList[$k]=array();
				$arr = FDBTool::getAll("select p.userId,u.name from sys_users_perm as p left join sys_users as u on u.userId=p.userId where rules='".$k."' and pageId='".$this->page."' order by u.name");
				foreach ($arr as $usr) {
					$this->ruleList[$k][$usr[0]] = $usr[1];
				}
				if(!empty($this->ruleList[$k])) {
					$this->ruleText[$k] = implode(",",$this->ruleList[$k]); 	
				} else {
					$this->ruleText[$k]='';	
				}
			}
			if($listPublic===true) {
				$arr = FDBTool::getRow("select public,userIdOwner from sys_pages where pageId='".$this->page."'");
				if($arr) {
					$this->public = $arr[0];
					$this->owner = $arr[1];
				}
				if($this->public=='') $this->public=1;
			}
		}
	}
	
	function inputName($k) {
		return 'rule-'.$k;
	}
	
	function printEditForm($idstr=0) {
		$this->getList(true,$idstr);
		$tpl = FSystem::tpl('pages.permissions.tpl.html');
		$tpl->setVariable('HEADERLABEL',FLang::$TEXT_PERMISSIONS_SET);
		$tpl->setVariable('SELECTLABEL',FLang::$LABEL_RULES_ACCESS);
		$tpl->setVariable('HELPLABEL',FLang::$LABEL_RULES_HELP);
		$tpl->setVariable('HELPTEXT',FLang::$LABEL_RULES_HELP_TEXT);

		$selectOptions = '';
		foreach($this->_pubTypes as $k=>$v) $selectOptions.=FText::options($k,$v,$this->public);
		$tpl->setVariable('SELECTOPTIONS',$selectOptions);
		$tpl->setVariable('SELECTNAME','public');

		foreach ($this->ruleText as $k=>$v) {
			$tpl->setCurrentBlock('rules');
			$tpl->setVariable('RULESNUM',$k);
			$tpl->setVariable('INPUTNAME',$this->inputName($k));
			$tpl->setVariable('RULESCONTENT',$v);
			$tpl->setVariable('RULESNAME',$this->ruleNames[$k]);
			$tpl->parseCurrentBlock();
		}

		return $tpl->get();
	}
	function update($data=array()) {
		if(!empty($data)) {
			if(isset($data['public'])) {
				$this->public = $data['public'];
			}
			foreach ($this->ruleText as $k=>$v) {
				$inputName = $this->inputName($k);
				if(isset($data[$inputName])) {
					$this->ruleText[$k] = $data[$inputName]; 
				}
			}
		}
		
		//---set rules
		$this->clear(); //delete perm for page

		foreach ($this->ruleText as $k=>$v){
			if(!empty($v)) {
				$arr=explode(",",$v);
				foreach ($arr as $usrname){
					$usrname=trim($usrname);
					if(!empty($usrname)) {
						$usrid = FUser::getUserIdByName($usrname);
						if($usrid != $this->owner) { // if not owner of page
							if(!empty($usrid)) $this->set($usrid,$this->page,$k);
							else FError::add(LABEL_USER." ".$usrname." ".LABEL_NOTEXISTS);
						}
					}
				}
			}
		}
		
		
		if(count($this->ruleList['1']) != 0) $this->public = 0;

		FDBTool::query("update `sys_pages` set public='".$this->public."' where pageId='".$this->page."'");
		
		//---invalidate active users
		FDBTool::query("update `sys_users_logged` set invalidatePerm=1");
		
		//---public update
		$this->getList(false);

	}

}