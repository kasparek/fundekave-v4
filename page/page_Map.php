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
			$itemVO->options['showPage']=true;
			$tpl->setVariable(array('MAPTITLE'=>$itemVO->addon //TODO - forum:username,galery:pagename+1/12
			,'MAPPOSITION'=>str_replace(";","\n",$itemVO->prop('position'))
			,'MAPINFO'=>( $itemVO->typeId=='galery' ? $itemVO->render()
			: ('<h2><a href="'.FSystem::getUri('i='.$itemVO->itemId).'">'.$itemVO->addon.'</a></h2>'
			.str_replace(array("\n","\r"),'',$itemVO->text)) )
			));
			if($itemVO->typeId=='galery') {
				$tpl->setVariable('MAPICO',$itemVO->getImageUrl(null,'50x50/crop'));
			}
			//info
			//forum - hover-avatar, click-avatar, datum, <forum name>
			//galery - hover-100x100 thumb, click-render with <showPage>
			//blog,event - hover-addon, click-<item link>, datum
			$tpl->parse('mapdata');
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}