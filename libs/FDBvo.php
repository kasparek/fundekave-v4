<?php
class FDBvo extends FDBTool {

	function __construct() {
		parent::__construct($this->table, $this->primaryCol);
		$this->parseTableDef();
		$this->fetchmode = 1;
		$this->cacheResults = 'l';
	}

	function load() {
		$arr = $this->get( $this->{$this->primaryCol} );
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
		$this->columns = ItemVO::getTypeColumns('',true);
		foreach($this->columns as $col) {
			$this->addCol($col, $this->$col);
		}
		parent::save();
	}
}