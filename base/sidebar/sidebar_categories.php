<?php
class sidebar_categories {
	static function show() {
		$user = FUser::getInstance();
		$multiType=false;
		$tool = new FDBTool('sys_pages_category','sys_pages_category.categoryId');

		$site_based_prefix = 'sys_pages_category';
		if(SITE_STRICT) $site_based_prefix='sys_pages';

		if($user->pageVO->typeId=='top') {
			$type='';
			if(isset(FLang::$TYPEID[$user->pageVO->typeIdChild])) $type=$user->pageVO->typeIdChild;
			if(isset(FLang::$TYPEID[$user->pageParam])) $type=$user->pageParam;
			if($type!='') {
				$tool->setWhere($site_based_prefix.".typeId = '".$type."'");
			} else {
				$tool->setWhere($site_based_prefix.".typeId in ('galery','forum','blog')");
				$multiType=true;
			}
		} else {
			$tool->setWhere($site_based_prefix.".typeId = '".$user->pageVO->pageId."'");
		}

		$pageId='';
		if($user->pageVO) {
			$pageId = $user->pageVO->pageId;
		}

		switch($pageId) {
			case'eveac':
				$total = "'0'";
				break;
			default:
				$total ='count(sys_pages.pageId)';
		}

		$tool->setSelect('sys_pages_category.categoryId,sys_pages_category.name, ( '.$total.' ) as total,sys_pages_category.typeId');
		if(SITE_STRICT) {
			$tool->addWhere("sys_pages.pageIdTop in ('".SITE_STRICT."','".$user->pageVO->pageId."')");
		}
		$tool->addJoinAuto('sys_pages','categoryId',array(),'join');
		$tool->setGroup('sys_pages.categoryId');

		$tool->addWhere("sys_pages_category.public = '1'");
		$tool->setOrder('sys_pages_category.name');

		$arr = $tool->getContent();

		if(!empty($arr)) {
			$tpl = FSystem::tpl('sidebar.list.tpl.html');
			if($multiType){
				foreach(FLang::$TYPEID as $k=>$v) {
					if(FConf::get('settings','perm_add_'.$k)>0) {
						$tpl->setVariable('URL', FSystem::getUri('',$multiType?'foall':$user->pageId,$k));
						$tpl->setVariable('TEXT', $v);
						$tpl->parse('item');
					}
				}
			}
			
			foreach ($arr as $category) {
				$tpl->setVariable('URL', FSystem::getUri('c='.$category[0],$multiType?'foall':$user->pageId,''));
				$tpl->setVariable('TEXT', ($multiType ? FLang::$TYPEID[$category[3]].' ' : '') . $category[1]);
				if($category[2]>0) $tpl->setVariable('BADGE', $category[2]);
				$tpl->parse('item');
			}
			
			return $tpl->get();
		}
	}
}