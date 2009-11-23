<?php
require_once(ROOT.'pear/Cache/Lite.php');
/**
 * file driver for FCache
 * 
 * PHP versions 4 and 5
 * 
 * dependency: PEAR::Cache_Lite
 * 
 * @author frantisek.kaspar
 *
 */
class FileDriver 
{
	var $cacheLite;

	//---could be null to live forever
	var $lifeTimeDefault = 60;
	var $lifeTime = 60;

	function __construct() {
		$cacheDir = FConf::get('settings','cache_path'); 
		if(!is_dir($cacheDir)) mkdir($cacheDir,0777,true);
		$cacheOptions['cacheDir'] = $cacheDir;
		$cacheOptions['lifeTime'] = $this->lifeTime;
		$this->cacheLite = new Cache_Lite($cacheOptions);
	}
	
	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new FileDriver();
		}
		return self::$instance;
	}

	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
		$this->cacheLite->setLifeTime = $lifeTime;
	}

	function setData($key, $data, $grp) {
		return $this->cacheLite->save( serialize($data), $key, $grp );
	}

	function getGroup($grp) {
		return false;
	}

	function getPointer( $key, $grp) {
		return false;
	}
	
	function getData($key, $grp) {
		return unserialize($this->cacheLite->get($key,$grp));
	}

	function invalidateData($key, $grp) {
		$this->cacheLite->remove($key, $grp);
	}

	function invalidateGroup( $grp ) {
		$this->cacheLite->clean($grp);
	}

	function invalidate( ) {
		$this->cacheLite->clean();
	}
}