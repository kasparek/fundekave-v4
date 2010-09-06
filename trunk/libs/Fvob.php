<?php
class Fvob {
	
	var $table;
	var $primaryCol;
	
	var $columns;
	var $propertiesList;
	
	//---watcher
	public $saveOnlyChanged = false;
	public $changed = false;
	
	function set($key, $value, $params=array()) {
		$changed = false;
		if(property_exists($this,$key)) {
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
		$vo = new FDBvo( $this );
		$vo->load();
	}

	function save(){
		$vo = new FDBvo( $this );
		$vo->feed();
		if($vo->hasKey()) {
			$this->dateUpdated = 'now()';
			$vo->notQuote('dateUpdated');
			$vo->addIgnore('dateCreated');
			$vo->forceInsert = false;
		} else {
			$vo->forceInsert = true;
			$this->dateCreated = 'now()';
			$vo->notQuote('dateCreated');
			$vo->addIgnore('dateUpdated');
		}
		if( $vo->save() ) {
			return true;
		}
	}
	
	function delete() {
	   $vo = new FDBvo( $this );
	   $vo->delete();
	}
  
  public function getTable() {
		return $this->table;
	}
	
	public function getPrimaryCol() {
		return $this->primaryCol;
	}
  
	public function getColumns() {
		return $this->columns;
	}
	
	public function getPropertiesList() {
	  return $this->propertiesList;
	}	
}