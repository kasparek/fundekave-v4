<?php
/**
 * Database tool 
 * 
 * @author frantisek.kaspar
 *
 */
class FDBTool {
	
	/**
	 * 0 - number indexed data
	 * 1 - name indexed
	 * @var int
	 */
	var $fetchmode = 0;
	/**
	 *cacheResults - driver
	 * 
	 * 0 - no cache
	 * l - cache per load
	 * s - cache per session
	 * d - database 
	 * f - cache in file
	 **/
	var $cacheResults = 0;
	/**
	 * lifetime in seconds of cached results
	 *
	 * @var Number
	 */
	var $lifeTime = 0;
	
	var $debug = 0;
	
	var $queryTemplate = 'select {SELECT} from {TABLE} {JOIN} where {WHERE} {GROUP} {ORDER} {LIMIT}';
	var $table = '';
	var $primaryCol = '';
	var $tableDef;
	var $columns;
	
	private $_where = array();
	private $_order = array();
	private $_select = array('*');
	private $selectCount = 'count(1)';
	private $_group = array();
	private $autojoin = false;
	private $_join = '';
	private $_limit = array();
	
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
		$this->debug = FConf::get('dboptions','debug');
		$this->table = $tableName;
		$this->variablesRegExp = '@' . $this->openingDelimiter . '(' . $this->variablenameRegExp . ')' . $this->closingDelimiter . '@sm';
		$this->replaceKeys = array_keys($this->replaceVars);
		$this->primaryCol = $primaryCol;
	}
	
	function parseTableDef() {
		if(!empty($this->tableDef)) {
			require_once('SQL/Parser.php');
			$parser = new SQL_Parser($this->tableDef,'MySQL');
			$parsed = $parser->parse();
			$this->table = $parsed['table_names'][0];
			foreach($parsed["column_defs"] as $k=>$v) {
				$this->columns[] = $k;
				if(isset($v["constraints"])) {
					foreach($v["constraints"] as $constr) {
						if($constr["type"]=='primary_key') {
							$this->primaryCol = $k;
						}
					}
				}
			}
		}
	}
	
	function queryReset() {
		$this->_where = array();
		$this->_join = '';
		$this->_order = array();
		$this->_group = array();
	}
	function setTemplate($template) {
		$this->queryTemplate = $template;
	}
	function getTemplate() {
		return $this->queryTemplate;
	}
	function getWhere() {
		if(empty($this->_where)) {
			$where[] = '1';
		} else if($this->autojoin===true) {
			foreach($this->_where as $cond) {
				if(!strpos($conf,'.')) {
					$cond = $this->table.'.'.$cond;
				}
				$where[] = $cond;
			}
		} else {
			$where = $this->_where;
		}
		return ' '.implode(' ',$where);
	}
	function setWhere($whereCondition) {
		$this->_where = array($whereCondition);
	}
	function addWhere($where,$condition='AND') {
		$len = count($this->_where);
		if($len>0) {
			$this->_where[$len-1] .= $condition;
		}
		$this->_where[] = ' '.$where.' ';
	}
	function addJoin($condition) {
		$this->_join .= ' '.$condition;
	}
	function addJoinAuto($table,$joinColumn,$selectColumnsArray,$type='LEFT JOIN') {
		$this->_join .= ' '.$type.' '.$table.' on ' . $this->table.'.'.$column. '=' .$table.'.'.$column;
		$this->autoJoin = true;
		foreach($selectColumnsArray as $col) {
			$this->addSelect($table.'.'.$col);
		}
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
		$orderCondition = $orderCondition .($desc ? ' DESC' : '');
		$this->_order = explode(',',$orderCondition);
	}
	function addOrder($orderCondition='', $desc=false) {
		$this->_order[] = $orderCondition .($desc ? ' DESC' : '');
	}
	function getOrder() {
	 if($this->autojoin===true) {
	 	foreach($this->_order as $cond) {
	 		if(!strpos($cond,'.')) {
	 			$cond = $this->table.'.'.$cond;
	 		}
	 		$order[] = $cond;
	 	}
	 } else {
	 	$order = $this->_order;
	 }
	 if(!empty($order)) return ' order by '.implode(',',$order);
	}
	function setSelect($what='*') {
		$this->_select = explode(',',$what);
	}
	function addSelect($what='*') {
		$this->_select[] = $what;
	}
	function replaceSelect($what,$with) {
		$len=count($this->_select);
		for($i=0;$i<$len;$i++){
			$this->_select[$i] = str_replace($what,$with,$this->_select[$i]);
		}
	}
	function getSelect() {
	 if($this->autojoin) {
	 	foreach($this->_select as $col) {
	 		if(!strpos($col,'.')) $col = $this->table.'.'.$col;
	 		$arrCols[] = $col;
	 	}
	 } else {
	 	$arrCols = $this->_select;
	 }
		return implode(',',$arrCols);
	}
	function setLimit($from=0, $count=0) {
		if ($from==0 && $count==0) $this->_limit = array();
		else $this->_limit = array($from, $count);
	}
	function getLimit() {
		return ((!empty($this->_limit))?(' limit '.$this->_limit[0].','.$this->_limit[1]):(''));
	}
	function setGroup($group='') {
		$this->_group = explode(',',$group);
	}
	function getGroup() {

		if($this->autojoin===true) {
			foreach($this->_group as $cond) {
				if(!strpos($cond,'.')) {
					$cond = $this->table.'.'.$cond;
				}
				$group[] = $cond;
			}
		} else {
			$group = $this->_group;
		}
		if(!empty($group)) return ' group by '.implode(',',$group);
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
		if($this->debug == 1) echo "BUILDED: ".$query." <br />\n";
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
		if($this->debug == 1) echo "GETCOUNT RUN: ".$dot." <br />\n"; ;
		return FDBTool::getOne($dot, md5($dot), 'fdb', $this->cacheResults, $this->lifeTime);
	}
	function getContent($from=0,$perPage=0, $cacheId=false) {
		$dot = $this->buildQuery($from,$perPage);
		if($this->debug == 1) echo "GETCONTENT RUN: ".$dot." <br />\n"; ;
		return FDBTool::getAll($dot,(($cacheId!==false)?($cacheId):(md5($dot))),'fdb',$this->cacheResults,$this->lifeTime);
	}
	function getCacheId($id) {
		return $this->table.'-'.$this->primaryCol.'-'.$id;
	}
	function get($id) {
		if(empty($this->_select) && !empty($this->columns)) {
			$this->setSelect(implode(',',$this->columns));
		}
		$this->addWhere($this->primaryCol.'="'.$id.'"');
		$ret = $this->getContent(0,0,$this->getCacheId($id));
		if(!empty($ret)) {
			if($this->fetchmode == 1 && !empty($this->columns)) {
				$len = count($arr);
				for($i=0; $i<$len; $i++) {
					$col = $this->columns[$i];
					$this->$col = $arr[$i];
				}
			}
			return $ret[0];	
		}
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
		return FDBTool::getOne("SELECT LAST_INSERT_ID()");
	}
	function save( $cols=array(), $notQuoted=array(), $forceInsert=false ) {
		if(!empty($cols)) $this->setCols($cols,$notQuoted);
		$insert = false;
		if(empty($this->_cols[$this->primaryCol]) || $forceInsert) {
			$dot = $this->buildInsert();
			$insert = true;
		} else {
			$retId = $this->_cols[$this->primaryCol];
			$dot = $this->buildUpdate();
		}
		//---save
		if($this->debug==1) echo $dot;
		if(FDBTool::query($dot)) {
			if($insert) $retId = $this->getLastId();
		}
		//---invalidate cache
		if($this->cacheResults!=0) {
			$cache = FCache::getInstance($this->cacheResults);
			$cache->invalidateData($this->getCacheId($retId),'fdb');
		}
		return $retId;
	}
	function delete($id) {
		return FDBTool::query("delete from ".$this->table." where ".$this->primaryCol."=".$this->quoteType.$id.$this->quoteType);
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

	//---simple query
	static function getAll($query, $key=null, $grp='default', $driver='l', $lifeTime=-1) {
		if($key !== null || $driver != 0) {
			//---cache results
			$cache = FCache::getInstance($driver);
			if($lifeTime > -1) $cache->setConf($lifeTime);
			if(false === ($ret = $cache->getData($key,$grp))) {
				$ret = FDBTool::getData('getAll',$query);
				$cache->setData( $ret );
			}
		} else {
			//---no cache
			$ret = FDBTool::getData('getAll',$query);
		}
		return $ret;
	}

	static function getRow($query, $key=null, $grp='default', $driver='l', $lifeTime=-1) {
		if($key!==null) {
			//---cache results
			$cache = FCache::getInstance($driver);
			if($lifeTime > -1) $cache->setConf($lifeTime);
			if( ($ret = $cache->getData($key,$grp)) === false ) {
				$ret = FDBTool::getData('getRow',$query);
				$cache->setData( $ret );
			}
		} else {
			//---no cache
			$ret = FDBTool::getData('getRow',$query);
		}
		return $ret;
	}

	static function getCol($query, $key=null, $grp='default', $driver='l', $lifeTime=-1) {
		if($key!==null) {
			//---cache results
			$cache = FCache::getInstance($driver);
			if($lifeTime > -1) $cache->setConf($lifeTime);
			if( ($ret = $cache->getData($key,$grp)) === false ) {
				$ret = FDBTool::getData('getCol',$query);
				$cache->setData( $ret );
			}
		} else {
			//---no cache
			$ret = FDBTool::getData('getCol',$query);
		}
		return $ret;
	}

	static function getOne($query, $key=null, $grp='default', $driver='l', $lifeTime=-1) {
		if($key!==null) {
			//---cache results
			$cache = FCache::getInstance($driver);
			if($lifeTime > -1) $cache->setConf($lifeTime);
			if( ($ret = $cache->getData($key,$grp)) === false ) {
				$ret = FDBTool::getData('getOne',$query);
				$cache->setData( $ret );
			}
		} else {
			//---no cache
			$ret = FDBTool::getData('getOne',$query);
		}
		return $ret;
	}

	static function query($query) {
		return FDBTool::getData('query',$query);
	}
	
	private static function getData($function, $query) {
		$db = FDBConn::getInstance();
		$ret = $db->$function($query);
		if (PEAR::isError($ret)) {
			echo $ret->getMessage();
			if(FConf::get('dboptions','debug')==1) {
				echo " <br />\n";
			    echo 'Code: ' . $db->getCode() . " <br />\n";
			    echo 'DBMS/User Message: ' . $db->getUserInfo() . " <br />\n";
			    echo 'DBMS/Debug Message: ' . $db->getDebugInfo() . " <br />\n";
			}
			die();
		}
		return $ret;
	}
}

