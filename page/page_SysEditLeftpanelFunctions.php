<?php
include_once('iPage.php');
class page_SysEditLeftpanelFunctions implements iPage {

	static function process() {
		$category = new fCategory('sys_leftpanel_functions','functionId');
		$category->process();
	}

	static function build() {
		$category = new fCategory('sys_leftpanel_functions','functionId');
		$category->ident = 'rhfunctions';

		$category->arrHead=array(FLang::$LABEL_CATEGORY_FUNCTION,FLang::$LABEL_CATEGORY_NAME,FLang::$LABEL_CATEGORY_PUBLIC);
		$category->arrInputType=array("text","text",'public');
		$category->arrClass=array('','','');
		$category->arrDbUsedCols=array('function','name','public');
		$category->requiredCol = 'function';
		$category->setOrder('name');

		$tmptext = $category->getEdit();

		FBuildPage::addTab(array("MAINDATA"=>$tmptext));
	}
}