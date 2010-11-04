<?php
include_once('iPage.php');
class page_PagesBooked implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		if(FRules::getCurrent(FConf::get('settings','perm_add_forum')))FMenu::secondaryMenuAddItem(FSystem::getUri('t=forum','foall','a'), FLang::$LABEL_PAGE_FORUM_NEW);
		if(FRules::getCurrent(FConf::get('settings','perm_add_blog')))FMenu::secondaryMenuAddItem(FSystem::getUri('t=blog','foall','a'), FLang::$LABEL_PAGE_BLOG_NEW);
		if(FRules::getCurrent(FConf::get('settings','perm_add_galery')))FMenu::secondaryMenuAddItem(FSystem::getUri('t=galery','foall','a'), FLang::$LABEL_PAGE_GALERY_NEW);
	
		FMenu::secondaryMenuAddItem(FSystem::getUri('t=forum','',''),FLang::$LABEL_FORUMS,array('parentClass'=>'opposite'));
		FMenu::secondaryMenuAddItem(FSystem::getUri('t=blog','',''),FLang::$LABEL_BLOGS,array('parentClass'=>'opposite'));
		FMenu::secondaryMenuAddItem(FSystem::getUri('t=galery','',''),FLang::$LABEL_GALERIES,array('parentClass'=>'opposite'));

		$type='';
		if(isset($data['__get']['t'])) $type = $data['__get']['t'];
		if(!isset(FLang::$TYPEID[$type])) $type='forum';

		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;

		
		

		$user = FUser::getInstance();
		$bookOrder = $user->userVO->getXMLVal('settings','bookedorder') * 1;
			
		//---template init
		$tpl = FSystem::tpl('pages.favorite.tpl.html');

		$userId=$user->userVO->userId;

		$fPages = new FPages($type,$user->userVO->userId);
		$fPages->setWhere('sys_pages.userIdOwner="'.$userId.'" and sys_pages.locked<3');
		if($bookOrder==1) $fPages->setOrder('name');
		else $fPages->setOrder('(sys_pages.cnt-favoriteCnt) desc,sys_pages.name');
		$arraudit = $fPages->getContent();
		if(count($arraudit)>0){
			$tpl->setVariable('PAGELINKSOWN',$fPages->printPagelinkList($arraudit,array('noitem'=>true)));
			$newSum=0;
			foreach($arraudit as $forum) $newSum += $forum->unreaded;
			if($newSum>0) $tpl->setVariable('OWNERNEW',$newSum);
		} else {
			$tpl->setVariable('CREATETYPE',FSystem::getUri('t='.$type,'foalla'));
			$tpl->setVariable('TYPE',FLang::$TYPEID[$type]);
		}

		//vypis oblibenych
		$fPages = new FPages($type,$user->userVO->userId);
		$fPages->setWhere('f.book="1" and sys_pages.userIdOwner!="'.$userId.'" and sys_pages.locked<2');
		if($bookOrder==1) $fPages->setOrder('sys_pages.name');
		else $fPages->setOrder('(sys_pages.cnt-favoriteCnt) desc,sys_pages.name');
		$arraudit = $fPages->getContent();
		
		if(count($arraudit)>0){
			$tpl->setVariable('PAGELINKSBOOKED',$fPages->printPagelinkList($arraudit,array('noitem'=>true)));
			$newSum=0;
			foreach($arraudit as $forum) $newSum += $forum->unreaded;
			if($newSum>0) $tpl->setVariable('BOOKEDNEW',$newSum);
		} else {
			$tpl->setVariable('FINDFAVORITE',FSystem::getUri('','foall',$type));
		}

		//vypis novych
		$fPages = new FPages($type,$user->userVO->userId);
		$fPages->setWhere('f.pageId=sys_pages.pageId and f.book="0" and f.userId!=sys_pages.userIdOwner and sys_pages.userIdOwner!="'.$userId.'" and sys_pages.locked < 2');
		$fPages->setOrder('sys_pages.dateCreated desc');
		$fPages->setLimit(0,6);
		$arraudit = $fPages->getContent();
		if(count($arraudit)>0) {
			$tpl->setVariable('PAGELINKSNEW',$fPages->printPagelinkList($arraudit,array('noitem'=>true)));
		}
		$data = $tpl->get();

		FBuildPage::addTab(array("MAINDATA"=>$data));
	}
}