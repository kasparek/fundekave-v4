<?php
class FDBTool {
    var $cacheResults = false;
	var $queryTemplate = 'select {SELECT} from {TABLE} {JOIN} where {WHERE} {GROUP} {ORDER} {LIMIT}';
	var $table = '';
	var $primaryCol = '';
	private $_where = '';
	private $_order = '';
	private $_select = '*';
	private $selectCount = 'count(1)';
	private $_group = '';
	private $_join = '';
	private $_limit = array();
	var $debug = 0;
	private $openingDelimiter = '{#';
	private $closingDelimiter     = '#}';
	private $variablenameRegExp    = '[\.0-9A-Za-z_-]+';
	private $variablesRegExp;
	private $replaceKeys;
	private $replaceVars = array(
    'date' => '%Y-%m-%d',
    'date_iso' => '%Y-%m-%d',
    'date_local' => '%d.%m.%Y',
    'time' => '%T',
    'time_short' => '%H:%i',
    'datetime_iso' => '%Y-%m-%d %T',
    'datetime_iso' => '%Y-%m-%dT%T',
    'datetime_local' => '%T %d.%m.%Y',
    );
    //---save tool
  	var $_cols = array();
	var $_notQuoted = array();
	var $quoteType = "'";
	function __construct($tableName='',$primaryCol='') {
		$this->table = $tableName;
		$this->variablesRegExp = '@' . $this->openingDelimiter . '(' . $this->variablenameRegExp . ')' . $this->closingDelimiter . '@sm';
		$this->replaceKeys = array_keys($this->replaceVars);
		$this->primaryCol = $primaryCol;
	}
	function queryReset() {
      $this->_where = '';
      $this->_join = '';
      $this->_order = '';
      $this->_group = '';
    }
	function setTemplate($template) {
		$this->queryTemplate = $template;
	}
	function getTemplate() {
	  return $this->queryTemplate;
	}
	function getWhere() {
        return ((!empty($this->_where))?($this->_where):('1'));
    }
	function setWhere($whereCondition) {
		$this->_where = $whereCondition;
	}
	function addWhere($where,$condition='AND') {
        $this->setWhere(((!empty($this->_where))?($this->_where.' '.$condition.' '.$where):($where)));
	}
	function addJoin($condition) {
		$this->_join .= ' '.$condition;
	}
	function getJoin() {
		return $this->_join;
	}
	function addWhereSearch($column, $string, $condition = "AND",$groupCondition="AND") {
	    $string = str_replace('"', '""', str_replace(' ', '%',strtolower(trim($string))));
	    if(is_array($column)) {
	    	foreach ($column as $col) {
	    		$arr[] = ' LOWER('.$col.') LIKE "%'.$string.'%" ';
	    		$str = implode($condition,$arr);
	    	}
	    	$this->addWhere('('.$str.')', $groupCondition);
	    }
		else $this->addWhere('LOWER('.$column.') LIKE "%'.$string.'%"', $condition);
	}
	function addFulltextSearch($columns, $string, $condition = "AND",$queryExpansion=true) {
	    $this->addWhere('MATCH ('.$columns.') AGAINST ("'.$string.'"'.(($queryExpansion==true)?(' WITH QUERY EXPANSION'):('')).')');
	}
	function setOrder($orderCondition='', $desc=false) {
    	$this->_order = $orderCondition .($desc ? ' DESC' : '');
	}
	function addOrder($orderCondition='', $desc=false) {
    	$order = $orderCondition .($desc ? ' DESC' : '');
    	if ($this->_order) $this->_order = $this->_order.','.$order;
    	else $this->_order = $order;
	}
	function getOrder() {
    	return ((!empty($this->_order))?(' order by '.$this->_order):(''));
	}
	function setSelect($what='*') {
    	$this->_select = $what;
	}
	function addSelect($what='*') {
    	$this->_select .= ((!empty($this->_select))?(','):('')).$what;
	}
	function replaceSelect($what,$with) {
    	$this->_select = str_replace($what,$with,$this->_select);
	}
	function getSelect() {
		return $this->_select;
	}
	function setLimit($from=0, $count=0) {
    	if ($from==0 && $count==0) $this->_limit = array();
    	else $this->_limit = array($from, $count);
	}
	function getLimit() {
		return ((!empty($this->_limit))?(' limit '.$this->_limit[0].','.$this->_limit[1]):(''));
	}
	function setGroup($group='') {
    	$this->_group = $group;
	}
	function getGroup() {
    	return ((!empty($this->_group))?(' group by '.$this->_group):(''));
	}
	function buildBase() {
		$query = $this->queryTemplate;
		$query = str_replace('{TABLE}',$this->table,$query);
		$query = str_replace('{JOIN}',$this->getJoin(),$query);
		$query = str_replace('{WHERE}',$this->getWhere(),$query);
		$query = str_replace('{GROUP}',$this->getGroup(),$query);
		return $query;
	}
	function replaceKeys($query) {
        preg_match_all($this->variablesRegExp, $query, $regs);
        $length = count($regs[1]);
        if ($length != 0) {
          $x = 0;
          while($x < $length) {
            if(in_array($regs[1][$x],$this->replaceKeys)) $query = str_replace($regs[0][$x],$this->replaceVars[$regs[1][$x]],$query);
            else die('fQueryTool:key not exists:'.$regs[1][$x]);
            $x++;
          }
        }
        return $query;
    }
	function buildQuery($from=0,$perPage=0) {
	    if($perPage>0) $this->setLimit($from,$perPage);
		$query = $this->buildBase();
		$query = str_replace('{SELECT}',$this->getSelect(),$query);
		$query = str_replace('{ORDER}',$this->getOrder(),$query);
		$query = str_replace('{LIMIT}',$this->getLimit(),$query);
		$query = $this->replaceKeys($query);
		if($this->debug==1) echo "BUILDED: ".$query." <br />\n"; 
		return $query;
	}
	function buildGetCount($count = '') {
	    if($count!='') $this->selectCount = $count;
		$query = $this->buildBase();
		$query = str_replace('{SELECT}',$this->selectCount,$query);
		$query = str_replace('{ORDER}','',$query);
		$query = str_replace('{LIMIT}','',$query);
		$query = $this->replaceKeys($query);
		return $query;
	}
	//---query run functions
	function getCount() {
		$dot = $this->buildGetCount();
		if($this->debug==1) echo "GETCOUNT RUN: ".$dot." <br />\n"; ;
		if($this->cacheResults == true) {
		  $data = $this->getCachedData($dot);
		} else {
			$db = FDBConn::getInstance();
		  $data = $db->getAll($dot);
		}
		if(!DB::iserror($data)) {
		    if(isset($data[0][0])) return $data[0][0];
		}
		else {
      die('Error in query: '.$dot);
    }
	}
	function getContent($from=0,$perPage=0) {
		$dot = $this->buildQuery($from,$perPage);
		if($this->debug==1) echo "GETCONTENT RUN: ".$dot." <br />\n"; ;
		if($this->cacheResults == true) {
		  $data = $this->getCachedData($dot);
		} else {
			$db = FDBConn::getInstance();
		  $data = $db->getAll($dot);
		}
		if(!DB::iserror($data)) return $data;
		else {
      die('Error in query: '.$dot);
    } 
	}
	function get($id) {
	    $this->addWhere($this->primaryCol.'="'.$id.'"');
	    $ret = $this->getContent();
	    if(!empty($ret)) return $ret[0];
	}
	private function getCachedData($query) {
	    global $user;
	    $cacheId = $query;
        $fromCache =  true;
	    if(!$data = $user->cacheGet('fPages',$cacheId)) {
	    	$db = FDBConn::getInstance();
            $data = $db->getAll($query);
            $user->cacheSave(serialize($data));
            $fromCache = false;
		}
		if($fromCache == true) $data = unserialize($data);
		return $data;
	}
	//---get one record
	function getRecord($recordId) {
		$this->setSelect( implode(',',$this->_cols) );
		$this->setWhere($this->primaryCol ."='".$recordId."'");
		return $this->getContent();
	}
	//---save functions
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
	//---save support functions
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
}

