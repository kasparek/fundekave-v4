<?php
class FDBvo extends FDBTool {
	var $saveOnlyChanged = false;
	var $changed = false;
	
	function __construct() {
		parent::__construct($this->table, $this->primaryCol);
		$this->parseTableDef();
		$this->fetchmode = 1;
		$this->cacheResults = 'l';
	}
	
	function set($key, $value, $params=array()) {
		$changed = false;
		if(isset($this->{$key})) {
			//---verify
			if(isset($params['type'])) {
				switch($params['type']) {
					case 'date':
						$value = FSystem::switchDate($value);
						if(true !== FSystem::isDate($value)) return false;
					break;
				}
			}
			//---check if changed
			if($this->{$key} != $value) {
				$changed = true;
				$this->changed = $changed; 
			}
			//---set
			$this->{$key} = $value;
			
		}
		return $changed;
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
		if($this->changed === true || $this->saveOnlyChanged === false) {
			$this->queryReset();
			foreach($this->columns as $col) {
				if( $this->$col !== null ) {
					$this->addCol($col, $this->$col);
					if($this->$col == 'null') {
						$this->notQuote($col);
						$this->$col = null;
					}
				}
			}
			$this->changed = false;
			return $this->{$this->primaryCol} = parent::save();
		}
	}
	
	function delete() {
		parent::delete($this->{$this->primaryCol});
	}
}