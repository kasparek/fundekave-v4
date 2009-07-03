<?php
class pageCategories {
static function show() {
		$user = FUser::getInstance();
		$cache = FCache::getInstance('f',86400);
		if(false===($tmptext = $cache->getData(($user->userVO->userId*1).'-user', 'pagescategories'))) {
			$arr = FDBTool::getAll("select categoryId,name from sys_pages_category where typeId = '".$user->pageVO->pageId."' order by ord,name");
			$tmptext = '';
			if(!empty($arr)) {
					
				$tpl = new FTemplateIT('sidebar.page.categories.tpl.html');
				foreach ($arr as $category) {
					$tpl->setCurrentBlock('item');
					$tpl->setVariable('PAGEID',$user->pageVO->pageId);
					$tpl->setVariable('CATEGORYID',$category[0]);
					$tpl->setVariable('NAME',$category[1]);
					$tpl->setVariable('SUM',FDBTool::getOne("select count(1) from sys_pages_items where categoryId='".$category[0]."'"));
					$tpl->parseCurrentBlock();
				}
				$tmptext = $tpl->get();
			}
			$cache->setData($tmptext);
		}
		return $tmptext;
	}
}