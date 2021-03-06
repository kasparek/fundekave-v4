<?php
class FDBvo extends FDBTool {

	var $vo;

	function __construct( &$vo ) {
		$this->columns = $vo->getColumns();
		parent::__construct($vo->getTable(), $vo->getPrimaryCol());
		$this->fetchmode = 1;
		$this->cacheResults = 'l';
		$this->vo = $vo;
	}
	
	function load() {
		if(!$this->primaryCol) {
			write_log('FDBvo::load:20 missing primaryCol');
		}
		$primCol = explode(',',$this->primaryCol);
    $missingPrimValue = false;
    $anyValue = false;
		foreach($primCol as $col) {
      $v = $this->vo->{$col};
      if(empty($v)) $missingPrimValue=true; 
			$keys[] =  $v;
		}
    if(!$missingPrimValue) {
  		$arr = $this->get( implode(',',$keys) );
    } else {
      foreach($this->columns as $col) {
        $v = $this->vo->{$col};
        if(!empty($v)) {
          $this->addWhere($col."='".$v."'");
          $anyValue=true;
        }
      }
      if(!$anyValue) return false;
      $arr = $this->get();
    }
    
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
		$fdb = FDBConn::getInstance();
		$this->queryReset();
		foreach($this->columns as $col=>$select) {
			if($col==$select) //save only real columns
			if( $this->vo->$col !== null ) {
				$this->addCol($col, $fdb->escape($this->vo->$col));
				if($this->vo->$col == 'null') {
					$this->notQuote($col);
					$this->vo->$col = null;
				}
			}
		}
	}

	function save( $cols=array(), $notQuoted=array() ) {
		if($this->vo->getChanged() === true || $this->vo->getSaveOnlyChanged() === false) {
			$this->feed();
			$this->vo->setChanged(false);
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

	function delete( $id ) {
		$primCol = explode(',',$this->primaryCol);
		foreach($primCol as $col) {
			$delArr[$col] = $this->vo->{$col}; 
		}
		return parent::delete( $delArr );
	}
}