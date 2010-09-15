
<?php
include_once('iPage.php');
class page_ForumView implements iPage {

	static function process($data) {
		$user = FUser::getInstance();

		if(empty($user->pageParam)) {
				
			if($user->pageVO->typeId=='blog') {
				if(!$user->itemVO) return;
				$data['itemIdTop'] = $user->itemVO->itemId;
				FForum::process($data);
			}
			if($user->pageVO->typeId=='forum') {
				FForum::process($data);
			}
		}

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		FProfiler::profile('page_ForumView--START');

		//TODO: label calendar?
		//		if(empty($user->params)) {
		//			FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'k'),FLang::$LABEL_CALENDAR);
		//		}
		//TODO: params==k neexistuje
		/*
		if($user->params=='k') {
			//---events archiv
			FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'k'),FLang::$LABEL_EVENTS_ARCHIV);
			//---new event
			FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'u'),FLang::$LABEL_EVENTS_CREATE);
		}
		*/
		/* PALCE FILTER TOOLBAR */
		/*
		 if($user->idkontrol) {
			if($user->pageParam=='') {
			if(isset($_GET['s']) || FItemsToolbar::isToolbarEnabled()) {
			//---show enabled toolbar
			FBuildPage::addTab(array("MAINDATA"=>FItemsToolbar::getTagToolbar(false)));
			} else {
			//---button to enable toolbar
			FMenu::secondaryMenuAddItem(FSystem::getUri('m=items-tool'), FLang::$LABEL_THUMBS,0,'','fajaxa');
			}
			}
			}
			*/


		if ($user->pageVO->typeId=='blog') {

			/* BLOG */
			$itemId = 0;
			if($user->itemVO) $itemId = $user->itemVO->itemId;
			FBuildPage::addTab(array("MAINDATA"=>FBlog::listAll($itemId,(($user->pageParam == 'u')?(true):(false))),"MAINID"=>'bloged'));

		} else {
				
			/* WEBCAMS */
			//$fuvatar =  new FUvatar();
			//FBuildPage::addTab(array("MAINDATA"=>$fuvatar->getLive()));
			//$fuvatar = false;
			
			FProfiler::profile('page_ForumView--FForum::show-BEFORE');
			/* FORUM */
			FBuildPage::addTab(array("MAINDATA"=>FForum::show()));
			FProfiler::profile('page_ForumView--FForum::show-DONE');
				
		}

	}
}