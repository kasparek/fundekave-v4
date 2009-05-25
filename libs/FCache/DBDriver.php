<?php
/**
 *TODO: calculate difference between dataUpdate and now - compare with lifetime
 *line  23
 **/
class DBDriver
{
	private $tableName = 'sys_cache';
	private $tableDef = "CREATE TABLE {TABLENAME} (
     groupId VARCHAR(20) not null default 'default'
    , nameId VARCHAR(20) not null
    , value blob not null
    , dateCreated DATETIME not null
    , dateUpdated DATETIME not null
    , lifeTime MEDIUMINT unsigned not null
    , PRIMARY KEY (groupId,nameId)
);";

	public $lifeTimeDefault = 0;
	private $lifetime = 0;

	function __construct() {
		$db = FDBConn::getInstance();
		$res = $db->getAll("show tables like '".$this->tableName."'");
		if(empty($res)) {
			$q = str_replace('{TABLENAME}',$this->tableName,$this->tableDef);
			$db->query($q);
		}
		$this->flushOld();
	}

	public function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	private function flushOld() {
		$db = FDBConn::getInstance();
		$db->query("delete from ".$this->tableName." where datediff(now()-dataUpdated) > lifeTime");
	}

	public function setData($id, $data, $group = 'default') {

		$db = FDBConn::getInstance();
		$db->query('insert into '.$this->tableName.' (groupId,nameId,value,dateCreated,dateUpdated,lifeTime)
			values ("'.$group.'","'.$id.'","'.serialize($data).'",now(),now(),"'.$this->lifeTime.'") 
			on duplicate key update dateUpdated=now(), lifeTime="'.$this->lifeTime.'", value = "'.$data.'"');

	}

	public function getGroup($group = 'default') {
		$db = FDBConn::getInstance();
		$arr = $db->getCol("select data from ".$this->tableName." where groupId='".$group."' and datediff(now()-dataUpdated) > lifeTime");
		if(!empty($arr)) {
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row);
			}
			return $arrUnserialized;
		} else {
			return false;
		}
	}

	public function getData($id, $group = 'default') {
		$db = FDBConn::getInstance();
		$user = FUser::getInstance();
		if($value = $db->getOne("select value from ".$this->tableName." where nameId='".$id."' and nameId='".$id."' and datediff(now()-dataUpdated) > lifeTime")) {
			if(!empty($value)) {
				return unserialize($value);
			} else {
				return false;
			}
		}
	}

	public function invalidateData($id='',$group='default') {
		if(!empty($id)) {
			$db = FDBConn::getInstance();
			$db->query("delete from ".$this->tableName." where groupId = '".$group."' and nameId='".$id."'");
		}
	}

	public function invalidateGroup( $group='default' ) {
		$db = FDBConn::getInstance();
		$db->query("delete from ".$this->tableName." where groupId = '".$group."'");
	}

	public function invalidate( ) {
		$db = FDBConn::getInstance();
		$db->query("delete from ".$this->tableName);
	}

}