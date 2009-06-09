<?php
/**
 * FCache - 05/2009
 * 
 * PHP versions  5
 * 
 * Cache tool, support multiple cache methods
 * - memory
 * - session
 * - database
 * - file
 * 
 * @author frantisek.kaspar
 *
 */
class FCache {

	var $loadDriver;
	var $sessionDriver;
	var $databaseDriver;
	var $fileDriver;

	var $activeDriver;
	var $activeId;
	var $activeGroup;
	
	var $defaultGroup = 'default';
	
	private static $instance;

	static function &getInstance($driver='',$lifeTime=-1) {
		if (!isset(self::$instance)) {
			self::$instance = &new FCache();
		}
		
		if( $driver != '') {
			FCache::getDriver($driver);
			if($lifeTime > -1) {
				FCache::setConf($lifeTime);
			} else {
				FCache::setConf(FCache::getLifetimeDefault());
			}
		}
		
		return self::$instance;
	}

	/**
	 * 
	 * DRIVERS INITIALIZATION
	 * 
	 */
	
	function loadConnection() {
		require_once('FCache/LoadDriver.php');
		return new LoadDriver();
	}

	function sessionConnection() {
		if(!isset($_SESSION)) {
			$cache = FCache::getInstance();
			return $cache->dbConnection();
		} else {
			require_once('FCache/SessionDriver.php');
			return new SessionDriver();
		}
	}

	function dbConnection() {
		if(FDBConn::getInstance() === false) {
			$cache = FCache::getInstance();
			return $cache->fileConnection();
		} else {
		 require_once('FCache/DBDriver.php');
		 return new DBDriver();
		}
	}

	function fileConnection() {
		require_once('FCache/FileDriver.php');
		return new FileDriver();
	}

	static function &getDriver($driver) {
		$cache = FCache::getInstance();
		switch($driver) {
			//---in session
			case 's':
			case 'sess':
			case 'session':
				if(!isset($cache->sessionDriver)) {
					$cache->sessionDriver = $cache->sessionConnection();
				}
				$driver = &$cache->sessionDriver;
				break;
				//---in database
			case 'd':
			case 'db':
			case 'database':
				if(!isset($cache->databaseDriver)) {
					$cache->databaseDriver = $cache->dbConnection();
				}
				$driver = &$cache->databaseDriver;
				break;
				//---cache lite
			case 'f':
			case 'file':
				if(!isset($cache->fileDriver)) {
					$cache->fileDriver = $cache->fileConnection();
				}
				$driver = &$cache->fileDriver;
				break;
				//---per load
			case 'load':
			case 'l':
			default:
				if(!isset($cache->loadDriver)) {
					$cache->loadDriver = $cache->loadConnection();
				}
				$driver = &$cache->loadDriver;
				break;
		}
		if(isset($driver)) {
			$cache->activeDriver = $driver;
			return $driver;
		} else {
			return false;
		}
	}

	/**
	 * 
	 * CACHE SETTINGS
	 * 
	 */
	static function setConf( $lifeTime ) {
		$cache = FCache::getInstance();
		$cache->activeDriver->setConf($lifeTime);
	}
	
	static function getLifetimeDefault() {
		$cache = FCache::getInstance();
		return $cache->activeDriver->lifeTimeDefault;
	}
	
	/**
	 * 
	 * CACHE DATA FUNCTIONS 
	 * 
	 */

	function getGroup( $group='default', $driver='' ) {
		if($driver!='') {
			FCache::getDriver($driver);
		}
		return $this->activeDriver->getGroup( $group );
	}

	function getData( $id, $group='default', $driver='' ) {
		if($driver!='') {
			FCache::getDriver($driver);
		}
		$this->activeId = $id;
		$this->activeGroup = $group;
		return $this->activeDriver->getData($this->activeId, $this->activeGroup);

	}

	function setData( $data, $id='', $group='', $driver='', $lifeTime=-1 ) {
		if($driver!='') {
			FCache::getDriver($driver);
		}
		if($lifeTime > -1) {
			$this->activeDriver->setConf($lifeTime);
		}
		if($id=='') {
			$id = $this->activeId;
		}
		if($group=='') {
			$group = $this->activeGroup;
		}
		if(empty($group)) {
			$group = $this->defaultGroup;
		}

		return $this->activeDriver->setData($id, $data, $group);
	}

	function invalidateData(  $id='', $group='default' ) {
		if($id!='') {
			$this->activeDriver->invalidateData($id, $group);
		}
	}

	function invalidateGroup( $group='' ) {
	 if($group!='') {
	 	$this->activeDriver->invalidateGroup( $group );
	 }
	}

	function invalidate() {
		$this->activeDriver->invalidate();
	}

}