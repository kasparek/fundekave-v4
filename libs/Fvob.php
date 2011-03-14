<?php
class Fvob {

	var $debug=0;
	
	protected $cacheType = 'l';

	//need to be overriden in child class
	public function getTable() { return ''; }
	public function getPrimaryCol() { return ''; }
	public function getColumns() { return ''; }
		
	protected $propertiesList;
	protected $propDefaults;
	protected $propLoadAtOnce=false;

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
		$this->{$this->getPrimaryCol()} = $primaryId;
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
    if($this->{$key} != $value) return false; //has not changed
		$this->changed = true;
		$this->{$key} = $value;
		return true;
	}

	function loadCached() {
		if(empty($this->{$this->getPrimaryCol()})) return false;
		$dataVO = $this->memGet();
		if($dataVO===false) return false;
		$this->reload($dataVO);
		$this->loadedCached = true;
		return true;
	}

	function load() {
		if(empty($this->{$this->getPrimaryCol()})) return false;
		$this->loadCached();
		if($this->loaded===false) {
			$vo = new FDBvo( $this );
			$this->loaded = $vo->load();
			if(!$this->loaded) $this->{$this->getPrimaryCol()} = null;
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
		if($this->forceInsert===false && !empty($this->{$this->getPrimaryCol()})) {
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
			$this->{$this->getPrimaryCol()} = $vo->vo->{$this->getPrimaryCol()};
			return $this->{$this->getPrimaryCol()};
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
		if(property_exists($this->properties,$propertyName)) {
			$value = $this->properties->$propertyName;
		} else {
			if($load===true) {
				$value=null;
				if(!$this->propLoadAtOnce) {
					$value = FDBTool::getOne("select value from ".$this->getTable()."_properties where ".$this->getPrimaryCol()."='".$this->{$this->getPrimaryCol()}."' and name='".$propertyName."'");
				} else {
					$q="select name,value from ".$this->getTable()."_properties where ".$this->getPrimaryCol()."='".$this->{$this->getPrimaryCol()}."'";
					$values = FDBTool::getAll($q);
					foreach($this->propertiesList as $p) 
						if(!property_exists($this->properties,$p)) 
							$this->properties->{$p} = null; 
					foreach($values as $row) {
						  $this->properties->{$row[0]} = $row[1];
						  if($row[0]==$propertyName) $value=$row[1];
					}
				}
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
		if(property_exists($this->properties,$propertyName)) {
			if($propertyValue==$this->properties->$propertyName || (empty($propertyValue) && empty($this->properties->$propertyName))) return;
		}
		$prop = $this->getProperty($propertyName,false,true);
		$ret = false;
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
		$c->setData( $this, $this->{$this->getPrimaryCol()}, 'cached_'.$this->getTable());
	}
	function memGet() {
		$c = FCache::getInstance($this->cacheType,-1,FCache::SERIALIZE_JSON);
		return $c->getData($this->{$this->getPrimaryCol()}, 'cached_'.$this->getTable());
	}
	function memFlush() {
		$c = FCache::getInstance($this->cacheType,-1,FCache::SERIALIZE_JSON);
		$c->invalidateData($this->{$this->getPrimaryCol()}, 'cached_'.$this->getTable());
	}

	public function getPropertiesList() {
		return $this->propertiesList;
	}
}