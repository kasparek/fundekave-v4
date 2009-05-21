<?php
class LoadDriver
{

  private $data;
  
  public $lifeTimeDefault = 0;
  private $lifeTime = 0;
  
  public function setConf( $lifeTime ) {
    
  }
  
  public function setData($id=NULL, $data, $group = 'default') {
    if($id!=NULL) {
      $this->data[$group][$id] = $data;
    } 
  }
  
  public function getData($id, $group = 'default') {
    if(isset($this->data[$group][$id])) {
      return $this->data[$group][$id];
    } else return false;
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