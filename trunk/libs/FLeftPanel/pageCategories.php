<?php
class pageCategories {
	static function show() {
		$user = FUser::getInstance();
		$arr = FDBTool::getAll("select categoryId,name from sys_pages_category where typeId = '".$user->pageVO->pageId."' order by ord,name");
		if(!empty($arr)) {
			$tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
			$tpl->loadTemplatefile('sidebar.page.categories.tpl.html');	
			foreach ($arr as $category) {
				$tpl->setCurrentBlock('item');
				$tpl->setVariable('PAGEID',$user->pageId);
				$tpl->setVariable('CATEGORYID',$category[0]);
				$tpl->setVariable('NAME',$category[1]);
				$tpl->setVariable('SUM',FDBTool::getOne("select count(1) from sys_pages_items where categoryId='".$category[0]."'"));
				$tpl->parseCurrentBlock();
			}
			return $tpl->get();
		}
	}
}