<?php
class FDBvo extends FDBTool {

	var $vo;

	function __construct( &$vo ) {
		$this->columns = $vo->columns;
		parent::__construct($vo->table, $vo->primaryCol);
		$this->fetchmode = 1;
		if(!isset($vo->cacheResults)) {
			$this->cacheResults = 'l';
		} else {
			$this->cacheResults = $vo->cacheResults;
		}
		$this->vo = $vo;
	}

	function load() {
		$arr = $this->get( $this->vo->{$this->primaryCol} );
		if(!empty($arr)) {
			foreach($arr as $k=>$v) {
				$this->vo->{$k} = $v;
			}
		}
	}

	function save() {
		if($this->vo->changed === true || $this->vo->saveOnlyChanged === false) {
			$this->queryReset();
			foreach($this->columns as $col) {
				if( $this->vo->$col !== null ) {
					$this->addCol($col, $this->vo->$col);
					if($this->vo->$col == 'null') {
						$this->notQuote($col);
						$this->vo->$col = null;
					}
				}
			}
			$this->vo->changed = false;
			return $this->vo->{$this->primaryCol} = parent::save();
		}
	}

	function delete() {
		parent::delete($this->vo->{$this->primaryCol});
	}
}