<?php
include_once('iPage.php');

class page_Sail implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {
		//form is processed in FAjax_item::submit
	}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
		$planPageId = 'vd98H';
		$positionPageId = 'DTMTH';

		$user = FUser::getInstance();

		$planRenderer = new FItemsRenderer();
		$planRenderer->setCustomTemplate('sail.plan.item.tpl.html');

		$planItems = new FItems('blog',false,$planRenderer);
		$planItems->addWhere("sys_pages_items.pageId='".$planPageId."' and sys_pages_items.public=1");
		$planItems->setOrder('itemId');
		//get all plan items
		$planRender = $planItems->render();
    


		$positionRenderer = new FItemsRenderer();
		$positionRenderer->setCustomTemplate('sail.position.item.tpl.html');

		$posItems = new FItems('blog',false,$positionRenderer);
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