<?php
class SessionDriver
{

  private $data;
  private $lifeTime = 60;
  
  function __construct() {
    $data = &$_SESSION['FCache_data'];
    $data = array();
  }
  
  public function setConf( $lifeTime ) {
    $this->lifeTime = $lifeTime;
  }
  
  public function setData($id=NULL, $data, $group = 'default') {
    if($id!=NULL) {
      $this->data[$id] = array($this->lifeTime, date("U") , $data);
    } 
  }
  
  public function getData($id, $group = 'default') {
  if(isset($this->data[$id])) {
      if($this->data[$id][0] + $this->data[$id][1] > date("U") || $this->data[$id][0]==0) {
      return $this->data[$id];
    } else {
      $this->invalidateData($id);
    }
  }
  
  public function invalidateData($id='') {
    if(!empty($id)) {
      unset($this->data[$id])
    } else {
      $this->data = array();
    }
  }

}