<?php
class Fvob {

	var $table;
	var $primaryCol;

	var $columns;
	var $propertiesList;
	public $propDefaults;

	//extra array of key/value array properties
	var $properties;

	//---watcher
	public $saveOnlyChanged = false;
	public $changed = false;

	public function date($value,$format) {
		if(!$value) return null;
		switch($format) {
			case 'iso':
				$format = DATE_ATOM;
				break;
			default:
				$formatConf = FConf::get('internationalization',$format);
				if($formatConf) $format = $formatConf;
		}
		if(!$format) return null;
		return date($format, strtotime($value));
	}

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

	function prop($propertyName,$value=null,$load=true) {
		if($value!==null) $this->setProperty($propertyName,$value);
		$default='';
		if(isset($this->propDefaults[$propertyName])) $default = $this->propDefaults[$propertyName];
		return $this->getProperty($propertyName,$default,$load);
	}

	//---special properties
	function getProperty($propertyName,$default=false,$load=false) {
		$value = null;
		if(isset($this->properties[$propertyName])) {
			$value = $this->properties[$propertyName];
		} else {
			if($load===true) {
				$q = "select value from ".$this->getTable()."_properties where ".$this->getPrimaryCol()."='".$this->{$this->getPrimaryCol()}."' and name='".$propertyName."'";
				$value = FDBTool::getOne($q);
				//---set in list
				if(empty($value)) $value = false;
				$this->properties[$propertyName] = $value;
				//---save in cache
				$this->memStore();
			}
		}
		if($value === false || $value === null) $value = $default;
		return $value;
	}

	function setProperty($propertyName,$propertyValue) {
		//check if needed to be saved
		if(isset($this->properties[$propertyName])) {
			if($propertyValue==$this->properties[$propertyName]) return;
		}
		//save in db
		if(empty($propertyValue)) {
			FDBTool::query("delete from ".$this->getTable()."_properties where ".$this->getPrimaryCol()."='".$this->{$this->getPrimaryCol()}."' and name='".$propertyName."'");
			$propertyValue = false;
		} else {
			FDBTool::query("insert into ".$this->getTable()."_properties (".$this->getPrimaryCol().",name,value) values ('".$this->{$this->getPrimaryCol()}."','".$propertyName."','".$propertyValue."') on duplicate key update value='".$propertyValue."'");
		}
		//---save in cache
		$this->properties[$propertyName] = $propertyValue;
		//---update cache
		$this->memStore();
	}

	function memStore() {
		$cache = FCache::getInstance('l');
		$cache->setData( $this, $this->itemId, 'cached'.$this->getTable());
	}
	function memGet() {
		$cache = FCache::getInstance('l');
		return $cache->getData($this->itemId, 'cached'.$this->getTable());
	}
	function memFlush() {
		$cache = FCache::getInstance('l');
		$cache->invalidateData($this->itemId, 'cached'.$this->getTable());
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