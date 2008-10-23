<?php
class fUvatar {
	var $id = 1;
	var $gateway = 'index.php';
	var $targetFtp;
	var $targetUrl; 
    var $targetJpg;
    var $refresh;
    var $targetAnimGif;
    var $configXMLFilenam = 'config.xml';
    var $exitAfterUpload = true;
    var $iconUrl = 'img/';
    var $onlineIcon = 'fuvatar_online.png';
    var $offlineIcon = 'fuvatar_offline.png';
    function __construct($paramsArr=array()) {
            if(!empty($paramsArr)) foreach ($paramsArr as $k=>$v) $this->$k = $v;

    }
    function process() {
    	if(isset($_GET['fust'])) {
    		$this->showStatusIcon($_GET['fust']);
	    	if($this->exitAfterUpload == true) exit();
    	}  elseif(isset($_GET['fuco'])) {
            echo $this->getConfig();
            if($this->exitAfterUpload == true) exit();
        } elseif(isset($_POST["futa"])) {
            $type = 'jpg';
            if(isset($_GET['futy'])) $type = $_GET['futy'];
            if ($type=='gif') {
            	if(!empty($this->targetAnimGif)) {
            		$filename = $this->targetFtp . $this->targetAnimGif;
            	   file_put_contents($filename,base64_decode($_POST["futa"]));
            	   @chmod($filename, 0777);
            	} 
            } else {
                if(!empty($this->targetJpg)) {
                	$filename = $this->targetFtp . $this->targetJpg;
                    file_put_contents($filename,base64_decode($_POST["futa"]));
            	   @chmod($filename, 0777);
                }
            }
            if($this->exitAfterUpload == true) exit();
        } elseif(isset($_GET['fuca'])) {
          $this->showImg();
          if($this->exitAfterUpload == true) exit();
        }
    }
    function getConfig() {
        $configXML = file_get_contents('config.xml');
        return $configXML;
    }
    function getImg() {
    	if(file_exists($this->targetFtp . $this->targetJpg)) 
	    	return '<img class="fuvatarimg" src="'.$this->gateway.'?fure='.$this->refresh.'&fuca='.filemtime($this->targetFtp . $this->targetJpg).'" />';
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
    function getStatusIcon() {
	    return '<img class="fuvatarstatus" src="'.$this->gateway.'?fust='.$this->id.'&fure='.$this->refresh.'" />';
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
    function showImg() {
    	header("Content-type: image/jpg") ;
    	if(file_exists($this->targetFtp.$this->targetJpg)) {
    		echo file_get_contents($this->targetFtp.$this->targetJpg);
    	}
    	
    }
}