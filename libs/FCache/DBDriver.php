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
			self::$instance = &new DBDriver();
		}
		return self::$instance;
	}

	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	function flushOld() {
		FDBTool::query("delete from ".$this->tableName." where datediff(now()-dataUpdated) > lifeTime");
	}

	function setData($id, $data, $group = 'default') {
		return FDBTool::query('insert into '.$this->tableName.' (groupId,nameId,value,dateCreated,dateUpdated,lifeTime)
			values ("'.$group.'","'.$id.'","'.serialize($data).'",now(),now(),"'.$this->lifeTime.'") 
			on duplicate key update dateUpdated=now(), lifeTime="'.$this->lifeTime.'", value = "'.$data.'"');
	}

	function getGroup($group = 'default') {
		$arr = FDBTool::getCol("select data from ".$this->tableName." where groupId='".$group."' and datediff(now()-dataUpdated) > lifeTime");
		if(!empty($arr)) {
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row);
			}
			return $arrUnserialized;
		} else {
			return false;
		}
	}

	function getData($id, $group = 'default') {
		if($value = FDBTool::getOne("select value from ".$this->tableName." where nameId='".$id."' and groupId='".$group."' and datediff(now()-dataUpdated) > lifeTime")) {
			if(!empty($value)) {
				return unserialize($value);
			} else {
				return false;
			}
		}
	}

	function invalidateData($id='',$group='default') {
		if(!empty($id)) {
			FDBTool::query("delete from ".$this->tableName." where groupId = '".$group."' and nameId='".$id."'");
		}
	}

	function invalidateGroup( $group='default' ) {
		FDBTool::query("delete from ".$this->tableName." where groupId = '".$group."'");
	}

	function invalidate( ) {
		FDBTool::query("delete from ".$this->tableName);
	}
}