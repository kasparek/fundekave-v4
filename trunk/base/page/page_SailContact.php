<?php
include_once('iPage.php');

class page_SailContact implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
    $user = FUser::getInstance(); 
    $tpl = FSystem::tpl('sail.contact.tpl.html');
    $tpl->setVariable('FORMACTION',FSystem::getUri());
    
    $cache = FCache::getInstance('s',0);
		$formData = $cache->getData($user->pageVO->pageId, 'form');
    if($formData) {
      $tpl->setVariable('NAME',$formData['name']);
      $tpl->setVariable('EMAIL',$formData['email']);
      $tpl->setVariable('MESSAGE',$formData['text']);
      $cache->invalidateData( $user->pageVO->pageId, 'form');
    }
        
    $tpl->setVariable('CONTENT',FText::postProcess($user->pageVO->content));
    
    FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
  }
}