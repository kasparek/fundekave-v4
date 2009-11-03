<?php
include_once('iPage.php');
class page_ForumView implements iPage {

	static function process($data) {
		$user = FUser::getInstance();

		if(empty($user->pageParam)) {
			
			if($user->pageVO->typeId=='blog') {
				if(!$user->itemVO) return;
				$data['itemIdTop'] = $user->itemVO->itemId;
				FForum::process($data,"FBlog::callbackForumProcess");
			}
			
			if($user->pageVO->typeId=='forum') {
				FForum::process($data);
			}
		}

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		FProfiler::profile('page_ForumView--START');
		
		/* PALCE FILTER TOOLBAR */
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
		


		if ($user->pageVO->typeId=='blog') {

			/* BLOG */
			$itemId = 0;
			if($user->itemVO) $itemId = $user->itemVO->itemId;
			FBuildPage::addTab(array("MAINDATA"=>FBlog::listAll($itemId,(($user->pageParam == 'u')?(true):(false))),"MAINID"=>'bloged'));

		} else {
			
			/* WEBCAMS */
			$fuvatar =  new FUvatar();
			FBuildPage::addTab(array("MAINDATA"=>$fuvatar->getLive()));
			$fuvatar = false;
			FProfiler::profile('page_ForumView--FForum::show-BEFORE');
			/* FORUM */
			FBuildPage::addTab(array("MAINDATA"=>FForum::show()));
			FProfiler::profile('page_ForumView--FForum::show-DONE');
		}

	}
}