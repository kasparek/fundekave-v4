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
	
	const DEFAULT_GRP = 'default';
	
	public $debug = 0;
	
	private $driver = null;
	private $key;
	private $grp = self::DEFAULT_GRP;

	static function &getInstance($driverIdent='',$lifeTime=-1) {
		$cache = new FCache();
		if( $driverIdent != '') {
			if(false !== $cache->getDriver( $driverIdent )) {
				if($lifeTime > -1) {
					$cache->setConf($lifeTime);
				} else {
					$cache->setConf($cache->getLifetimeDefault());
				}
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

	/**
	 * 
	 * @param $driver String - identifier of driver
	 * @return instance of driver
	 */
	function &getDriver( $driverIdent='' ) {
		if(!empty($driverIdent)) {
			switch($driverIdent) {
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
		}
		if(null === $this->driver) {
			if($this->debug == 1) echo 'FCache::invalid driver::('.$driverIdent.')';
		}
		return $this->driver;
	}

	/**
	 *
	 * CACHE SETTINGS
	 *
	 */
	function setConf( $lifeTime ) {
		if($lifeTime > -1) {
			$this->driver->setConf($lifeTime);
		}
	}

	function getLifetimeDefault() {
		return $this->driver->lifeTimeDefault;
	}
	
	function checkKey( $key ) {
		if(empty($key)) {
			$key = $this->key;
		}
		if(empty($key)) {
			if($this->debug == 1) echo 'FCache::invalid key';
			return false;	
		}
		return $key;
	}
	function checkGrp( $grp ) {
		if(empty($grp)) {
			$grp = $this->grp;
		}
		if(empty($grp)) {
			if($this->debug == 1) echo 'FCache::invalid group';
			return false;	
		}
		return $grp;
	}

	/**
	 *
	 * CACHE DATA FUNCTIONS
	 *
	 */

	function getGroup( $grp = self::DEFAULT_GRP, $driverIdent='' ) {
		if(null === ($driver = $this->getDriver( $driverIdent ))) return false;
		return $driver->getGroup( $grp );
	}

	function getData( $key , $grp = self::DEFAULT_GRP, $driverIdent='' ) {
		if(null === ($driver = $this->getDriver($driverIdent))) return false;
		if($this->checkKey($key) === false) return false;
		if($this->checkGrp($grp) === false) return false;
		$this->key = $key;
		$this->grp = $grp;
		return $driver->getData($key, $grp);
	}

	function setData( $data, $key='', $grp='', $driverIdent='', $lifeTime=-1 ) {
		if(null === ($driver = $this->getDriver($driverIdent))) return false;
		$this->setConf($lifeTime);
		if(($key = $this->checkKey($key)) === false) return false;
		if(($grp = $this->checkGrp($grp)) === false) return false;
		return $driver->setData( $key, $data, $grp );
	}

	function invalidateData(  $key = '', $grp = self::DEFAULT_GRP ) {
		if(null === ($driver = $this->getDriver())) return false;
		if($this->checkKey($key) === false) return false;
		if($this->checkGrp($grp) === false) return false;
		$driver->invalidateData($key, $grp);
	}

	function invalidateGroup( $grp='' ) {
		if(null === ($driver = $this->getDriver())) return false;
		if($this->checkGrp($grp) === false) return false;
		$driver->invalidateGroup( $grp );
	}

	function invalidate() {
		if(null === ($driver = $this->getDriver())) return false;
		$driver->invalidate();
	}

}