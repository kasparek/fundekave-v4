<?php
class FDBConn extends mysqli
{
	private static $instance;
	private static $allowInstantiation = false;
	
	public $assoc = false;
	private $cache;

	function __construct() {
		if(self::$allowInstantiation==true) {
			$conf = FConf::getInstance();
			$dbConf = $conf->a['db'];
			parent::__construct($dbConf['hostspec'], $dbConf['username'], $dbConf['password'], $dbConf['database']);
			if (mysqli_connect_error()) {
				die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			}
			$this->query("set character_set_client = ".$dbConf['charset']);
			$this->query("set character_set_connection= ".$dbConf['charset']);
			$this->query("set character_set_results = ".$dbConf['charset']);
			$this->query("set character_name = ".$dbConf['charset']);
		}
	}
	
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$allowInstantiation = true;
			self::$instance = new FDBConn();
			self::$allowInstantiation = false;
		}
		return self::$instance;
	}
	
	public function escape($text) {
		return $this->real_escape_string($text);
	}

	public function getOne($q) {
		$ret = false;
		if ($result = $this->query($q)) {
			if($result->num_rows > 0) {
				$row = $result->fetch_row();
				$ret = $row[0];
			}
			$result->free();
		}
		return $ret;
	}

	public function getRow($q) {
		$ret = array();
		if ($result = $this->query($q)) {
			if($result->num_rows > 0) {
				if($this->assoc===true) $ret = $result->fetch_assoc();
				else $ret = $result->fetch_row();
			}
			$result->free();
		}
		return $ret;
	}

	public function getCol($q) {
		$ret = array();
		if ($result = $this->query($q)) {
			if($result->num_rows > 0) {
				while($row = $result->fetch_row()) {
					$ret[] = $row[0];
				}
			}
			$result->free();
		}
		return $ret;
	}

	public function getAll($q) {
		$ret = array();
		if ($result = $this->query($q)) {
			if($result->num_rows > 0) {
				if($this->assoc===true) while($row = $result->fetch_assoc()) { $ret[] = $row; }
				else while($row = $result->fetch_row()) { $ret[] = $row; }
			}
			$result->free();
		}
		return $ret;
	}

	function kill() {
		$this->close();
	}
	
	public function queueProcess() {
    $cache = $this->getCache();
    $dataRaw = $cache->get('queue');
    if($dataRaw===false) return;
    $data = unserialize($dataRaw);
    if(!empty($data)) {
			foreach($data as $q) {
				$this->query($q);
				FError::write_log("queueProcess - ".$q);
			}
		}
    $cache->remove('queue');
	}
	
	public function queuePush($query) {
		$cache = $this->getCache();
		$data = array();
		$dataRaw = $cache->get('queue');
		if($dataRaw!==false) $data = unserialize($dataRaw);
		$data[]=$query;
		$cache->save(serialize($data)); 
	}
	
	private function &getCache() {
		if($this->cache) return $this->cache;
		$cacheDir = FConf::get('settings','logs_path').'fdbtool/'; 
		if(!is_dir($cacheDir)) mkdir($cacheDir,0777,true);
		$this->cache = new FCacheFile(array('cacheDir'=>$cacheDir));
		return $this->cache;
	} 
}
