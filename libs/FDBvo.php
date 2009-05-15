<?php
class FDBvo extends FDBTool {
	function __construct() {
		parent::__construct();
	
	    require_once('SQL/Parser.php');
	    $parser = new SQL_Parser($this->tableDef,'MySQL');
		$parsed = $parser->parse();
	    
	    $this->table = $parsed['table_names'][0];
	    
	    foreach($parsed["column_defs"] as $k=>$v) {
	    	$this->_cols[] = $k;
	    	if(isset($v["constraints"])) {
	    	foreach($v["constraints"] as $constr) {
	    		if($constr["type"]=='primary_key') {
	    			$this->primaryCol = $k;
	    		}
	    	}
	    	}
	    }
	}
	
	function load() {
		
	  	$pCol = $this->primaryCol;
	  	$arr = $this->getRecord( $this->$pCol );
	  	if(!empty($arr)) {
	  	$len = count($arr[0]);
	  	for($i=0;$i<$len;$i++) {
	  		$col = $this->_cols[$i];
	  		$this->$col = $arr[0][$i];
	  	}
	    }
	  }
	
}