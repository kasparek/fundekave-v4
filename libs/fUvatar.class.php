<?php
class fUvatar {
  private $configTemplateUrl;

	var $id;
	var $gateway = 'index.php';
 
	var $targetFtp;
	var $targetUrl; 
    var $targetJpg;

    //needed for player
    var $resolution;
    var $resolutions = array(
    0=>array('width'=>160,'height'=>120),
    1=>array('width'=>320,'height'=>240),
    );
    var $refresh;
    var $width;
    var $height;
    
    var $targetAnimGif;
    var $configXMLFilenam = 'config.xml';
    var $exitAfterUpload = true;
    var $iconUrl = 'img/';
    var $onlineIcon = 'fuvatar_online.png';
    var $offlineIcon = 'fuvatar_offline.png';
    function __construct($fuvatarId,$paramsArr=array()) {
        $this->id = $fuvatarId;
        if(!empty($paramsArr)) foreach ($paramsArr as $k=>$v) $this->$k = $v;
        if(empty($this->width)) {
            $res = $this->resolutions[$this->resolution*1];
            $this->width = $res['width'];
            $this->height = $res['height'];
        }
    }
    
    function upload($imageData) {
      if(!empty($this->targetJpg)) {
         	$filename = $this->targetFtp . $this->targetJpg;
          file_put_contents($filename,base64_decode($imageData));
         @chmod($filename, 0777);
         //---call to change avatar if auto change is set to true
         global $user;
         $user->updateAvatarFromWebcam($filename);
       }
    }
    
    function download($username) {
      if(!empty($this->targetFtp)) {
         	$filename = $this->targetFtp . $username . '.jpg';
         	if(file_exists($filename)) {
            return file_get_contents($filename);
          }
       }
    }
        
    function getConfig() {
        $configXML = file_get_contents($this->configTemplateUrl);
        $xml = new SimpleXMLElement($configXML);
        //---change base config by user settings
        global $user;
        //default use 160x120
        $xml->conf->mode->width = $this->resolutions[0]['width'];
        $xml->conf->mode->height = $this->resolutions[0]['height'];

        $resolution = (int) $user->getXMLVal('webcam','resolution');
        if($resolution > 0) {
            //use 320x240 or else
            $xml->conf->mode->width = $this->resolutions[$resolution]['width'];
            $xml->conf->mode->height = $this->resolutions[$resolution]['height'];
        }
        
        $xml->conf->timeout  = (int) $user->getXMLVal('webcam','interval');
        
        $xml->conf->jpegquality = (int) $user->getXMLVal('webcam','quality');
        if($xml->conf->jpegquality > 95) $xml->conf->jpegquality = 95;
        if($xml->conf->jpegquality < 0) $xml->conf->jpegquality = 0;
        
        $xml->conf->activity->monitor = (int) $user->getXMLVal('webcam','motion');
        
        
        return $xml->asXML();
    }
    
    function check($username) {
        $filename = $this->targetFtp . $username . '.jpg';
        if(file_exists($filename)) {
		    $lastMod = filemtime($filename);
		    $dateLast = date("Y-m-d H:i:s",$lastMod);
		}
		else {
		    $lastMod = 0;
		    $dateLast = '';
		}
		echo '<fuplay><last>'.$dateLast.'</last><timestamp>'.$lastMod.'</timestamp><now>'.date('U').'</now></fuplay>';
    }
    
    
    
    function isOnline() {
    	$ret = false;
    	if(file_exists($this->targetFtp . $this->targetJpg)) {
     		$lastMod = filemtime($this->targetFtp . $this->targetJpg);
    		$now = date("U");
    		if(($now - $lastMod) < (($this->refresh/1000)*2)) {
    			$ret = true;
    		}
    	}
    	return $ret;
    }
    
   
    function hasData() {
        return file_exists($this->targetFtp . $this->id . '.jpg');
    }
    function getSwf() {
        return '<div id="fuplay'.$this->id.'" class="fuvatarswf"><img id="fuimg'.$this->id.'" class="fuvatarimg" src="/fuvatar.php?u='.$this->id.'&w='.$this->width.'&h='.$this->height.'&t='.$this->refresh.'" /></div>';
    }
    
    
    function showStatusIcon($fuvatarId) {
    	header("Content-type: image/png") ;
    	if($this->isOnline()==true) {
    		$filename = $this->iconUrl.$this->onlineIcon;
    	} else {
	    	$filename =  $this->iconUrl.$this->offlineIcon;
    	}
    	echo file_get_contents($filename);
    }
     function getStatusIcon() {
	    return '<img class="fuvatarstatus" src="'.$this->gateway.'?fust='.$this->id.'&fure='.$this->refresh.'" />';
    }
    
}