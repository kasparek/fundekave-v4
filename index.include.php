<?php 
require(INIT_FILENAME); 

//---process ajax requests - or alternative POST requests
$user = FUser::getInstance();
if(isset($_REQUEST['m']) && $user->pageAccess == true) {
  FAjax::process($_REQUEST['m'],(isset($_REQUEST['d']))?($_REQUEST['d']):($_POST));
}

//---process post/get for page
//TODO: do this as soon as possible, usually there is redirect
$data = $_POST;
if(!empty($_FILES))  $data['__files'] = $_FILES; 
if(!empty($_GET))  $data['__get'] = $_GET;
FBuildPage::process( $data );

//----DEBUG
if(isset($_GET['d'])) {
    print_r($user->pageVO);
    die(); 
    FSystem::profile('START:'); 
}

if($user->idkontrol) {
  FItemsToolbar::setTagToolbar();
}

//---shows message that page is locked
if(($user->pageVO->locked == 2 && $user->userVO->userId != $user->pageVO->userIdOwner) || $user->pageVO->locked == 3)  {
	FError::addError(FLang::$MESSAGE_PAGE_LOCKED);
	if(!FRules::get($user->userVO->userId,'sadmi',1)) $user->pageAccess = false;
}

//---generate page
FBuildPage::show();

//---close resources
session_write_close();
$db = FDBConn::getInstance();
$db->disconnect();