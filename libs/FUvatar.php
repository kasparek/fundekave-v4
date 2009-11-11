<?php
class FUvatar {
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
    
    function __construct($fuvatarId='',$paramsArr=array()) {
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
        $user = FUser::getInstance();
         
      if($user->userVO->getXMLVal('webcam','avatar') == 1) {
            //---RESIZE
            $resizeParams = array('quality'=>80,'crop'=>1,'width'=>AVATAR_WIDTH_PX,'height'=>AVATAR_HEIGHT_PX);
            FImgProcess::process($filename,URL_AVATAR . $user->userVO->avatar,$resizeParams);
        }
         
         $interval = (int) $user->getXMLVal('webcam','interval');
         $resolution = (int) $user->getXMLVal('webcam','resolution');
         if(empty($interval)) $interval = 3;
         $lastMod = filemtime($filename);
         FDBTool::query("INSERT INTO sys_users_fuvatar_live (`userId` ,`pageId` ,`refresh` ,`filetime` ,`dateUpdated` ,`resolution`) VALUES ('".$user->gid."', '".$user->currentPageId."', '".$interval."', '".$lastMod."', NOW(), '".$resolution."') on duplicate key update pageId='".$user->currentPageId."',refresh='".$interval."',filetime='".$lastMod."',dateUpdated=now(),resolution='".$resolution."'");
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
        
        //default use 160x120
        $xml->conf->mode->width = $this->resolutions[0]['width'];
        $xml->conf->mode->height = $this->resolutions[0]['height'];
        
        $user = FUser::getInstance();
        $resolution = (int) $user->getXMLVal('webcam','resolution');
        if($resolution > 0) {
            //use 320x240 or else
            $xml->conf->mode->width = $this->resolutions[$resolution]['width'];
            $xml->conf->mode->height = $this->resolutions[$resolution]['height'];
        }
        
        $xml->conf->timeout  = (int) $user->getXMLVal('webcam','interval');
        if(empty($xml->conf->timeout)) $xml->conf->timeout = 3;
        
        $xml->conf->jpegquality = (int) $user->getXMLVal('webcam','quality');
        if(empty($xml->conf->jpegquality)) $xml->conf->jpegquality = 30;
        if($xml->conf->jpegquality > 95) $xml->conf->jpegquality = 95;
        if($xml->conf->jpegquality < 0) $xml->conf->jpegquality = 0;
        
        $xml->conf->activity->monitor = (int) $user->getXMLVal('webcam','motion');
        
        return $xml->asXML();
    }
    
    //---online check function for fuplay.swf
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
    
    //---print functions for swf
    function hasData() {
        return file_exists($this->targetFtp . $this->id . '.jpg');
    }
    /**
     *fuArr - array(id=>fuvatarid,refresh=>intervak timeout,width=>(int),height=>(int)
     **/         
    function getSwf($fuArr=array()) {
      if(empty($fuArr)) $fuArr = array('id'=>$this->id,'refresh'=>$this->refresh,'width'=>$this->width,'height'=>$this->height);
        return '<div class="fuvatarbox"><div id="fuplay'.$fuArr['id'].'" class="fuvatarswf"><img id="fuimg'.$fuArr['id'].'" class="fuvatarimg" src="/fuvatar.php?u='.$fuArr['id'].'&w='.$fuArr['width'].'&h='.$fuArr['height'].'&amp;t='.$fuArr['refresh'].'" /></div></div>';
    }
    
 
    //---get list of online users
    function getLive() {
      //---get list of live
      
        $user = FUser::getInstance();
        
      $arr = FDBTool::getAll("select u.name,fu.refresh,fu.resolution from sys_users_fuvatar_live as fu join sys_users as u on u.userId=fu.userId where fu.pageId='".$user->pageVO->pageId."' and fu.filetime >= ".date("U")."-(fu.refresh*2) ");
      
      $ret = '';
      
      if(!empty($arr)) {
        foreach($arr as $row) {
          $res = $this->resolutions[$row[2]*1];
            $arrSwf = array('id'=>$row[0],'refresh'=>$row[1],'width'=>$res['width'],'height'=>$res['height']);
          $ret .= $this->getSwf($arrSwf);
        }
      }
      
      return $ret.'<hr class="cleaner" />';
      
    }
    
//---fuvatar support functions--------------------------------------
    static function fuvatarAccess($userName) {
        $user = FUser::getInstance();
        $ret = false;
        if($userName == $user->gidname) $ret = true;
        else {
            $row = FDBTool::getRow('select userId,info from sys_users where name="'.$userName.'"');
            if(!empty($row)) {
                $xml = new SimpleXMLElement($row[1]);
                switch ($xml->webcam->public) {
                    case 0:
                        $ret = true;
                        break;
                    case 1:
                        if($user->gid > 0) $ret = true;
                        break;
                    case 2:
                    	$userVO = new UserVO();
                    	$userVO->userId = $row[0];
                        $arr = $userVO->getFriends();
                        if(!empty($arr)) {
                            if(in_array($user->gid,$arr)) $ret = true;
                        }
                        break;
                    case 3:
                        $chosen = $xml->webcam->chosen;
                        if(!empty($chosen)) {
                            $arrChosen = explode(',',$chosen);
                            if(in_array($user->gid,$arrChosen)) $ret = true;
                        }
                        break;
                }
            }
        }
        return $ret;
    }
    
}