<?php
/**
 *TODO: calculate difference between dataUpdate and now - compare with lifetime
 *line  23
 **/ 
class DBDriver
{
  private $lifetime = 0;

  public function setConf( $lifeTime ) {
    $this->lifeTime = $lifeTime;
  }

  public function setData($id=NULL, $data, $group = 'default') {
    $db = FDBConn::getInstance();
    $user = FUser::getInstance();
    if($id!=NULL) {
      $db->query('insert into sys_users_cache (userId,name,value,dateUpdated, lifeTime) values ("'.$user->userVO->userId.'","'.$id.'","'.$data.'",now(),"'.$this->lifeTime.'") on duplicate key update dateUpdated=now(),lifeTime="'.$this->lifeTime.'",value = "'.$data.'"');
    }
  }
  
  public function getData($id, $group = 'default') {
    $db = FDBConn::getInstance();
    $user = FUser::getInstance();
    if($row = $db->getRow("select value, 1, lifeTime from sys_users_cache where userId='".$user->userVO->userId."' and name='".$id."'")) {
      if($row[1]==1 || $row[2]==0) {
        return $row[0];
        } else {
          $this->invalidateData($id);
        }
      }
  }
  
  public function invalidateData($id='') {
    $db = FDBConn::getInstance();
    $user = FUser::getInstance();
    if(!empty($id)) {
      $db->query('delete from sys_users_cache where userId = "'.$user->userVO->userId.'" and name="'.$id.'"');
    } else {
      $db->query('delete from sys_users_cache where userId = "'.$user->userVO->userId.'"');
    }
  }

}