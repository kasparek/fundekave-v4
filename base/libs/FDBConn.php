<?php
class FDBConn extends mysqli
{
	private static $instance;
	private static $allowInstantiation = false;
	
	public $assoc = false;

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
			$this->query("SET time_zone = '".FConf::get('internationalization','timezone')."';");
		}
	}
	
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$allowInstantiation = true;
			self::$instance = new FDBConn();
			self::$allowInstantiation = false;
		}
		self::$instance->assoc = false;
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
}
