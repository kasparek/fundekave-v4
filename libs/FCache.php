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
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = &new FCache();
		}
		return self::$instance;
	}
	
	public function getDriver($driver) {
    switch($driver) {
      //---in session
      case 's':
      case 'sess':
      case 'session':
        if(!isset($this->sessionDriver)) {
          require_once('FCache/SessionDriver.php');
          $this->sessionDriver = new SessionDriver();
        }
        $this->activeDriver = &$this->sessionDriver;
      break
      //---in database
      case 'd':
      case 'db':
      case 'database':
        if(!isset($this->databaseDriver)) {
          require_once('FCache/DBDriver.php');
          $this->databaseDriver = new DBDriver();
        }
        $this->activeDriver = &$this->databaseDriver;
      break
      //---cache lite
      case 'f':
      case 'file':
        if(!isset($this->fileDriver)) {
          require_once('FCache/FileDriver.php');
          $this->fileDriver = new FileDriver();
        }
        $this->activeDriver = &$this->fileDriver;
      break
      //---per load
      case 'load':
      case 'l':
      default:
        if(!isset($this->loadDriver)) {
          require_once('FCache/FileDriver.php');
          $this->loadDriver = new FileDriver();
        }
        $this->activeDriver = &$this->loadDriver;
      break;
    }
    return $this->activeDriver;
  }
	
	public function getData($driver, $id, $group='default' ) {
    if($driver!='') {
      $this->getDriver($driver);
    }
    $this->activeId = $id;
    $this->activeGroup = $group;
    
    $this->activeDriver->getData($this->activeId, $this->activeGroup);
    
  }
  
  public function setData($data, $driver='', $id='', $group=NULL, $lifeTime=-1) {
    if($driver!='') {
      $this->getDriver($driver);
    }
    if($lifeTime > -1) {
      $this->activeDriver->setConf($lifeTime);
    }
    if($id=='') {
      $id = $this->activeId;
    }
    if($group!=NULL) {
      $this->activeGroup = $group;
    }
    if(!empty($id) {
      $this->activeDriver->setData($id, $data, $this->activeGroup);
    }
  }
	

}