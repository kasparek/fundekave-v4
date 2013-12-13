<?php
require(ROOT.'system.init.php');
/*** CRON JOBS **/
if(isset($_GET['cron'])) {
	require_once(ROOT.'cron/'.$_GET['cron'].'.php');
	exit;
}
/*** HEADERS PROCESSING **/
if(isset($_GET['header_handler'])) {
	require(ROOT.'header.handler.php');
}
/*** FILES UPLOAD PROCESSING **/
if(strpos($_SERVER['REQUEST_URI'],"/files/")===0 || strpos($_SERVER['REQUEST_URI'],"/files.php")!==false) {
	require(ROOT.'files.upload.php');
}

/*** MAIN PAGE PROCESSING **/
if(isset($_GET['authCheck'])) {
	$user->kde(); //---check user / load info / load page content / chechk page exist
	if($user->idkontrol===true) echo '1'; else echo '0';
	FSystem::fin();
}

/*** BUILD RSS **/
if(strpos($_SERVER['REQUEST_URI'],"/rss")!==false || strpos($_SERVER['REQUEST_URI'],"/frss.php")!==false || !empty($_GET['rss'])) {
	$user->kde();
	FRSS::process($_GET);
	FRSS::build($_GET);
	FSystem::fin();
}

//---process ajax requests - or alternative POST requests
if(isset($_REQUEST['m'])) {
	if(isset($_REQUEST['d'])) $data = $_REQUEST['d']; //simple link handling
	else if(strpos($_REQUEST['m'],'-x')!==false) {
		if(!empty($HTTP_RAW_POST_DATA)) $data = $HTTP_RAW_POST_DATA;
		else FError::add("NO RAW_POST DATA ".$_SERVER['REQUEST_URI']);
	}
	if(empty($data)) $data = $_POST; //handling post if not ajax
	$options = array();
	if(!empty($_FILES)) $options['data']['__files'] = $_FILES;
	if(!empty($_GET)) $options['data']['__get'] = $_GET;
	FAjax::prepare($_REQUEST['m'], $data, $options);
	$fajax = FAjax::getInstance();
	if(!empty($fajax->data['i'])) {
		$itemVO = new ItemVO($fajax->data['i'],false);
		if($itemVO->load()) $user->itemVO = $itemVO; else $itemVO=null;
	}
	$user->kde(); //---check user / load info / load page content / chechk page exist
	if($itemVO) $user->itemVO->prepare(); //need to be done after user initialization
	FAjax::process($_REQUEST['m'], $data, $options);
} else {
	$data = $_POST;
	$data['__ajaxResponse'] = false;
	if(!empty($_FILES)) $data['__files'] = $_FILES;
	if(!empty($_GET)) $data['__get'] = $_GET;
	$data = FAjax::preprocessPost($data);
	$user->kde();
}

//---build page
if($itemVO) $user->itemVO->prepare(); //need to be done after user initialization
FBuildPage::process( $data );
FProfiler::write('PAGE PROCESS DONE');

//increment hit for items
if($user->itemVO && empty($user->pageParam) && (!$user->idkontrol || $itemVO->userId != $user->userVO->userId)) $itemVO->hit();
	
//---shows message that page is locked
if($user->pageVO && ($user->pageVO->locked == 2 && $user->userVO->userId != $user->pageVO->userIdOwner) || $user->pageVO->locked == 3) {
	FError::add(FLang::$MESSAGE_PAGE_LOCKED);
	if(!FRules::get($user->userVO->userId,'sadmi',1)) $user->pageAccess = false;
}

//---generate page
$html = FBuildPage::show( $data );

header("Content-Type: text/html; charset=".FConf::get('internationalization','charset'));
if(!isset($_GET['nooutput'])) echo $html;
else echo strlen($html).'Bytes produced';
	
//---profiling
FProfiler::write('PAGE COMPLETE');
//---close connections
FSystem::fin();
