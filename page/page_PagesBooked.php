<?php
include_once('iPage.php');
class page_PagesBooked implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		
		if($user->whoIs > 0) $addUrl = '&who='.$user->whoIs; else $addUrl = '';
		FMenu::secondaryMenuAddItem(FSystem::getUri($addUrl),FLang::$LABEL_FORUMS);
		FMenu::secondaryMenuAddItem(FSystem::getUri('t=blog'.$addUrl),FLang::$LABEL_BLOGS);
		FMenu::secondaryMenuAddItem(FSystem::getUri('t=galery'.$addUrl),FLang::$LABEL_GALERIES);

		$typeId = $user->pageVO->typeIdChild;
		if(isset($_GET['t'])) $typeId = $_GET['t'];

		if(!FItems::isTypeValid($typeId)) $typeId = FItems::TYPE_DEFAULT;

		$fPages = new FPages($typeId,$user->userVO->userId);
		$data = $fPages->printBookedList();

		FBuildPage::addTab(array("MAINDATA"=>$data));
	}
}