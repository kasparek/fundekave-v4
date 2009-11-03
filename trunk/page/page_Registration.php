<?php
include_once('iPage.php');
class page_Registration implements iPage {
	
	static function process($data) {

		if(isset($data['addusr'])) {
			$user = FUser::getInstance();
			if (!FUser::logon()) {

				$user->register( $data );
				
			}
		}
		
	}
	
	static function build($data=array()) {
		$cache = FCache::getInstance('s');
		if(($data=$cache->getData('reg','form'))!==false) {
			$cache->invalidateData('reg','form');	
		} else {
			$data = array('jmenoreg'=>'','pwdreg1'=>'','pwdreg2'=>'','email'=>'');
		}
		$tpl = FSystem::tpl('user.registration.tpl.html');
		
		if (!FUser::logon()) {
			$tpl->setVariable('FORMACTION',FSystem::getUri());
			$tpl->setVariable('NAME',$data['jmenoreg']);
			$tpl->setVariable('PWD1',$data['pwdreg1']);
			$tpl->setVariable('PWD2',$data['pwdreg2']);
			$tpl->setVariable('EMAIL',$data['email']);
		} else {
			$tpl->setVariable('DUMMYNO','');
		}
		
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
}