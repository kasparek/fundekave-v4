<?php
/**
 * database driver for FCache
 * 
 * PHP versions 4 and 5
 * 
 * @author frantisek.kaspar
 *
 */
class DBDriver
{
	var $tableName = 'sys_cache';
	var $tableDef = "CREATE TABLE {TABLENAME} (
     groupId VARCHAR(20) not null default 'default', nameId VARCHAR(20) not null
    , value blob not null
    , dateCreated DATETIME not null, dateUpdated DATETIME not null, lifeTime MEDIUMINT unsigned not null
    , PRIMARY KEY (groupId,nameId) );";

	var $lifeTimeDefault = 0;
	var $lifetime = 0;

	function __construct() {
		$db = FDBConn::getInstance();
		$res = FDBTool::getAll("show tables like '".$this->tableName."'");
		if(empty($res)) {
			$q = str_replace('{TABLENAME}',$this->tableName,$this->tableDef);
			FDBTool::query($q);
		}
		$this->flushOld();
	}
	
	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new DBDriver();
		}
		return self::$instance;
	}

	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	function flushOld() {
		FDBTool::query("delete from ".$this->tableName." where lifeTime>0 and TIME_TO_SEC(timediff(now(),dateUpdated)) > lifeTime");
	}

	function setData($key, $data, $grp) {
		$dataSerialized = serialize($data);
		return FDBTool::query("insert into ".$this->tableName." (groupId,nameId,value,dateCreated,dateUpdated,lifeTime)
			values ('".$grp."','".$key."','".$dataSerialized."',now(),now(),'".$this->lifeTime."') 
			on duplicate key update dateUpdated=now(), lifeTime='".$this->lifeTime."', value = '".$dataSerialized."'");
	}

	function getGroup($grp) {
		$q = "select value from ".$this->tableName." where groupId='".$grp."' and (datediff(now(),dateUpdated) > lifeTime or lifeTime=0)";
		$arr = FDBTool::getCol($q);
		if(!empty($arr)) {
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row);
			}
			return $arrUnserialized;
		} else {
			return false;
		}
	}
	
	function getPointer( $key, $grp) {
		return false;
	}
	
	function getData( $key, $grp ) {
		$q = "select value from ".$this->tableName." where nameId='".$key."' and groupId='".$grp."' and (datediff(now(),dateUpdated) > lifeTime or lifeTime=0)";
		if($value = FDBTool::getOne($q)) {
			if(!empty($value)) {
				return unserialize($value);
			}
		}
		return false;
	}

	function invalidateData($key, $grp) {
		FDBTool::query("delete from ".$this->tableName." where groupId = '".$grp."' and nameId='".$key."'");
	}

	function invalidateGroup( $grp ) {
		FDBTool::query("delete from ".$this->tableName." where groupId = '".$grp."'");
	}

	function invalidate( ) {
		FDBTool::query("delete from ".$this->tableName);
	}
}