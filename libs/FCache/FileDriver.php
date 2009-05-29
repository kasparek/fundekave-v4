<?php
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
		require_once('Cache/Lite.php');
		if(!is_dir(ROOT.ROOT_CACHE_TEXT)) mkdir(ROOT.ROOT_CACHE_TEXT,0777,true);
		$cacheOptions['cacheDir'] = ROOT.ROOT_CACHE_TEXT;
		$cacheOptions['lifeTime'] = $this->lifeTime;
		$this->cacheLite = new Cache_Lite($cacheOptions);
	}

	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
		$this->cacheLite->setLifeTime = $lifeTime;
	}

	function setData($id, $data, $group = 'default') {
		$this->cacheLite->save( serialize($data), $id, $group );
	}

	function getGroup($group = 'default') {
		return false;
	}

	function getData($id, $group = 'default') {
		return unserialize($this->cacheLite->get($id,$group));
	}

	function invalidateData($id='',$group='default') {
		if($id!='') {
			$this->cacheLite->remove($id, $group);
		}
	}

	function invalidateGroup( $group='default' ) {
		$this->cacheLite->clean($group);
	}

	function invalidate( ) {
		$this->cacheLite->clean();
	}
}