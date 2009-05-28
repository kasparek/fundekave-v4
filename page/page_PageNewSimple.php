<?php
include_once('iPage.php');
class page_PagesList implements iPage {

	static function process() {

		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;
		// blog or forum
		$arrDefaultCategory = array('blog'=>318,'forum'=>301);

		//--creating action
		if(isset($_REQUEST["add"])) {
			$ocem= FSystem::textins($_POST["ocem"],array('plainText'=>1));
			$nazev= FSystem::textins($_POST["nazev"],array('plainText'=>1));
			if($nazev=='') fError::addError((($typeId=='forum')?(FLang::$ERROR_FORUM_NAMEEMPTY):(FLang::$ERROR_BLOG_NAMEEMPTY)));
			if(FPages::page_exist('name',$nazev)) fError::addError(($typeId=='forum')?(FLang::$ERROR_FORUM_NAMEEXISTS):(FLang::$ERROR_BLOG_NAMEEXISTS));
			if(!fError::isError()) {
				$fPageSave = new fPagesSaveTool($typeId);
				$newPageId = $fPageSave->savePage(array('name'=>$nazev,'categoryId'=>$arrDefaultCategory[$typeId],
      'description'=>$ocem,'userIdOwner'=>$user->userVO->userId));
				$user->cacheRemove('calendarlefthand');
				fError::addError(FLang::$MESSAGE_SUCCESS_CREATE.': <a href="?k='.$newPageId.'">'.$nazev.'</a>');
				fHTTP::redirect(FUser::getUri());
			} else {
				$cache = FCache::getInstance('s');
				$cache->setData(array($nazev,$ocem),'newP','form');
			}
		}

	}

	static function build() {

		$cache = FCache::getInstance('s');
		$nazev = '';
		$ocem = '';
		if(($arr = $cache->getData('newP','form')) !== false) {
			$nazev = $arr[0];
			$ocem = $arr[1];
		}


		$tpl = new fTemplateIT('forum.new.tpl.html');
		$tpl->setVariable('FORMACTION',FUser::getUri());
		$tpl->setVariable('NAME',$nazev);
		$tpl->setVariable('DESC',$ocem);


		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}