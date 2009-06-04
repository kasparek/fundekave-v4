<?php
/**
 * FCache - 05/2009
 * 
 * PHP versions 4 and 5
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

	function &getInstance($driver='',$lifeTime=-1) {
		static $instance;
		if (!isset($instance)) {
			$instance = &new FCache();
		}
		if($instance->getDriver($driver) === false) {
			return false;
		}
		if($lifeTime > -1) {
			$instance->activeDriver->setConf( $lifeTime );
		} else {
			$instance->activeDriver->setConf( $instance->activeDriver->lifeTimeDefault );
		}
		return $instance;
	}

	function loadConnection() {
		require_once('FCache/LoadDriver.php');
		return new LoadDriver();
	}

	function sessionConnection() {
		if(!isset($_SESSION)) {
			return $this->dbConnection();
		} else {
			require_once('FCache/SessionDriver.php');
			return new SessionDriver();
		}
	}

	function dbConnection() {
		if(FDBConn::getInstance() === false) {
			return $this->fileConnection();
		} else {
		 require_once('FCache/DBDriver.php');
		 return new DBDriver();
		}
	}

	function fileConnection() {
		require_once('FCache/FileDriver.php');
		return new FileDriver();
	}

	function getDriver($driver) {
		$this->activeDriver = null;
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
		if($this->activeDriver) {
			return $this->activeDriver;
		} else {
			return false;
		}
	}

	function setConf( $lifeTime ) {
		$this->activeDriver->setConf($lifeTime);
	}

	function getGroup( $group='default', $driver='' ) {
		if($driver!='') {
			$this->getDriver($driver);
		}
		return $this->activeDriver->getGroup( $group );
	}

	function getData( $id, $group='default', $driver='' ) {
		if($driver!='') {
			$this->getDriver($driver);
		}
		$this->activeId = $id;
		$this->activeGroup = $group;
		return $this->activeDriver->getData($this->activeId, $this->activeGroup);

	}



	function setData( $data, $id='', $group='default', $driver='', $lifeTime=-1 ) {
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