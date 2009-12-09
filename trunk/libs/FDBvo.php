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
		$primCol = explode(',',$this->primaryCol);
		foreach($primCol as $col) {
			$keys[] =  $this->vo->{$col};
		}
		$arr = $this->get( implode(',',$keys) );
		if(!empty($arr)) {
			foreach($arr as $k=>$v) {
				$this->vo->{$k} = $v;
			}
			return true;
		} else {
			foreach($primCol as $col) {
				$this->vo->{$col}=0;
			}
			return false;
		}
	}

	function feed() {
		$this->queryReset();
		
		foreach($this->columns as $col=>$select) {
			if($col==$select) //save only real columns
			if( $this->vo->$col !== null ) {
				$this->addCol($col, $this->vo->$col);
				if($this->vo->$col == 'null') {
					$this->notQuote($col);
					$this->vo->$col = null;
				}
			}
		}
	}

	function save() {
		if($this->vo->changed === true || $this->vo->saveOnlyChanged === false) {
			$this->feed();
			$this->vo->changed = false;
			$id = parent::save();
			$idList = explode(',',$id);
			$primCol = explode(',',$this->primaryCol);
			$i=0;
			foreach($primCol as $col) {
				$this->vo->{$col} = $idList[$i];
				$i++;
			}
			return $id; 
		}
	}

	function delete() {
		$primCol = explode(',',$this->primaryCol);
		foreach($primCol as $col) {
			$delArr[$col] = $this->vo->{$col}; 
		}
		return parent::delete( $delArr );
	}
}