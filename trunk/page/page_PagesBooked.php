<?php
include_once('iPage.php');
class page_PagesBooked implements iPage {

	static function process($data) {

	}

	static function build() {
		$user = FUser::getInstance();
		
		if($user->whoIs > 0) $addUrl = '&who='.$user->whoIs; else $addUrl = '';
		FSystem::secondaryMenuAddItem(FUser::getUri($addUrl),FLang::$LABEL_FORUMS);
		FSystem::secondaryMenuAddItem(FUser::getUri('t=blog'.$addUrl),FLang::$LABEL_BLOGS);
		FSystem::secondaryMenuAddItem(FUser::getUri('t=galery'.$addUrl),FLang::$LABEL_GALERIES);

		$typeId = $user->pageVO->typeIdChild;
		if(isset($_GET['t'])) $typeId = $_GET['t'];

		if(!FItems::isTypeValid($typeId)) $typeId = FItems::TYPE_DEFAULT;

		$fPages = new FPages($typeId,$user->userVO->userId);
		$data = $fPages->printBookedList();

		FBuildPage::addTab(array("MAINDATA"=>$data));
	}
}