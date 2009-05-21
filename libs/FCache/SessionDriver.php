<?php
class SessionDriver
{

  private $data;
  public $lifeTimeDefault = 3600;
  private $lifeTime = 3600;
  
  function __construct() {
    $data = &$_SESSION['FCache_data'];
    $data = array();
  }
  
  public function setConf( $lifeTime ) {
    $this->lifeTime = $lifeTime;
  }
  
public function getGroup($group = 'default') {
	if(isset($this->data[$group])) {
  	return $this->data[$group];
	} else return false;
  }
  
  public function setData($id=NULL, $data, $group = 'default') {
    if($id!=NULL) {
      $this->data[$group][$id] = array($this->lifeTime, date("U") , $data);
    } 
  }
  
  public function getData($id, $group = 'default') {
  if(isset($this->data[$group][$id])) {
      if($this->data[$group][$id][0] + $this->data[$group][$id][1] > date("U") || $this->data[$group][$id][0]==0) {
      return $this->data[$group][$id];
    } else {
      $this->invalidateData($id, $group);
    }
  } else {
    return false;
  }
  }
  
  public function invalidateData($id='',$group='default') {
    if(!empty($id)) {
      unset($this->data[$group][$id]);
    } 
  }
  
  public function invalidateGroup( $group='default' ) {
    $this->data[$group] = array();
  }
  
  public function invalidate( ) {
    $this->data = array();
  }

}