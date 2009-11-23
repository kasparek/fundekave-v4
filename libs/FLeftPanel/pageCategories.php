<?php
class pageCategories {
	static function show() {
		$user = FUser::getInstance();
		
		$tool = new FDBTool('sys_pages_category as c','c.categoryId');
		if($user->pageVO->typeId=='top') {
			$tool->setWhere("c.typeId = '".$user->pageVO->typeIdChild."'");
		} else {
			$tool->setWhere("c.typeId = '".$user->pageVO->pageId."'");
		}
		$tool->setOrder('c.ord,c.name');
		
		$pageId='';
		if($user->pageVO) {
			$pageId = $user->pageVO->pageId;
		}
		
		switch($pageId) {
			case'event':
				$total ="select count(1) from sys_pages_items where (dateStart >= date_format(NOW(),'%Y-%m-%d') or (dateEnd is not null and dateEnd >= date_format(NOW(),'%Y-%m-%d'))) and categoryId=c.categoryId";
				break;
			case'eveac':
				$total ="select count(1) from sys_pages_items where dateStart < date_format(NOW(),'%Y-%m-%d') and categoryId=c.categoryId";
				break;
			default:
				if($user->pageVO->typeId=='top') {
					$total ='select count(1) from sys_pages where categoryId=c.categoryId';
				} else {
					$total ='select count(1) from sys_pages_items where categoryId=c.categoryId';
				}
		}
		
		$tool->setSelect('c.categoryId,c.name, ( '.$total.' ) as total');
		$arr = $tool->getContent();

		if(!empty($arr)) {
			$tpl = FSystem::tpl(FLang::$TPL_SIDEBAR_PAGE_CATEGORIES);
			foreach ($arr as $category) {
				if($category[2] > 0) {
					$tpl->setVariable('URL', FSystem::getUri('c='.$category[0],$user->pageId,''));
					$tpl->setVariable('NAME', $category[1]);
					$tpl->setVariable('SUM', $category[2]);
					$tpl->parse('item');
				}
			}
			return $tpl->get();
		}
	}
}