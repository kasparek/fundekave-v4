<?php
if(isset($_SERVER['HTTP_ACCEPT_ENCODING']))
if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip')!==false) {
	ob_start("ob_gzhandler");
	header('Content-Encoding: gzip');
}
//---host name
$host = $_SERVER['HTTP_HOST'];
$hostArr = explode('.',$host);
$host = $hostArr[0]=='www' ? $hostArr[1] : $hostArr[0];  
if($host=='localhost') $host='fundekave';
//--------------------------------------------------------------class autoloader
function class_autoloader($c) {
	if(strpos($c,'page_')!==false) $c = ROOT . 'page/' . $c ;
	elseif(strpos($c,'FAjax_')!==false) $c = ROOT . 'libs/FAjax/' . $c;
	elseif(strpos($c,'VO')!==false) $c = ROOT . 'libs/vo/'.$c;
	else $c = ROOT . 'libs/' . $c ;
	require( $c . '.php' );
}
spl_autoload_register("class_autoloader");
//--------------------------------------------------------error handler
FError::init(PHPLOG_FILENAME);
//--------------------------------------------------------config + constant init
FConf::getInstance(WEBROOT.'config/'.$host.'.conf.ini');
date_default_timezone_set(FConf::get('internationalization','timezone'));
setlocale(LC_CTYPE, FConf::get('internationalization','setlocale'));
setlocale(LC_COLLATE, FConf::get('internationalization','setlocale'));
if(FConf::get('internationalization','lang')) require(FConf::get('internationalization','lang'));

if(isset($_GET['nonInit'])) $nonInit=true;
if(!isset($nonInit)) {
	//---session settings - stored in db
	ini_set("session.gc_maxlifetime",SESSIONLIFETIME);
	ini_set('session.gc_probability',1);
	if(!is_dir(ROOT_SESSION)) {
		$ff=new FFile();
		$ff->makeDir(ROOT_SESSION);
	}
	ini_set('session.save_path', ROOT_SESSION);
	session_start();
	FProfiler::write('START - session started');
	//startup user
	$user = FUser::getInstance();
	$user->init();
	FProfiler::write('USER - initialized - "'.($user->userVO?$user->userVO->userId:'anonym').'"');
	if(isset($_GET['auth'])) $user->setRemoteAuthToken( FSystem::safeText($_GET['auth']) );
	//initial pageid retrieve
	if(isset($_REQUEST['k'])) $pageId = $_REQUEST['k'];
	//---backward compatibility
	if(isset($_GET['kam'])) {
		$add = ''; if($_GET['kam']>33000) { $add = 'f'; $kam=$_GET['kam']-33000; }
		elseif($_GET['kam']>23000 && $_GET['kam']<33000) { $add = 'g'; $kam=$_GET['kam']-23000; }
		$els=''; for($x=0;$x<(4-strlen($kam));$x++) $els.='l'; 
    if(!empty($kam)) $pageId = $add . $els . $kam;
	}
	//check for item
	$itemId = 0;
	$itemVO = null;
	if(!empty($_REQUEST["i"])) $itemId = (int) $_REQUEST['i'];
	elseif(isset($_REQUEST['nid'])) $itemId = (int) $_REQUEST['nid']; //---backwards compatibility
	if($itemId > 0) {
		$itemVO = new ItemVO($itemId);
		if($itemVO->load()) {
			if(empty($pageId)) $pageId = $itemVO->pageId;
			if($itemVO->itemIdTop > 0) $itemVO = new ItemVO( $itemVO->itemIdTop,true );
		} else $itemVO = null;
	}
	//check category
	if(isset($_REQUEST['c'])) {
		$c = (int) $_REQUEST['c'];
		if($c>0) {
			$catVO = new CategoryVO($c);
			if($catVO->load()) {
				$user->categoryVO = $catVO;
			}
		}
	}

	//recheck pageId
	if(empty($pageId)) $pageId = HOME_PAGE;
	$pageId = FSystem::processK($pageId);
	//setup userVO
	if($itemVO) $user->itemVO = $itemVO;
	$user->pageId = $pageId;
	FProfiler::write('SYSTEM INIT - page params - page='.$pageId.($itemVO?' item='.$itemVO->itemId:''));
	if(isset($_REQUEST['who'])) $user->setWhoIs($_REQUEST['who']);
	//---logout action
	if(isset($_GET['logout']) && $user->userVO->userId>0) {
			FUser::logout($user->userVO->userId);
			FError::add(FLang::$MESSAGE_LOGOUT_OK,1);
			FHTTP::redirect(FSystem::getUri('','',''));
	}
	//map commands
	FCommand::getInstance(); //to load up class and get static constants
	//if(FSystem::isRobot()) FError::write_log("Robot visit - Page:".$user->pageId." Item:".(isset($user->itemVO)?$user->itemVO->itemId:0)." Host".$_SERVER['HTTP_USER_AGENT']);
}