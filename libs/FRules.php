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

	function __construct($page=0,$owner=0) {
		$this->page = $page;
		$this->_pubTypes = FLang::$ARRPUBLIC;
		$this->ruleNames = FLang::$ARRPERMISSIONS;
		$this->owner = $owner;
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
			$db->query('update '.$this->_table.' set invalidatePerm=1 where userId="'.$usr.'"');
			$db->query("delete from ".$this->_table." where ".$this->_arrCols[0]."='".$usr."' and ".$this->_arrCols[1]."='".$page."'");
				
			if($db->query("insert into ".$this->_table." (".$this->_arrCols[0].",".$this->_arrCols[1].",".$this->_arrCols[2].")
				values ('".$usr."','".$page."','".$type."')" )) 
			return true;
			else return false;
				
		}

	}
	function clear($page=0){
		if(empty($page)) $page=$this->page;
		FDBTool::query("delete from ".$this->_table." where ".$this->_arrCols[1]."='".$page."'");
	}


	static function invalidate() {
	 $cache = FCache::getInstance('s');
	 $cache->invalidateGroup('fRules');
	}

	//---END---functions from user class
	static function getCurrent($type=1) {
		$user = FUser::getInstance();
		return FRules::get($user->userVO->userId,$user->pageVO->pageId,$type);
	}

	static function get($usr,$page,$type=1) {
		$ret = false;
		$key = $usr.'-'.$page.'-'.$type;
		$cache = FCache::getInstance('s');
		if(false === ($retVal = &$cache->getPointer($key, 'fRules'))) {
			//---if is rules = 0 is ban
			$dot = "select r.userId,r.rules,s.public,s.userIdOwner
			from sys_pages as s 
			left join sys_users_perm as r 
			on r.pageId=s.pageId and r.userId='".$usr."'
			where s.pageId='".$page."'";
			$arr = FDBTool::getRow($dot);
			if($arr[3] == $usr) $ret = true;
			elseif ($arr[0]>0 && $arr[1]==0) $ret=false;//banned from page at any time
			elseif ($arr[0]>0 && $arr[1]>=$type) $ret=true; //if rulez for user are set and as type or higher
			elseif ($arr[2] < 3 && $type<2) { // not an admin page, just reading
				//not an owner
				if($arr[2] == 1) $ret = true; //public page
				if($arr[2] == 2 && $usr > 0) $ret = true; //for registrated page
			}
			$retVal = ($ret===true)?(1):(2);
		}
    	return ($retVal===1)?(true):(false);
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
			if($listPublic) {
				$arr = FDBTool::getRow("select public,userIdOwner from sys_pages where pageId='".$this->page."'");
				$this->public = $arr[0];
				$this->owner = $arr[1];
				if($this->public=='') $this->public=1;
			}
		}
	}
	
	function inputName($k) {
		return 'rule-'.$k;
	}
	
	function printEditForm($idstr=0) {
		$this->getList(true,$idstr);
		$tpl = new FTemplateIT('pages.permissions.tpl.html');
		$tpl->setVariable('HEADERLABEL',FLang::$TEXT_PERMISSIONS_SET);
		$tpl->setVariable('SELECTLABEL',FLang::$LABEL_RULES_ACCESS);
		$tpl->setVariable('HELPLABEL',FLang::$LABEL_RULES_HELP);
		$tpl->setVariable('HELPTEXT',FLang::$LABEL_RULES_HELP_TEXT);

		$selectOptions = '';
		foreach($this->_pubTypes as $k=>$v) $selectOptions.='<option value="'.$k.'"'.(($k==$this->public)?(' selected="selected"'):('')).'>'.$v.'</option>';
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
				$rules->public = $data['public'];
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
							else FError::addError(LABEL_USER." ".$usrname." ".LABEL_NOTEXISTS);
						}
					}
				}
			}
		}
		//---public update
		$this->getList(false);
		if(count($this->ruleList['1']) != 0) $this->public=0;

		$dot = "update sys_pages set public='".$this->public."' where pageId='".$this->page."'";
		FDBTool::query($dot);

		//---invalidate active users
		FDBTool::query("update `sys_users_logged` set invalidatePerm=1");

	}

}