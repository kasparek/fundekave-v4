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
		if(SITE_STRICT == 1) {
				$tool->addWhere("c.pageIdTop = '".HOME_PAGE."'");
			}		
		$tool->setOrder('c.ord,c.name');
		
		$pageId='';
		if($user->pageVO) {
			$pageId = $user->pageVO->pageId;
		}
		
		switch($pageId) {
			case'event':
				$total ="c.num";
				break;
			case'eveac':
				$total ="select count(1) from sys_pages_items where dateStart < date_format(NOW(),'%Y-%m-%d') and categoryId=c.categoryId";
				break;
			default:
				if($user->pageVO->typeId=='top') {
					$total ='c.num';
				} else {
					$total ='c.num';
				}
		}
				
		$tool->setSelect('c.categoryId,c.name, ( '.$total.' ) as total');
		$arr = $tool->getContent();

		if(!empty($arr)) {
			$tpl = FSystem::tpl('item.pagelink.tpl.html');
			foreach ($arr as $category) {
				if($category[2] > 0) {
					$tpl->setVariable('URL', FSystem::getUri('c='.$category[0],$user->pageId,''));
					$tpl->setVariable('PAGENAME', $category[1]);
					$tpl->setVariable('SUM', $category[2]);
					$tpl->parse('item');
				}
			}
			return $tpl->get();
		}
	}
}