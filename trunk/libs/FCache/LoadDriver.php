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

	var $father;

	var $data;

	var $lifeTimeDefault = 0;
	var $lifeTime = 0;

	private static $instance;
	static function &getInstance($father) {
		if (!isset(self::$instance)) {
			self::$instance = new LoadDriver();
		}
		//self::$instance->father=$father;
		return self::$instance;
	}
	
	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	function getGroup( $grp ) {
		if(isset($this->data[$grp])) return $this->data[$grp];
		return false;
	}

	function setData($key, $data, $grp ) {
		$this->data[$grp][$key] = $data;
		return true;
	}

	function &getPointer( $key, $grp ) {
		if(!isset($this->data[$grp][$key])) $this->data[$grp][$key] = false;
		return $this->data[$grp][$key];
	}
	
	function getData($key, $grp) {
		if(isset($this->data[$grp][$key])) return $this->data[$grp][$key];
		return false;
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