<?php
include_once('iPage.php');
class page_Map implements iPage {

	static function process($data){}

	static function build($data=array()) {
		FMenu::secondaryMenuAddItem(FSystem::getUri('','',''),FLang::$BUTTON_PAGE_BACK);
		
		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;
		
		$type='';
		$category=0;
		if(isset(FLang::$TYPEID[$user->pageVO->typeIdChild])) $type=$user->pageVO->typeIdChild;
		if(isset(FLang::$TYPEID[$user->pageVO->typeId])) $type=$user->pageVO->typeId;
		if(isset(FLang::$TYPEID[$user->pageParam])) $type=$user->pageParam;
		if($user->categoryVO){
			$category=$user->categoryVO->categoryId;
			$type=$user->categoryVO->typeId;
		}

		$fitems = new FItems();
		$fitems->joinOnPropertie('position',0,'join');
		if(SITE_STRICT == 1) $fitems->addWhere("pageIdTop='".HOME_PAGE."'");
		$fitems->addWhere('sys_pages_items.public=1');
		if($user->pageVO->typeId!='top') {
			$fitems->addWhere('sys_pages_items.pageId="'.$user->pageVO->pageId.'"');
		} else {
			if($type!='') $fitems->addWhere("sys_pages_items.typeId='".$type."'");
			if($category>0) $fitems->addWhere("sys_pages_items.categoryId='".$category."'");
		}
		$list = $fitems->getList();

		$tpl = FSystem::tpl('map.tpl.html');
		
		if(!empty($list))
		while($itemVO = array_pop($list)) {
			$info='';
			switch($itemVO->typeId) {
				case 'galery':
					$pageName = $itemVO->pageVO->get('name');
					$title = $pageName;
					$info .= '<strong><a href="'.FSystem::getUri('',$itemVO->pageId,'').'">'.$pageName.'</a></strong><br />';
					$info .= '<a href="'.FSystem::getUri('i='.$itemVO->itemId,$itemVO->pageId,'').'"><img src="'.$itemVO->thumbUrl.'" /></a>';
					$tpl->setVariable('MAPICO',$itemVO->getImageUrl(null,'40x40/crop')); 
				break;
				case 'forum':
					$pageName = $itemVO->pageVO->get('name');
					$title = $pageName.' - '.$itemVO->name;
					$info .= '<strong><a href="'.FSystem::getUri('i='.$itemVO->itemId,$itemVO->pageId,'').'">'.$pageName.'</a></strong><br />';
					$info .= $itemVO->dateCreatedLocal.'<br/>';
					$info .= FSystem::textIns($itemVO->text,array('plainText'=>1));
					$tpl->setVariable('MAPICO',FAvatar::getAvatarUrl($itemVO->userId));
				break;
				case 'event':
				case 'blog':
					$title = $itemVO->addon;
					$info .= '<strong><a href="'.FSystem::getUri('i='.$itemVO->itemId,$itemVO->pageId,'').'">'.$itemVO->addon.'</a></strong><br />';
					$info .= $itemVO->dateStartLocal.'<br/>';
				break;
			}
			$distance = (int) $itemVO->prop('distance');
			$info.= $distance>0 ? '<div>Cesta: '.$distance.'NM</div>' : '';
			
			$tpl->setVariable(array('MAPTITLE'=>$title,'MAPINFO'=>$info,'MAPPOSITION'=>str_replace(";","\n",$itemVO->prop('position'))));
			$tpl->parse('mapdata');
		}
		
		//PAGES
		$fitems = new FPages($type,$user->userVO->userId);
		$fitems->joinOnPropertie('position',0,'join');
		if($user->pageVO->typeId!='top') $fitems->addWhere('pageId="'.$user->pageVO->pageId.'"');
		if(SITE_STRICT == 1) $fitems->addWhere("pageIdTop='".HOME_PAGE."'");
		if($user->pageVO->typeId!='top') {
			$fitems->addWhere('sys_pages.pageId="'.$user->pageVO->pageId.'"');
		} else {
			if($type!='') $fitems->addWhere("sys_pages.typeId='".$type."'");
			if($category>0) $fitems->addWhere("sys_pages.categoryId='".$category."'");
		}
		$list = $fitems->getContent();
		if(!empty($list))
		while($pageVO = array_pop($list)) {
			$info = '<a href="'.FSystem::getUri('',$pageVO->pageId,'').'">'.$pageVO->name.'</a>';
			$distance = (int) $pageVO->prop('distance');
			$info.= $distance>0 ? '<div>Cesta: '.$distance.'NM</div>' : '';
			$title = $pageVO->name;
			$tpl->setVariable(array('MAPTITLE'=>$title,'MAPINFO'=>$info,'MAPPOSITION'=>str_replace(";","\n",$pageVO->prop('position'))));
			$tpl->parse('mapdata');
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}