<?php
/**
 * session driver for FCache
 * 
 * PHP versions 4 and 5
 * 
 * @author frantisek.kaspar
 *
 */
class SessionDriver
{

	var $data;
	var $lifeTimeDefault = 0;
	var $lifeTime = 0;

	function __construct() {
		$this->data = &$_SESSION['FCache_data'];
	}
	
	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new SessionDriver();
		}
		return self::$instance;
	}

	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	function getGroup($grp) {
		if(isset($this->data[$grp])) {
			$arr = $this->data[$grp];
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row[2]);
			}
			return $arrUnserialized;
		} else return false;
	}

	function setData($key, $data, $grp) {
		$time = 0;
		if($this->lifeTime > 0) $time = date("U");
		$this->data[$grp][$key] = array($this->lifeTime, $time , serialize($data));
		return true;	
	}

	function getData($key, $grp) {
		if(isset($this->data[$grp][$key])) {
			$data = $this->data[$grp][$key];
			if($data[0] + $data[1] > date("U") || $data[0]==0) {
				return unserialize($data[2]);
			} else {
				$this->invalidateData($key, $grp);
			}
		} else {
			return false;
		}
	}

	function invalidateData($key,$grp) {
		if(isset($this->data[$grp][$key])) {
			unset($this->data[$grp][$key]);
		}
	}

	function invalidateGroup( $grp ) {
		unset($this->data[$grp]);
	}

	function invalidate( ) {
		$this->data = array();
	}
}