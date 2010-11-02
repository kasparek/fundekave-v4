<?php
include_once('iPage.php');
class page_Map implements iPage {

	static function process($data){}

	static function build($data=array()) {
		FMenu::secondaryMenuAddItem(FSystem::getUri('','',''),FLang::$BUTTON_PAGE_BACK);
		
		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;

		$fitems = new FItems();
		$fitems->joinOnPropertie('position',0,'join');
		if($user->pageVO->typeId!='top') $fitems->addWhere('pageId="'.$user->pageVO->pageId.'"');
		$list = $fitems->getList();

		$tpl = FSystem::tpl('map.tpl.html');
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
					$title = $pageName;
					$info .= '<strong><a href="'.FSystem::getUri('i='.$itemVo->itemId,$itemVO->pageId,'').'">'.$pageName.'</a></strong><br />';
					$info .= FSystem::textIns($itemVO->text,array('plainText'=>1));
					$tpl->setVariable('MAPICO',FAvatar::getAvatarUrl($itemVO->userId));
				break;
				case 'event':
				case 'blog':
					$info .= '<strong><a href="'.FSystem::getUri('i='.$itemVO->itemId,$itemVO->pageId,'').'">'.$itemVO->addon.'</a></strong><br />';
				break;
			}
			$distance = (int) $itemVO->prop('distance');
			//$info.= $distance>0 ? '<div>Cesta: '.$distance.'NM</div>' : '';
			
		
			$tpl->setVariable(array('MAPTITLE'=>$title,'MAPINFO'=>$info,'MAPPOSITION'=>str_replace(";","\n",$itemVO->prop('position'))));
			//info
			//forum - hover-avatar, click-avatar, datum, <forum name>
			//galery - hover-100x100 thumb, click-render with <showPage>
			//blog,event - hover-addon, click-<item link>, datum
			$tpl->parse('mapdata');
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}