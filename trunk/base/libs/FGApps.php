<?php
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_Photos_UserQuery');
Zend_Loader::loadClass('Zend_Gdata_Photos_AlbumQuery');
Zend_Loader::loadClass('Zend_Gdata_Photos_PhotoQuery');
Zend_Loader::loadClass('Zend_Gdata_App_Extension_Category');

class FGApps {

  var $serviceName = Zend_Gdata_Photos::AUTH_SERVICE_NAME;
  var $user = "kokokonec@gmail.com";
  var $pass = "bnbnbnbn";
  
  private $gp;

  private static $instance;
	private static $allowInstantiation = false;
	
	function __construct() {
		if(self::$allowInstantiation==true) {
      $client = Zend_Gdata_ClientLogin::getHttpClient($this->user, $this->pass, $this->serviceName);
      $this->gp = new Zend_Gdata_Photos($client, "fundekave-galery-0.1");			
		} else {
			throw new Exception('Instantioation denied - SINGLETON - use getinstance.');
		}
	}
	
	static function &getInstance() {
		if (empty(self::$instance)) {
			self::$allowInstantiation = true; 
			self::$instance = new FGApps();
			self::$allowInstantiation = false;
		}
		return self::$instance;
	}
  
  function createAlbum($title,$description) {
    $gp = $this->gp;
    $entry = new Zend_Gdata_Photos_AlbumEntry();
    $entry->setTitle($gp->newTitle($title));
    $entry->setSummary($gp->newSummary($description));
  
    $createdEntry = $gp->insertAlbumEntry($entry);
    return $createdEntry->id;
  }
  
  function getAlbum($id) {
    $gp = $this->gp;
    $query = $gp->newAlbumQuery();
    $query->setUser("default");
    $albumIdArr = explode('/',$id);
    $albumId = array_pop($albumIdArr);
    $query->setAlbumId($albumId);
    $query->setType("entry");
    try {
      return $gp->getAlbumEntry($query);
    } catch (Zend_Gdata_App_Exception $e) {
      return false;
    }
  }    
  
  function createPhoto($albumId,$source,$desc,$title="") {
    $gp = $this->gp;
    $query = $gp->newAlbumQuery();
    $query->setUser("default");
    $albumIdArr = explode('/',$albumId);
    $albumId = array_pop($albumIdArr);
    $query->setAlbumId($albumId);
    $query->setKind("photo");
    $query->setImgMax("1024");
    
    $fd = $gp->newMediaFileSource($source);
    $fd->setContentType("image/jpeg");
     // Create a PhotoEntry
    $photoEntry = $gp->newPhotoEntry();
    $photoEntry->setMediaSource($fd);
    if(!empty($title)) $photoEntry->setTitle($gp->newTitle($title));
    if(!empty($desc)) $photoEntry->setSummary($gp->newSummary($desc));

    $insertedEntry = $gp->insertPhotoEntry($photoEntry, $query->getQueryUrl());
    $mediaContentArray = $insertedEntry->getMediaGroup()->getContent();
    return $mediaContentArray[0]->getUrl();
  } 
}