<?php
/**
 * file driver for FCache
 * 
 * PHP 5
 * 
 * @author frantisek.kaspar
 *
 */
class FileDriver 
{
	var $cacheEngine;

	//---could be null to live forever
	var $lifeTimeDefault = 0;
	var $lifeTime = 0;

	function __construct() {
		$cacheDir = FConf::get('settings','cache_path'); 
		if(!is_dir($cacheDir)) mkdir($cacheDir,0777,true);
		$cacheOptions['cacheDir'] = $cacheDir;
		$cacheOptions['lifeTime'] = $this->lifeTime==0 ? null : $this->lifeTime;
		$cacheOptions['hashedDirectoryLevel'] = 2;
		$this->cacheEngine = new FCacheFile($cacheOptions);
	}
	
	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new FileDriver();
		}
		return self::$instance;
	}

	function setConf( $lifeTime ) {
		$this->cacheEngine->setLifeTime = $this->lifeTime = empty($lifeTime) ? null : $lifeTime;
	}

	function setData($key, $data, $grp) {
		return $this->cacheEngine->save( serialize($data), $key, $grp );
	}

	function getGroup($grp) {
		return false;
	}

	function getPointer( $key, $grp) {
		return false;
	}
	
	function getData($key, $grp) {
		return unserialize($this->cacheEngine->get($key,$grp));
	}

	function invalidateData($key, $grp) {
		$this->cacheEngine->remove($key, $grp);
	}

	function invalidateGroup( $grp ) {
		$this->cacheEngine->clean($grp);
	}

	function invalidate( ) {
		$this->cacheEngine->clean();
	}
}