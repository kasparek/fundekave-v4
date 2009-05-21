<?php
class FileDriver
{
  
  private $cacheLite;
  
  //---could be null to live forever
  public $lifeTimeDefault = 60;
  private $lifeTime = 60;
  

  function __construct() {
      require_once('Cache/Lite.php'); 
      if(!is_dir(ROOT.ROOT_CACHE_TEXT)) mkdir(ROOT.ROOT_CACHE_TEXT,0777,true);
      $cacheOptions['cacheDir'] = ROOT.ROOT_CACHE_TEXT;
      $cacheOptions['lifeTime'] = $this->lifeTime;
      $this->cacheLite = new Cache_Lite($cacheOptions);
  }
  
  public function setConf( $lifeTime ) {
    $this->lifeTime = $lifeTime;
    $this->cacheLite->setLifeTime = $lifeTime;
  }
  
  public function setData($id=NULL, $data, $group = 'default') {
    $this->cacheLite->save( $data, $id, $group );
  }
  
	public function getGroup($group = 'default') {
		return false;
	}
  
  public function getData($id, $group = 'default') {
    return $this->cacheLite->get($id,$group);
  }
  
  public function invalidateData($id='',$group='default') {
    if($id!='') {
      $cacheLite->remove($id, $group);
    }
  }
  
  public function invalidateGroup( $group='default' ) {
    $cacheLite->clean($group);
  }
  
  public function invalidate( ) {
    $cacheLite->clean();
  }


}