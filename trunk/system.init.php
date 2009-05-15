<?php
error_reporting(E_ALL);
//------------------------------------------------------------------------------

if(!isset($nonUserInit)) $nonUserInit = false;
if(!isset($xajax)) $xajax = false;

//-------------------------------------------------------------time for debuging
list($usec, $sec) = explode(" ",microtime());
$start = ((float)$usec + (float)$sec);

//--------------------------------------------------------------class autoloader
function class_autoloader($c) {
	if(!strpos($c,'_')) {
		if(strpos($c,'VO')) {
			$c = 'vo/'.$c;
		}
		$c = $c . '.php';
		include LIBSDIR . $c;
	}
}
spl_autoload_register("class_autoloader");
setlocale(LC_ALL,'cs_CZ.utf-8');

//--------------------------------------------------------config + constant init
FConf::getInstance();
  
//---session settings - stored in db
//require_once("fSession.php");
//session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
ini_set("session.gc_maxlifetime",SESSIONLIFETIME);
ini_set('session.gc_probability',1);
ini_set('session.save_path', ROOT.'tmp/');

//require_once(LIBSDIR.'FUser.php');

session_start();

//---system user init
$user->currentItemId = 0;
if(!$nonUserInit) {
	
	$user = FUser::getInstance();

	if(!$xajax) {
	 $this->page = new PageVO();
		$user->page->pageId = HOME_PAGE;
    	
    	$user->currentPageParam = '';
    	//---backward compatibility
    	if(isset($_GET['kam'])) {
    	    if($_GET['kam']>33000) { $add = 'f'; $kam=$_GET['kam']-33000; }
    	    elseif($_GET['kam']>23000 && $_GET['kam']<33000) { $add = 'g'; $kam=$_GET['kam']-23000; }
    	    $els='';
    	    for($x=0;$x<(4-strlen($kam));$x++) $els.='l';
    	    $_GET['k'] = $user->page->pageId = $add . $els . $kam;
    	}
    	//---u=username
    	if(isset($_GET['u'])) {
    	    $userId = $user->getUserIdByName($_GET['u']);
    	    if($userId > 0) {
    	        $arr  = $user->get($userId);
    	        if(!empty($arr['personal']->HomePageId)) {
    	            $user->page->pageId = (string) $arr['personal']->HomePageId;
    	        }
    	    }
    	}
    	if(!empty($_REQUEST["i"])) {
    	   $user->item = new ItemVO();
    	    $user->item->itemId = (int) $_REQUEST['i'];
     	    $user->item->checkItem();
    	}
    	
    	if(!empty($_REQUEST["k"])) $user->page->pageId = $_REQUEST['k'];
    	elseif ($user->item->itemId > 0) {
    	   $user->page->pageId = $user->currentItem['pageId'];
    	}
    	if(isset($user->page->pageId{5})) {
    		//---slice pageid on fiveid and params
    		if($user->page->pageId{5}==';') {
    		  $getArr = explode(";",substr($user->page->pageId,5));
    		  foreach ($getArr as $getVar) {
    		    $getVarArr = explode("=",$getVar);
    		    $_GET[$getVarArr[0]] = $getVarArr[1];
          }
        } else {
    		  $user->pageParam = substr($user->page->pageId,5);
    		  $user->page->pageId = substr($user->page->pageId,0,5);
    		}
    	}
	}
	
	$user->whoIs = 0;
	if(isset($_REQUEST['who'])) $user->setWhoIs($_REQUEST['who']);
	$user->kde($xajax); //---check user / load info / load page content / chechk page exist
}