<?php
include_once('iPage.php');
class page_PagesBooked implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;

		$fPages = new FPages(array('forum','blog'),$user->userVO->userId);
		$data = $fPages->printBookedList();

		FBuildPage::addTab(array("MAINDATA"=>$data));
	}
}