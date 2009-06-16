<?php
include_once('iPage.php');
class page_ForumView implements iPage {

	static function process() {
		$user = FUser::getInstance();

		if($user->pageVO->typeId=='forum' && empty($user->pageParam)) {
			FForum::process();
		}


	}

	static function build() {
		$user = FUser::getInstance();

		/* PALCE FILTER TOOLBAR */
		if($user->idkontrol) {
			if($user->pageParam=='') {
				if(isset($_GET['s']) || FItemsToolbar::isToolbarEnabled()) {
					//---show enabled toolbar
					$TOPTPL->addTab(array("MAINDATA"=>FItemsToolbar::getTagToolbar(false)));	
				} else {
					//---button to enable toolbar
					FSystem::secondaryMenuAddItem(FUser::getUri('m=items-tool'), FLang::$LABEL_THUMBS,0,'itemsTool');
				}
			}
		}
		


		if ($user->pageVO->typeId=='blog') {

			/* BLOG */
			FBuildPage::addTab(array("MAINDATA"=>FBlog::listAll($user->itemVO->itemId,(($user->pageParam == 'u')?(true):(false))),"MAINID"=>'bloged'));

		} else {
			
			/* WEBCAMS */
			$fuvatar =  new FUvatar();
			FBuildPage::addTab(array("MAINDATA"=>$fuvatar->getLive()));

			/* FORUM */
			FBuildPage::addTab(array("MAINDATA"=>FForum::show()));

		}

	}
}