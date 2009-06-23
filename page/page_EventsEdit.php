<?php
include_once('iPage.php');
class page_EventsEdit implements iPage {

	static function process($data) {
		
		FEvents::processForm($data, true);

	}

	static function build() {
		
		FBuildPage::addTab( array("MAINDATA"=>FEvents::editForm() ) );
		
	}
}