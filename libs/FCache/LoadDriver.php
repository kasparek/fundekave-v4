<?php
class LoadDriver
{

  private $data;
  
  public function setConf( $lifeTime ) {
    
  }
  
  public function setData($id=NULL, $data, $group = 'default') {
    if($id!=NULL) {
      $this->data[$id] = $data;
    } 
  }
  
  public function getData($id, $group = 'default') {
    if(isset($this->data[$id])) {
      return $this->data[$id];
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