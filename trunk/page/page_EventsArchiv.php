<?php
include_once('iPage.php');
class page_EventsArchiv implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		
		FBuildPage::addTab(array("MAINDATA"=>FEvents::show(true),"MAINID"=>'fajaxContent'));
		
	}
}