<?php
class fSqlSaveTool {
	var $table = '';
	var $primaryCol = '';
	var $_cols = array();
	var $_notQuoted = array();
	var $debug = 0;
	var $quoteType = "'";
	function __construct($tableName,$primaryCol='') { $this->table = $tableName; $this->primaryCol = $primaryCol; }
	
	function quote($str) {
        $str = str_replace($this->quoteType,'\\'.$this->quoteType, $str);
        $str = str_replace('\\\\','\\', $str);
        return $this->quoteType . trim($str) . $this->quoteType;
    }
    function quoteCols() {
    	foreach ($this->_cols as $k=>$v) {
    		$arr[$k] = ((!in_array($k,$this->_notQuoted))?($this->quote($v)):($v));
    	}
    	return $arr;
    }
	function addCol($name,$value,$quote=true) {
		$this->_cols[$name] = $value;
		if(!$quote) $this->_notQuoted[]=$name;
	}
	function setCols($cols,$notQuoted=array()) {
		$this->_cols = array();
		foreach ($cols as $k=>$v) {
			$this->addCol($k,$v,((in_array($k,$notQuoted))?(false):(true)));
		}
	}
	function buildInsert($cols=array(),$notQuoted=array()) {
		if(!empty($cols)) $this->setCols($cols,$notQuoted);
		$cols = $this->quoteCols();
		$ret = 'insert into '.$this->table
		.' ('.implode(",",array_keys($cols))
		.') values ('
		.implode(',',$cols)
		.')';
		$this->_cols = array();
		return $ret;
	}
	function buildUpdate($cols=array(),$notQuoted=array()) {
		if(!empty($cols)) $this->setCols($cols,$notQuoted);
		$ret = 'update '.$this->table.' set ';
		$cols = $this->quoteCols();
		$first = true;
		foreach ($this->_cols as $k=>$v) {
			if($k!=$this->primaryCol) {
				$ret.=(($first)?(''):(',')).$k.'='.((!in_array($k,$this->_notQuoted))?($this->quote($v)):($v));
				$first = false;
			}
		}
		$ret.=' where '.$this->primaryCol.'='.$this->quoteType.$this->_cols[$this->primaryCol].$this->quoteType;
		$this->_cols = array();
		return $ret;
	}
	function getLastId() {
		global $db;
		return $db->getOne("SELECT LAST_INSERT_ID()");
	}
	function save($cols=array(),$notQuoted=array(),$forceInsert=false) {
    		if(!empty($cols)) $this->setCols($cols,$notQuoted);
    		$insert = false;
    		if(empty($this->_cols[$this->primaryCol]) || $forceInsert) {
    			$dot = $this->buildInsert();
    			$insert = true;
    		} else {
    			$retId = $this->_cols[$this->primaryCol];
    			$dot = $this->buildUpdate();
    		}
    		
    		global $db;
    		if($this->debug==1) echo $dot;
    		if($db->query($dot)) {
    			if(isset($cols[$this->primaryCol])) return $cols[$this->primaryCol];
    			elseif($insert) return $this->getLastId();
    			else return $retId;
    		}
    }
    function delete($id) {
    	global $db;
    	return $db->query("delete from ".$this->table." where ".$this->primaryCol."=".$this->quoteType.$id.$this->quoteType);
    }
}