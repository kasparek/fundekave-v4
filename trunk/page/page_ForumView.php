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

		if(!empty($user->pageParam) || ($userId > 0 && $typeId == 'blog')) {
			FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,''), FLang::$BUTTON_PAGE_BACK);
		}

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
			FSystem::secondaryMenuAddItem($user->getUri('',$pageId,'e'), FLang::$LABEL_SETTINGS,'',1);
		}

		//tlacitko sledovat - jen pro nemajitele
		if($user->idkontrol) {
			if($user->pageParam=='' && $user->pageVO->userIdOwner != $userId) {
				FSystem::secondaryMenuAddItem('#book',((0 == $user->isPageFavorite())?( FLang::$LABEL_BOOK ):( FLang::$LABEL_UNBOOK )),"xajax_forum_auditBook('".$pageId."','".$userId."');",0,'bookButt');
			}

			FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,'p'), FLang::$LABEL_POLL);
			FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,'s'), FLang::$LABEL_STATS);

			if($user->pageParam=='') {
				if(isset($_GET['s']) || FItems::isToolbarEnabled()) $TOPTPL->addTab(array("MAINDATA"=>FItems::getTagToolbar(false)));
				else {
					FSystem::secondaryMenuAddItem($user->getUri('s=t'), FLang::$LABEL_THUMBS,"xajax_forum_toolbar();return false;");
				}
			}

		}
		if($typeId=='forum') {
			FSystem::secondaryMenuAddItem($user->getUri('',$pageId,'h'), FLang::$LABEL_HOME);
		}

		if($user->pageParam == 'e') {

			require(ROOT.ROOT_CODE.'page.edit.php');

		} elseif($user->pageParam == 'p') {

			require(ROOT.ROOT_CODE.'page.poll.php');

		} elseif($user->pageParam == 's') {

			require(ROOT.ROOT_CODE.'page.stat.php');

		} elseif($user->pageParam == 'h') {

			$tmptext = '';
			$homePage = $user->pageVO->getPageParam('home');

			if(!empty($home)) $tmptext = $home;
			else $tmptext = FLang::$MESSAGE_FORUM_HOME_EMPTY;

			FBuildPage::addTab(array("MAINDATA"=>$tmptext));

		} elseif ($typeId=='blog') {

			$fBlog = new FBlog();
			FBuildPage::addTab(array("MAINDATA"=>$fBlog->listAll($user->itemVO->itemId,(($user->pageParam == 'u')?(true):(false))),"MAINID"=>'bloged'));

		} else {

			$fuvatar =  new FUvatar();
			FBuildPage::addTab(array("MAINDATA"=>$fuvatar->getLive()));

			FBuildPage::addTab(array("MAINDATA"=>FForum::show()));

		}

	}
}