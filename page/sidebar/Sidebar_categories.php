<?php
class Sidebar_categories {
	static function show() {
		$user = FUser::getInstance();
		$multiType=false;
		$tool = new FDBTool('sys_pages_category as c','c.categoryId');
		if($user->pageVO->typeId=='top') {
			$type='';
			if(isset(FLang::$TYPEID[$user->pageVO->typeIdChild])) $type=$user->pageVO->typeIdChild;
			if(isset(FLang::$TYPEID[$user->pageParam])) $type=$user->pageParam;
			if($type!='') {
				$tool->setWhere("c.typeId = '".$type."'");
			} else {
				$tool->setWhere("c.typeId in ('galery','forum','blog')");
				$multiType=true;
			}
		} else {
			$tool->setWhere("c.typeId = '".$user->pageVO->pageId."'");
		}
		if(SITE_STRICT == 1) {
			$tool->addWhere("c.pageIdTop = '".HOME_PAGE."'");
		}
		$tool->setOrder('c.name');

		$pageId='';
		if($user->pageVO) {
			$pageId = $user->pageVO->pageId;
		}

		switch($pageId) {
			case'eveac':
				//$total ="select count(1) from sys_pages_items where dateStart < date_format(NOW(),'%Y-%m-%d') and categoryId=c.categoryId";
				$total = "'0'";
				break;
			default:
				$total ='c.num';
		}

		$tool->setSelect('c.categoryId,c.name, ( '.$total.' ) as total,c.typeId');
		$arr = $tool->getContent();

		if(!empty($arr)) {
			$tpl = FSystem::tpl('item.pagelink.tpl.html');
			if($multiType){
				foreach(FLang::$TYPEID as $k=>$v) {
					if(FConf::get('settings','perm_add_'.$k)>0) {
						$tpl->setVariable('URL', FSystem::getUri('',$multiType?'foall':$user->pageId,$k));
						$tpl->setVariable('PAGENAME', $v);
						$tpl->parse();
					}
				}
			}
			foreach ($arr as $category) {
				$tpl->setVariable('URL', FSystem::getUri('c='.$category[0],$multiType?'foall':$user->pageId,''));
				$tpl->setVariable('PAGENAME', ($multiType ? FLang::$TYPEID[$category[3]].' ' : '') . $category[1]);
				if($category[2]>0) $tpl->setVariable('SUM', $category[2]);
				$tpl->parse();
			}
			return $tpl->get();
		}
	}
}