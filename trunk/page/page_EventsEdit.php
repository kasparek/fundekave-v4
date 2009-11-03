<?php
include_once('iPage.php');
class page_EventsEdit implements iPage {

	static function process($data) {
		
		FEvents::processForm($data, true);

	}

	static function build($data=array()) {
		
		$user = FUser::getInstance();
		$itemId=0;
		if($user->itemVO) $itemId = $user->itemVO->itemId;
		FBuildPage::addTab( array("MAINDATA"=>FEvents::editForm($itemId),"MAINID"=>'fajaxContent' ) );
		
	}
}