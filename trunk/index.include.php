<?php 
require(INIT_FILENAME); 

//---process ajax requests - or alternative POST requests
$user = FUser::getInstance();
if(isset($_REQUEST['m']) && $user->pageAccess == true) {
  FAjax::process($_REQUEST['m'],(isset($_REQUEST['d']))?($_REQUEST['d']):($_POST));
}
FProfiler::profile('FAJAX PROCESSED DONE');
//---process post/get for page
$data = $_POST;
if(!empty($_FILES))  $data['__files'] = $_FILES; 
if(!empty($_GET))  $data['__get'] = $_GET;
FBuildPage::process( $data );
FProfiler::profile('PAGE PROCESSED DONE');
if($user->pageAccess == true) {
	//---page stats counted just if not any redirect
	$user->pageStat();
	//---tag toolbar set up
	if($user->idkontrol === true) {
	  FItemsToolbar::setTagToolbar();
	}
}
FProfiler::profile('PAGE STAT/TOOLBAR');
//---shows message that page is locked
if(($user->pageVO->locked == 2 && $user->userVO->userId != $user->pageVO->userIdOwner) || $user->pageVO->locked == 3)  {
	FError::addError(FLang::$MESSAGE_PAGE_LOCKED);
	if(!FRules::get($user->userVO->userId,'sadmi',1)) $user->pageAccess = false;
}
FProfiler::profile('PAGE BEFORE SHOW');
//---generate page
FBuildPage::show();
FProfiler::profile('PAGE DONE');
//---profiling
FProfiler::profile('END');
FProfiler::profileLog();
FDBTool::profileLog();

//---close resources
session_write_close();
$db = FDBConn::getInstance();
$db->kill();