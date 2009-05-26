<?php
error_reporting(E_ALL);
//--------------------------------------------------------------class autoloader
function class_autoloader($c) {
	
		if(strpos($c,'page_')!==false) {
			$c = ROOT.ROOT_CODE . $c ;
		} else {
			if(strpos($c,'VO')!==false) {
				$c = 'vo/'.$c;
			}
			$c = LIBSDIR . $c ;
		}
		
		include  $c . '.php';
	
}
spl_autoload_register("class_autoloader");
setlocale(LC_ALL,'cs_CZ.utf-8');

//--------------------------------------------------------config + constant init
FConf::getInstance();

//-------------------------------------------------------------time for debuging
list($usec, $sec) = explode(" ",microtime());
$start = ((float)$usec + (float)$sec);
$cache = FCache::getInstance('l');
$cache->setData($start,'start','debug');
  
//---session settings - stored in db
//require_once("fSession.php");
//session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
ini_set("session.gc_maxlifetime",SESSIONLIFETIME);
ini_set('session.gc_probability',1);
ini_set('session.save_path', ROOT.'tmp/');

session_start();

//---system user init
$user->currentItemId = 0;

	
	$user = FUser::getInstance();

	if(!isset($_POST['m'])) {
	 $user->pageVO = new PageVO();
	 $user->itemVO = new ItemVO();
		$user->pageVO->pageId = HOME_PAGE;
    	
    	$user->pageParam = '';
    	//---backward compatibility
    	if(isset($_GET['kam'])) {
    	    if($_GET['kam']>33000) { $add = 'f'; $kam=$_GET['kam']-33000; }
    	    elseif($_GET['kam']>23000 && $_GET['kam']<33000) { $add = 'g'; $kam=$_GET['kam']-23000; }
    	    $els='';
    	    for($x=0;$x<(4-strlen($kam));$x++) $els.='l';
    	    $_GET['k'] = $user->pageVO->pageId = $add . $els . $kam;
    	}
    	//---u=username
    	if(isset($_GET['u'])) {
    	    $userId = FUser::getUserIdByName($_GET['u']);
    	    if($userId > 0) {
    	    	$userVO = new UserVO();
    	    	$userVO->userId = $userId;
    	    	$userVO->loadVO();
    	        $usersPageId = $userVO->getXMLVal('personal','HomePageId');
    	        if(!empty($usersPageId)) {
    	            $user->pageVO->pageId = (string) $usersPageId;
    	        }
    	    }
    	}
    	if(!empty($_REQUEST["i"])) {
    	    $user->itemVO->itemId = (int) $_REQUEST['i'];
     	    $user->itemVO->checkItem();
    	}
    	
    	if(!empty($_REQUEST["k"])) $user->pageVO->pageId = $_REQUEST['k'];
    	elseif ($user->itemVO->itemId > 0) {
    	   $user->pageVO->pageId = $user->itemVO['pageId'];
    	}
    	if(isset($user->pageVO->pageId{5})) {
    		//---slice pageid on fiveid and params
    		if($user->pageVO->pageId{5}==';') {
    		  $getArr = explode(";",substr($user->pageVO->pageId,5));
    		  foreach ($getArr as $getVar) {
    		    $getVarArr = explode("=",$getVar);
    		    $_GET[$getVarArr[0]] = $getVarArr[1];
          }
        } else {
    		  $user->pageParam = substr($user->pageVO->pageId,5);
    		  $user->pageVO->pageId = substr($user->pageVO->pageId,0,5);
    		}
    	}
    	$user->whoIs = 0;
	     if(isset($_REQUEST['who'])) $user->setWhoIs($_REQUEST['who']);
	} else {
		$user->pageAccess = true;	
	}
	
	$user->kde(); //---check user / load info / load page content / chechk page exist
