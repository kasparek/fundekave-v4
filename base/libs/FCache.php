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
	
	const SERIALIZE_NONE = 'serialNone';
	const SERIALIZE_STD = 'serialSTD';
	const SERIALIZE_JSON = 'serialJSON';
	
	public $debug = 0;
	
	private $driver = null;
	private $key;
	private $grp = self::DEFAULT_GRP;
	private $serializeType = self::SERIALIZE_STD; //none,std,json

	static function &getInstance($driverIdent='',$lifeTime=-1,$serializeType=self::SERIALIZE_STD) {
		if( $driverIdent == '') $driverIdent='f';
		$cache = new FCache();
		$cache->serializeType = $serializeType;
		$cache->getDriver( $driverIdent );
		if(!$cache->driver) return false;
		$cache->setConf($lifeTime > -1 ? $lifeTime : $cache->getLifetimeDefault());
		return $cache;
	}

	/**
	 *
	 * DRIVERS INITIALIZATION
	 *
	 */

	function loadConnection() {
		require_once('FCache/LoadDriver.php');
		return LoadDriver::getInstance($this);
	}

	function sessionConnection() {
		if(!isset($_SESSION)) {
			return $this->dbConnection();
		} else {
			require_once('FCache/SessionDriver.php');
			return SessionDriver::getInstance($this);
		}
	}

	function dbConnection() {
		if(FDBConn::getInstance() === false) {
			return $this->fileConnection();
		} else {
			require_once('FCache/DBDriver.php');
			return DBDriver::getInstance($this);
		}
	}

	function fileConnection() {
		require_once('FCache/FileDriver.php');
		return FileDriver::getInstance($this);
	}

	/**
	 * 
	 * @param $driver String - identifier of driver
	 * @return instance of driver
	 */
	function getDriver( $driverIdent='' ) {
		if(!empty($driverIdent)) {
			switch($driverIdent) {
				//---for testing - no caching - always return false
				case 'v':
				case 'void':
				case 'debug':
					require_once('FCache/VoidDriver.php');
					$this->driver = VoidDriver::getInstance($this);
					break;
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
			FError::write_log( 'FCache::invalid driver::('.$driverIdent.')' );
			return false;
		}
		return true;
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
	
	function setSerialize( $type ) {
		$this->serialize = $type;
	}
	
	function serialize( $data ) {
		if($this->serializeType==self::SERIALIZE_NONE) return $data;
		if($this->serializeType==self::SERIALIZE_JSON) return json_encode($data);
		return serialize($data);
	}
	
	function unserialize( $data ) {
		if($this->serializeType==self::SERIALIZE_NONE) return $data;
		if($this->serializeType==self::SERIALIZE_JSON) return json_decode($data);
		return unserialize($data);
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
		if(!$this->driver) return false;
		return $this->driver->getGroup( $grp );
	}

	function &getPointer( $key , $grp = self::DEFAULT_GRP, $driverIdent='' ) {
		if(!$this->driver) return false;
		if($this->checkKey($key) === false) return false;
		if($this->checkGrp($grp) === false) return false;
		$this->key = $key;
		$this->grp = $grp;
		return $this->driver->getPointer($key, $grp);
	}
	
	
	function getData( $key , $grp = self::DEFAULT_GRP, $driverIdent='' ) {
		if(!$this->driver) return false;
		if($this->checkKey($key) === false) return false;
		if($this->checkGrp($grp) === false) return false;
		$this->key = $key;
		$this->grp = $grp;
		return $this->driver->getData($key, $grp);
	}

	function setData( $data, $key='', $grp='', $driverIdent='', $lifeTime=-1 ) {
		if(!$this->driver) return false;
		$this->setConf($lifeTime);
		if(($key = $this->checkKey($key)) === false) return false;
		if(($grp = $this->checkGrp($grp)) === false) return false;
		return $this->driver->setData( $key, $data, $grp );
	}

	function invalidateData(  $key = '', $grp = self::DEFAULT_GRP ) {
		if(!$this->driver) return false;
		if($this->checkKey($key) === false) return false;
		if($this->checkGrp($grp) === false) return false;
		$this->driver->invalidateData($key, $grp);
	}

	function invalidateGroup( $grp='' ) {
		if(!$this->driver) return false;
		if($this->checkGrp($grp) === false) return false;
		$this->driver->invalidateGroup( $grp );
	}

	function invalidate() {
		if(!$this->driver) return false;
		$this->driver->invalidate();
	}

}