<?php 
require(INIT_FILENAME); 

$user = FUser::getInstance();
if(isset($_POST['m']) && $user->pageAccess == true) {
  FAjax::process($_POST['m'],$_POST['data']);
}

//----DEBUG
if(isset($_GET['d'])) {
    print_r($user->pageVO);
    //print_r($_SESSION);
    die(); 
    fSystem::profile('START:'); 
}

if(isset($_GET['t'])) {
  //tag item
  $tagItem = $_GET['t'] * 1;
  if($tagItem > 0) fItems::tag($tagItem,$user->userVO->userId);
}
if(isset($_GET['rt'])) {
  //remove tag item
  $tagItem = $_GET['rt'] * 1;
  if($tagItem > 0) fItems::removeTag($tagItem,$user->userVO->userId);
  fHTTP::redirect($user->getUri());
}

if(isset($_REQUEST['book'])) fForum::setBooked($user->pageVO->pageID,$user->userVO->userId,1);
if(isset($_REQUEST['unbook'])) fForum::setBooked($user->pageVO->pageID,$user->userVO->userId,0);

if($user->idkontrol) {
  fXajax::register('user_switchFriend');
  fXajax::register('user_tag');
  fXajax::register('fcalendar_monthSwitch');
  fXajax::register('draft_save');
  fXajax::register('poll_pollVote');
  fXajax::register('forum_fotoDetail');
  fXajax::register('pocket_add');
  fXajax::register('pocket_action');
  fXajax::register('forum_booked');
  //post page
  $reqSetRecipient = fXajax::register('post_setRecipientAvatarFromBooked');
  $reqSetRecipient->setParameter(0, XAJAX_INPUT_VALUE, 'prokoho_book');
  $reqSetRecipientFromInput = fXajax::register('post_setRecipientAvatarFromInput');
  $reqSetRecipientFromInput->setParameter(0, XAJAX_INPUT_VALUE, 'prokoho');
  //items
  fXajax::register('user_tag');
  
  fXajax::register('forum_auditBook');
  //forum
  fXajax::register('forum_toolbar');
  //blog
  fXajax::register('blog_blogEdit');
  fXajax::register('blog_processFormBloged');
  
  fItems::setTagToolbar();
}

if(($user->pageVO->locked==2 && $user->userVO->userId != $user->pageVO->userIdOwner) || $user->pageVO->locked==3)  {
	fError::addError(MESSAGE_PAGE_LOCKED);
	if(!fRules::get($user->userVO->userId,'sadmi',1)) $user->pageAccess = false;
}
//---process post/get
//TODO: do this as soon as possible, usually there is redirect
FBuildPage::process();
//----------------	generate page	----------------------------------------
FBuildPage::show();

session_write_close();
$db = FDBConn::getInstance();
$db->disconnect();