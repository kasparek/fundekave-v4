<?php
include_once('iPage.php');
class page_PageNewSimple implements iPage {

	static function process($data) {

		$user = FUser::getInstance();
		$typeId = $user->pageVO->typeIdChild;
		// blog or forum
		$arrDefaultCategory = array('blog'=>318,'forum'=>301);

		//--creating action
		if(isset($_REQUEST["add"])) {
			$ocem= FSystem::textins($data["ocem"],array('plainText'=>1));
			$nazev= FSystem::textins($data["nazev"],array('plainText'=>1));
			if($nazev=='') {
				FError::addError((($typeId=='forum')?(FLang::$ERROR_FORUM_NAMEEMPTY):(FLang::$ERROR_BLOG_NAMEEMPTY)));
			}
			if(FPages::page_exist('name',$nazev)) {
				FError::addError(($typeId=='forum')?(FLang::$ERROR_FORUM_NAMEEXISTS):(FLang::$ERROR_BLOG_NAMEEXISTS));
			}
			if(!FError::isError()) {
				$pageVO = new PageVO();
				$pageVO->typeId = $typeId;
				$pageVO->setDefaults();
				$pageVO->nameshort = (isset(FLang::${$pageVO->typeId}))?(FLang::${$pageVO->typeId}):('');
				$pageVO->name = $nazev;
				$pageVO->categoryId = $arrDefaultCategory[$typeId];
				$pageVO->description = $ocem;
				$pageVO->userIdOwner = $user->userVO->userId;
				$pageVO->save();
				$cache = FCache::getInstance('f');
				$cache->invalidateGroup('calendarlefthand');
				$cache->invalidateGroup('newpage');
				FError::addError(FLang::$MESSAGE_SUCCESS_CREATE.': <a href="'.FSystem::getUri('',$pageVO->pageId).'">'.$nazev.'</a>');
				FHTTP::redirect(FSystem::getUri());
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
			$cache->invalidateData('newP','form');
		}


		$tpl = new FTemplateIT('forum.new.tpl.html');
		$tpl->setVariable('FORMACTION',FSystem::getUri());
		$tpl->setVariable('NAME',$nazev);
		$tpl->setVariable('DESC',$ocem);


		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}