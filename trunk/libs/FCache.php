<?php
class FCache {

	/**
	 *types of cache
	 *
	 *load
	 *session
	 *database
	 *file
	 *
	 **/
	private $loadDriver;
	private $sessionDriver;
	private $databaseDriver;
	private $fileDriver;

	private $activeDriver;
	private $activeId;
	private $activeGroup;

	private static $instance;
	static function &getInstance($driver='',$lifeTime=-1) {
		if (!isset(self::$instance)) {
			self::$instance = &new FCache();
		}
		if($driver!='') {
			self::$instance->getDriver($driver);
		}
		if($lifeTime > -1) {
			self::$instance->activeDriver->setConf($lifeTime);
		} else {
			self::$instance->activeDriver->setConf(self::$instance->activeDriver->lifeTimeDefault);
		}
		return self::$instance;
	}

	private function loadConnection() {
		require_once('FCache/LoadDriver.php');
		return new LoadDriver();
	}

	private function sessionConnection() {
		if(!isset($_SESSION)) {
			return $this->dbConnection();
		} else {
			require_once('FCache/SessionDriver.php');
			return new SessionDriver();
		}
	}

	private function dbConnection() {
		if(FDBConn::getInstance() === false) {
			return $this->fileConnection();
		} else {
		 require_once('FCache/DBDriver.php');
		 return new DBDriver();
		}
	}

	private function fileConnection() {
		require_once('FCache/FileDriver.php');
		return new FileDriver();
	}

	public function getDriver($driver) {
		switch($driver) {
			//---in session
			case 's':
			case 'sess':
			case 'session':
				if(!isset($this->sessionDriver)) {
					$this->sessionDriver = $this->sessionConnection();
				}
				$this->activeDriver = &$this->sessionDriver;
				break;
				//---in database
			case 'd':
			case 'db':
			case 'database':
				if(!isset($this->databaseDriver)) {
					$this->databaseDriver = $this->dbConnection();
				}
				$this->activeDriver = &$this->databaseDriver;
				break;
				//---cache lite
			case 'f':
			case 'file':
				if(!isset($this->fileDriver)) {
					$this->fileDriver = $this->fileConnection();
				}
				$this->activeDriver = &$this->fileDriver;
				break;
				//---per load
			case 'load':
			case 'l':
			default:
				if(!isset($this->loadDriver)) {
					$this->loadDriver = $this->loadConnection();
				}
				$this->activeDriver = &$this->loadDriver;
				break;
		}
		return $this->activeDriver;
	}

	public function setConf( $lifeTime ) {
		$this->activeDriver->setConf($lifeTime);
	}

	public function getGroup( $group='default', $driver='' ) {
		if($driver!='') {
			$this->getDriver($driver);
		}
		return $this->activeDriver->getGroup( $group );
	}

	public function getData( $id, $group='default', $driver='' ) {
		if($driver!='') {
			$this->getDriver($driver);
		}
		$this->activeId = $id;
		$this->activeGroup = $group;
		return $this->activeDriver->getData($this->activeId, $this->activeGroup);

	}



	public function setData( $data, $id='', $group='default', $driver='', $lifeTime=-1 ) {
		if($driver!='') {
			$this->getDriver($driver);
		}
		if($lifeTime > -1) {
			$this->activeDriver->setConf($lifeTime);
		}
		if($id=='') {
			$id = $this->activeId;
		}
		$this->activeGroup = $group;

		if(!empty($id)) {
			$this->activeDriver->setData($id, $data, $this->activeGroup);
		}
	}

	public function invalidateData(  $id='', $group='default' ) {
		if($id!='') {
			$this->activeDriver->invalidateData($id, $group);
		}
	}

	public function invalidateGroup( $group='' ) {
	 if($group!='') {
	 	$this->activeDriver->invalidateGroup( $group );
	 }
	}

	public function invalidate() {
		$this->activeDriver->invalidate();
	}

}