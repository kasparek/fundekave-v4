<?php
/**
 * debug driver for FCache - no caching - help to devbug and you do not have to comment your current cache just change driver to void
 * 
 * PHP versions 4 and 5
 * 
 * @author frantisek.kaspar
 *
 */
class VoidDriver
{
	
	var $lifeTimeDefault = 0;
	var $lifeTime = 0;
	
	var $father;

	private static $instance;
	static function &getInstance($father) {
		if (!isset(self::$instance)) {
			self::$instance = new VoidDriver();
		}
		self::$instance->father=$father;
		return self::$instance;
	}
	
	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	function getGroup( $grp ) {
		return false;
	}

	function setData($key, $data, $grp ) {
		return true;
	}

	function &getPointer( $key, $grp ) {
		return false;
	}
	
	function getData($key, $grp) {
		return false;
	}

	function invalidateData($key,$grp) {
		
	}

	function invalidateGroup( $grp ) {
		
	}

	function invalidate( ) {
		
	}
}