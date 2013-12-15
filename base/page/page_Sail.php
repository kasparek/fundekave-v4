<?php
include_once('iPage.php');

class page_Sail implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
		$planPageId = 'vd98H';
		$positionPageId = 'DTMTH';

		$user = FUser::getInstance();

		$planItems = new FItems('blog',false);
		$planItems->addWhere("sys_pages_items.pageId='".$planPageId."' and sys_pages_items.public=1");
		$planItems->setOrder('dateStart');
		//get all plan items
		$planRender = $planItems->render();

		$posItems = new FItems('blog',false);
		$posItems->addWhere("sys_pages_items.pageId='".$positionPageId."'");
		$posItems->setOrder('itemId desc');
		//get all plan items
		$posRender = $posItems->render(0,6);
    

		//HEADER
		$vars['CONTENT'] = FText::postProcess($user->pageVO->content);
		$vars['PLANITEMS'] = $planRender;
		$vars['POSITIONITEMS'] = $posRender;


		//render to template
		$tpl = FSystem::tpl('sail.page.tpl.html');
		$tpl->setVariable($vars);
		$output = $tpl->get();
		//output
		FBuildPage::addTab(array("MAINDATA"=>$output));
		
	}

}