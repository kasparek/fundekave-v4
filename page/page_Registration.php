<?php
include_once('iPage.php');
class page_Registration implements iPage {
	
	static function process($data) {

		if(isset($data['addusr'])) {
			$user = FUser::getInstance();
			$user->register($data);
		}
		
	}
	
	static function build() {
		
		$tpl = new FTemplateIT('user.registration.tpl.html');
		if (!FUser::logon()) {
			$tpl->setVariable('FORMACTION',FUser::getUri());
		} else {
			$tpl->setVariable('DUMMYNO','');
		}
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
}