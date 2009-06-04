<?php
include_once('iPage.php');
class page_PagesBooked implements iPage {

	static function process() {

	}

	static function build() {
		$user = FUser::getInstance();
		
		if($user->whoIs > 0) $addUrl = '&who='.$user->whoIs; else $addUrl = '';
		FSystem::secondaryMenuAddItem($user->getUri($addUrl),FLang::$LABEL_FORUMS,"xajax_forum_booked('forum','".$user->whoIs."');return false;");
		FSystem::secondaryMenuAddItem($user->getUri('t=blog'.$addUrl),FLang::$LABEL_BLOGS,"xajax_forum_booked('blog','".$user->whoIs."');return false;");
		FSystem::secondaryMenuAddItem($user->getUri('t=galery'.$addUrl),FLang::$LABEL_GALERIES,"xajax_forum_booked('galery','".$user->whoIs."');return false;");

		$typeId = $user->pageVO->typeIdChild;
		if(isset($_GET['t'])) $typeId = $_GET['t'];

		if(!FItems::isTypeValid($typeId)) $typeId = FItems::TYPE_DEFAULT;

		$fPages = new FPages($typeId,$user->userVO->userId);
		$data = $fPages->printBookedList();

		FBuildPage::addTab(array("MAINDATA"=>$data));
	}
}