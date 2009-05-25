<?php
include_once('iPage.php');
class page_Registration implements iPage {
	
	static function process() {

		if(isset($_POST['addusr'])) {
			$user = FUser::getInstance();
			$user->register();
		}
		
	}
	
	static function build() {
		
		$tpl = new fTemplateIT('user.registration.tpl.html');
		if (!FUser::logon()) {
			$tpl->setVariable('FORMACTION',FUser::getUri());
		} else {
			$tpl->setVariable('DUMMYNO','');
		}
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
}