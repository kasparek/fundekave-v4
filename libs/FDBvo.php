<?php
class FDBvo extends FDBTool {

	function __construct() {
		parent::__construct();
		$this->parseTableDef();
		$this->fetchmode = 1;
		$this->cacheResults = 'l';
	}

	function load() {
		$pCol = $this->primaryCol;
		$arr = $this->get( $this->$pCol );
		if(!empty($arr)) {
			foreach($arr as $k=>$v) {
				$this->$k = $v;
			}
		}
	}
	
	function map($arr) {
		if(!empty($arr)) {
			foreach($arr as $k=>$v) {
				$this->$k = $v;
			}
		}
	}
	
	function save() {
		$this->queryReset();
		foreach($this->columns as $col) {
			$this->addCol($col, $this->$col);
		}
		parent::save();
	}
}