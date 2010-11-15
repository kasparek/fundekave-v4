<?php
class Sidebar_map {
	static function show() {
		$user = FUser::getInstance();
		
		$list = array();
		
		$type='';
		$category=0;
		if(isset(FLang::$TYPEID[$user->pageVO->typeIdChild])) $type=$user->pageVO->typeIdChild;
		if(isset(FLang::$TYPEID[$user->pageVO->typeId])) $type=$user->pageVO->typeId;
		if(isset(FLang::$TYPEID[$user->pageParam])) $type=$user->pageParam;
		if($user->categoryVO){
			$category=$user->categoryVO->categoryId;
			$type=$user->categoryVO->typeId;
		} 
		
		$dbtool = new FDBTool('sys_pages_items_properties');
		$dbtool->setSelect("value,'type.item'");
		$dbtool->setWhere("name='position'");
		$dbtool->addJoinAuto('sys_pages_items','itemId',array(),'join');
		if(empty($user->itemVO)) $dbtool->addWhere("(sys_pages_items.itemIdTop is null or sys_pages_items.itemIdTop='')");
		else $dbtool->addWhere("(sys_pages_items.itemId='".$user->itemVO->itemId."' or sys_pages_items.itemIdTop='".$user->itemVO->itemId."')");
		if(SITE_STRICT == 1) $dbtool->addWhere("sys_pages_items.pageIdTop='".HOME_PAGE."'");
		$dbtool->addWhere('sys_pages_items.public=1');
		if($user->pageVO->typeId!='top') {
			$dbtool->addWhere('sys_pages_items.pageId="'.$user->pageVO->pageId.'"');
		} else {
			if($type!='') $dbtool->addWhere("sys_pages_items.typeId='".$type."'");
			if($category>0) $dbtool->addWhere("sys_pages_items.categoryId='".$category."'");
		}
		$tmp = $dbtool->getContent(0,20);
		if(!empty($tmp)) $list = array_merge($list,$tmp);
		
		//PAGES
		$showPageLine = false; 
		if(empty($user->itemVO)) {
			$dbtool = new FDBTool('sys_pages_properties');
			$dbtool->setSelect("value,'type.page'");
			$dbtool->setWhere("name='position'");
			$dbtool->addJoinAuto('sys_pages','pageId',array(),'join');
			$dbtool->addWhere('sys_pages.public=1');
			if($user->pageVO->typeId!='top') $dbtool->addWhere("sys_pages.pageId='".$user->pageVO->pageId."'"); 
			if(SITE_STRICT == 1) $dbtool->addWhere("sys_pages.pageIdTop='".HOME_PAGE."'");
			if($type!='') $dbtool->addWhere("sys_pages.typeId='".$type."'");
			if($category>0) $dbtool->addWhere("sys_pages.categoryId='".$category."'");
			$tmp = $dbtool->getContent(0,20);
			if(count($tmp)==1) $showPageLine=true; 
			if(!empty($tmp)) $list = array_merge($list,$tmp);
		}
		if(empty($list)) return;
		
		//OUTPUT
		$tpl = FSystem::tpl('sidebar.map.tpl.html');
		if(isset(FLang::$TYPEID[$user->pageVO->typeId])) $pageId=$user->pageVO->pageId;
		else if(isset(FLang::$TYPEID[$user->pageVO->typeIdChild])) $pageId=$user->pageVO->pageId;
		else $pageId='foall';
		$par=array();
		if(!empty($user->itemVO)) $par[] = 'i='.$user->itemVO->itemId;
		if($type!='') $par[] = 't='.$type;
		if($category>0) $par[] = 'c='.$category;
		$tpl->setVariable("URL",FSystem::getUri(implode('&',$par),$pageId,'m'));
		while($row=array_pop($list)) {
			$position = $row[0];
			$journey = explode(';',$position);
			list($lat,$lng)=explode(',',$journey[count($journey)-1]);
			$tpl->setVariable('STATICMARKERPOS',round($lat,4).','.round($lng,4));
			if(count($journey)>1) {
				if($row[1]=='type.page' && $showPageLine==true) {
					$geoEncode = new GooEncodePoly();
					$tpl->setVariable('SWPLIST','enc:'.$geoEncode->encode($journey));
				}
			}
			$tpl->parse('marker');
		}
		$ret = $tpl->get();
		return $ret;
	}
}