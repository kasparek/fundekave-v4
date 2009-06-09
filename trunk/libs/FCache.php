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
	var $driverIdent;
	var $driver;
	var $key;
	var $grp;

	var $defaultGrp = 'default';

	static function &getInstance($driver='',$lifeTime=-1) {
		$cache = &new FCache();
		$cache->driverIdent = $driver;
		if( $driver != '') {
			$cache->getDriver($driver);
			if($lifeTime > -1) {
				$cache->setConf($lifeTime);
			} else {
				$cache->setConf($cache->getLifetimeDefault());
			}
		}

		return $cache;
	}

	/**
	 *
	 * DRIVERS INITIALIZATION
	 *
	 */

	function loadConnection() {
		require_once('FCache/LoadDriver.php');
		return LoadDriver::getInstance();
	}

	function sessionConnection() {
		if(!isset($_SESSION)) {
			return $this->dbConnection();
		} else {
			require_once('FCache/SessionDriver.php');
			return SessionDriver::getInstance();
		}
	}

	function dbConnection() {
		if(FDBConn::getInstance() === false) {
			return $this->fileConnection();
		} else {
		 require_once('FCache/DBDriver.php');
		 return DBDriver::getInstance();
		}
	}

	function fileConnection() {
		require_once('FCache/FileDriver.php');
		return FileDriver::getInstance();
	}

	function &getDriver($driver) {
		switch($driver) {
			//---in session
			case 's':
			case 'sess':
			case 'session':
				$this->driver = $this->sessionConnection();
				break;
				//---in database
			case 'd':
			case 'db':
			case 'database':
				$this->driver = $this->dbConnection();
				break;
				//---cache lite
			case 'f':
			case 'file':
				$this->driver = $this->fileConnection();
				break;
				//---per load
			case 'load':
			case 'l':
			default:
				$this->driver = $this->loadConnection();
				break;
		}
		if( $this->driver ) {
			return $this->driver;
		} else {
			return false;
		}
	}

	/**
	 *
	 * CACHE SETTINGS
	 *
	 */
	function setConf( $lifeTime ) {
		$this->driver->setConf($lifeTime);
	}

	function getLifetimeDefault() {
		return $this->driver->lifeTimeDefault;
	}

	/**
	 *
	 * CACHE DATA FUNCTIONS
	 *
	 */

	function getGroup( $group='default', $driver='' ) {
		if($driver!='') {
			$this->getDriver($driver);
		}
		if(!$this->driver) return false;
		return $this->driver->getGroup( $group );
	}

	function getData( $id, $group='default', $driver='' ) {
		if($driver!='') {
			$this->getDriver($driver);
		}
		if(!$this->driver) return false;
		$this->key = $id;
		$this->grp = $group;
		return $this->driver->getData($this->key, $this->grp);

	}

	function setData( $data, $id='', $group='', $driver='', $lifeTime=-1 ) {
		if($driver!='') {
			$this->getDriver($driver);
		}
		if(!$this->driver) return false;
		
		if($lifeTime > -1) {
			$this->driver->setConf($lifeTime);
		}
		if($id=='') {
			$id = $this->key;
		}
		if($group=='') {
			$group = $this->grp;
		}
		if(empty($group)) {
			$group = $this->grp;
		}

		return $this->driver->setData($id, $data, $group);
	}

	function invalidateData(  $id='', $group='default' ) {
		if($id!='') {
			$this->driver->invalidateData($id, $group);
		}
	}

	function invalidateGroup( $group='' ) {
		if($group!='') {
			$this->driver->invalidateGroup( $group );
		}
	}

	function invalidate() {
		$this->driver->invalidate();
	}

}