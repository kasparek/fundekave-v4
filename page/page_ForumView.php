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
		$userId = $user->userVO->userId;
		$pageId = $user->pageVO->pageId;

		$typeId = $user->pageVO->typeId;

		//---backwards compatibility
		if(isset($_REQUEST['nid'])) {
			$user->itemVO = new ItemVO();
			$user->itemVO->itemId = (int) $_REQUEST['nid'];
			$user->itemVO->checkItem();
		}

		if(FRules::get($userId,$pageId,2)) {
			if(empty($user->pageParam)) {
				if($typeId=='blog') {
					FSystem::secondaryMenuAddItem($user->getUri('',$pageId,'a'), FLang::$LABEL_ADD, "xajax_blog_blogEdit('0');return false;",1);
				}
			}
		}

		//TODO: refactor adding of palce toolbar
		//tlacitko sledovat - jen pro nemajitele
		if($user->idkontrol) {
			if($user->pageParam=='') {
				if(isset($_GET['s']) || FItemsToolbar::isToolbarEnabled()) {
					$TOPTPL->addTab(array("MAINDATA"=>FItemsToolbar::getTagToolbar(false)));	
				}
				else {
					FSystem::secondaryMenuAddItem($user->getUri('s=t'), FLang::$LABEL_THUMBS,"xajax_forum_toolbar();return false;");
				}
			}

		}
		


		if ($typeId=='blog') {

			$fBlog = new FBlog();
			FBuildPage::addTab(array("MAINDATA"=>$fBlog->listAll($user->itemVO->itemId,(($user->pageParam == 'u')?(true):(false))),"MAINID"=>'bloged'));

		} else {

			$fuvatar =  new FUvatar();
			FBuildPage::addTab(array("MAINDATA"=>$fuvatar->getLive()));

			FBuildPage::addTab(array("MAINDATA"=>FForum::show()));

		}

	}
}