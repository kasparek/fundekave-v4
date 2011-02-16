<?php
class Fvob {

	var $debug=0;
	
	protected $cacheType = 'l';
	
	protected $table;
	protected $primaryCol;
	protected $columns;
	protected $propertiesList;
	protected $propDefaults;

	//extra array of key/value array properties
	public $properties;

  //---public watcher
  public $loaded = false;
	public $loadedCached = false;
	//---watcher
	protected $forceInsert = false;
	protected $saveIgnore = array();
	protected $saveOnlyChanged = false;
	protected $changed = false;
		
	function __construct($primaryId=0, $autoLoad = false) {
		$this->properties = new stdClass();
		$this->{$this->primaryCol} = $primaryId;
		if($autoLoad == true) $this->load();
	}
	
	public function setForceInsert($val){ $this->forceInsert = $val; }
	public function setSaveIgnore($arr){ $this->saveIgnore = $arr; }
	
	public function getSaveOnlyChanged() { return $this->saveOnlyChanged; }
	public function setSaveOnlyChanged($val){ $this->saveOnlyChanged = $val; }
	
	public function getChanged() { return $this->changed; }
	public function setChanged($val){ $this->changed = $val; }

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
	
	function get($key,$load=true) {
		if(!$this->loaded) $this->load();
		return $this->{$key};
	}
	
	function set($key, $value, $params=array()) {
		if(!property_exists($this,$key)) return false;
		//---verify
		if(isset($params['type'])) {
			switch($params['type']) {
				case 'date':
					$value = FSystem::checkDate($value);
					break;
			}
		}
		if($key=='typeId') {
			if(!in_array($value,array('forum','galery','event','blog'))) return false;
		}
		$changed = false;
		//---check if changed
		if($this->{$key} != $value) {
			$changed = true;
			$this->changed = $changed;
		}
		//---set
		$this->{$key} = $value;
		return $changed;
	}

	function loadCached() {
		if(empty($this->{$this->primaryCol})) return false;
		$dataVO = $this->memGet();
		if($dataVO===false) return false;
		$this->reload($dataVO);
		$this->loadedCached = true;
		return true;
	}

	function load() {
		if(empty($this->{$this->primaryCol})) return false;
		$this->loadCached();
		if($this->loaded===false) {
			$vo = new FDBvo( $this );
			$this->loaded = $vo->load();
			if(!$this->loaded) $this->{$this->primaryCol} = null;
			else $this->memStore();
			return $this->loaded;
		}
		return true;
	}
	
	function reload($VO) {
		if(!empty($VO))
		foreach($VO as $key => $val) {
			if($this->debug) echo $key.'='.$val."<br>\n";
			$this->{$key} = $val;
		}
	}

	function save(){
		$vo = new FDBvo( $this );
		if(!empty($this->saveIgnore)) foreach($this->saveIgnore as $col) $vo->addIgnore($col);
		if($this->forceInsert===false && !empty($this->{$this->primaryCol})) {
			$this->dateUpdated = 'now()';
			$vo->notQuote('dateUpdated');
			$vo->addIgnore('dateCreated');
			$vo->forceInsert = false;
		} else {
			$this->forceInsert=false;
			$vo->forceInsert = true;
			$this->dateCreated = 'now()';
			$vo->notQuote('dateCreated');
			$vo->addIgnore('dateUpdated');
		}
		if( $vo->save() ) {
			//---update in cache
			$this->memFlush();
			//---update primary value
			$this->{$this->primaryCol} = $vo->vo->{$this->primaryCol};
			return $this->{$this->primaryCol};
		}
	}

	function delete() {
		$vo = new FDBvo( $this );
		$vo->delete();
	}

	function prop($propertyName,$value=null,$load=true) {
		if(!is_null($value)) $this->setProperty($propertyName,$value);
		$default='';
		if(isset($this->propDefaults[$propertyName])) $default = $this->propDefaults[$propertyName];
		return $this->getProperty($propertyName,$default,$load);
	}

	//---special properties
	function getProperty($propertyName,$default=false,$load=false) {
		if(empty($propertyName)) {
			FError::write_log("Fvob::getProperty - missing propertyName -  default:".$default);
			return;
		}
		if(!$this->loadedCached) $this->loadCached();
		$value = null;
		if(isset($this->properties->$propertyName)) {
			$value = $this->properties->$propertyName;
		} else {
			if($load===true) {
				$value = FDBTool::getOne("select value from ".$this->getTable()."_properties where ".$this->getPrimaryCol()."='".$this->{$this->getPrimaryCol()}."' and name='".$propertyName."'");
				//---set in list
				if(!is_numeric($value) && empty($value)) $value = false;
				if($value === false || $value === null) $value = $default;
				$this->properties->$propertyName = $value;
				//---save in cache
				$this->memStore();
			}
		}
		if($value === false || $value === null) $value = $default;
		return $value;
	}

	function setProperty($propertyName,$propertyValue) {
		//check if needed to be saved
		if(isset($this->properties->$propertyName)) {
			if($propertyValue==$this->properties->$propertyName || (empty($propertyValue) && empty($this->properties->$propertyName))) return;
		}
		$prop=$this->getProperty($propertyName,false,true);
		$ret=false;
		//save in db
		if(is_null($propertyValue) || $propertyValue===false) {
			FDBTool::query("delete from ".$this->getTable()."_properties where ".$this->getPrimaryCol()."='".$this->{$this->getPrimaryCol()}."' and name='".$propertyName."'");
			$propertyValue = false;
			if(!empty($prop)) $ret=true;
		} else {
			$db = FDBConn::getInstance();
			$propertyValue = $db->escape($propertyValue);
			FDBTool::query("insert into ".$this->getTable()."_properties (".$this->getPrimaryCol().",name,value) values ('".$this->{$this->getPrimaryCol()}."','".$propertyName."','".$propertyValue."') on duplicate key update value='".$propertyValue."'");
			if($prop!=$propertyValue) $ret=true;
		}
		//---save in cache
		$this->properties->$propertyName = $propertyValue;
		//---update cache
		$this->memStore();
		return $ret;
	}
	
	
	function memStore() {
		$c = FCache::getInstance($this->cacheType,-1,FCache::SERIALIZE_JSON);
		$c->setData( $this, $this->{$this->primaryCol}, 'cached_'.$this->table);
	}
	function memGet() {
		$c = FCache::getInstance($this->cacheType,-1,FCache::SERIALIZE_JSON);
		return $c->getData($this->{$this->primaryCol}, 'cached_'.$this->table);
	}
	function memFlush() {
		$c = FCache::getInstance($this->cacheType,-1,FCache::SERIALIZE_JSON);
		$c->invalidateData($this->{$this->primaryCol}, 'cached_'.$this->table);
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