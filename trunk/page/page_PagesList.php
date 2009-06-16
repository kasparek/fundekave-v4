<?php
include_once('iPage.php');
class page_PagesList implements iPage {

	static function process($data) {

	}

	static function build() {
		$userId = FUser::logon();
		if ( false !== $userId ) {
			FForum::clearUnreadedMess();
			FForum::afavAll($userId);
		}
		$fPages = new FPages('',$userId);
		FBuildPage::addTab(array("MAINDATA"=>$fPages->printCategoryList()));
	}
}
