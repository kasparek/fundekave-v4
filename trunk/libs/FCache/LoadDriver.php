<?php
/**
 * memory driver for FCache
 * 
 * PHP versions 4 and 5
 * 
 * @author frantisek.kaspar
 *
 */
class LoadDriver
{
	var $data;

	var $lifeTimeDefault = 0;
	var $lifeTime = 0;

	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new LoadDriver();
		}
		return self::$instance;
	}
	
	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	function getGroup( $grp ) {
		if(isset($this->data[$grp])) {
			$arr = $this->data[$grp];
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row);
			}
			return $arrUnserialized;
		} else return false;
	}

	function setData($key, $data, $grp ) {
		$this->data[$grp][$key] = serialize($data);
		return true;
	}

	function getData($key, $grp) {
		if(isset($this->data[$grp][$key])) {
			return unserialize($this->data[$grp][$key]);
		} else return false;
	}

	function invalidateData($key,$grp) {
		if(isset($this->data[$grp][$key])) {
			unset($this->data[$grp][$key]);
		}
	}

	function invalidateGroup( $grp ) {
		$this->data[$grp] = array();
	}

	function invalidate( ) {
		$this->data = array();
	}
}