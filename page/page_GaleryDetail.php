<?php
include_once('iPage.php');
class page_GaleryDetail implements iPage {

	static function process() {
		if($user->pageParam == 'e') {
			page_PageEdit::process();
		}
	}

	static function build() {
		$user = FUser::getInstance();
		$pageId = $user->pageVO->pageId;
		$userId = $user->userVO->userId;
		
		if(fRules::getCurrent(2)) {
			if($user->pageParam == 'e') fSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,''),BUTTON_PAGE_BACK);
			else fSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,'e'),FLang::$LABEL_SETTINGS);
		}
		if($user->idkontrol) {
			if($user->pageParam=='' && $user->pageVO->userIdOwner != $userId) {
				fSystem::secondaryMenuAddItem('#book',((0 == $user->isPageFavorite())?(FLang::$LABEL_BOOK):(FLang::$LABEL_UNBOOK)),"xajax_forum_auditBook('".$pageId."','".$userId."');",0,'bookButt');
			}
			fSystem::secondaryMenuAddItem(FUser::getUri('p=a'),FLang::$LABEL_POCKET_PUSH,"xajax_pocket_add('".$pageId."','1');return false;",0);
		}
		if($user->pageParam == 'e') {
			page_PageEdit::build();
		} else {
			$galery = new fGalery();
			$galery->getGaleryData($pageId);
			if(fRules::getCurrent(2)) {
				//---run just wher owner access
				$galery->refreshImgToDb($pageId);
			}


			if($user->itemVO->itemId == 0) {

				$fItems = new fItems();

				if($user->idkontrol) $fItems->xajaxSwitch = true; //---THINK ABOUT USABILITY AND BACK BUTTON
				$fItems->showTooltip = false;

				$fItems->initData('galery');
				$fItems->setWhere('i.pageId="'.$pageId.'"');
				$fItems->addWhere('i.itemIdTop is null');
				$totalItems = $fItems->getCount();
				$perPage = $galery->gPerpage;

				if($totalItems==0){
					fError::addError(ERROR_GALERY_NOFOTO);
					$user->pageAccess = false;
				} else {
					 
					if($galery->gOrderItems==0) $fItems->setOrder('i.enclosure');
					else $fItems->setOrder('i.dateCreated desc');

					$pager = fSystem::initPager($totalItems,$perPage);
					$od = ($pager->getCurrentPageID()-1) * $perPage;

					$fItems->openPopup = ($user->userVO->zgaltype==0)?(false):(true);
					$fItems->getData($od,$perPage);

					//---nahledy
					$tpl = new fTemplateIT('galery.thumbnails.tpl.html');
					$tpl->setCurrentBlock("thumbnails");

					/*
					 $category = new fCategory('sys_pages_category','categoryId');
					  
					 $tpl->setVariable("MAINGALERYLINK",'?k=galer');
					 $tpl->setVariable("CATEGORYLINK",'?k=galer&k='.$galery->gText);
					 $tpl->setVariable("CATEGORYDESC",$galery->gText);
					 $tpl->setVariable("CATEGORYNAME",$galery->gText);
					 */
					 
					$tpl->setVariable("GALERYTEXT",$galery->gText);
					$tpl->setVariable("GALERYHEAD",$user->pageVO->content);

					$x=0;
					while($fItems->arrData && $x < $galery->gPerpage) {
						$tpl->setCurrentBlock("cell");
						$fItems->parse();
						$tpl->setVariable("THUMBNAIL",$fItems->show());
						 
						$tpl->parseCurrentBlock();
					}

					 
					 
					if($perPage<$totalItems) {
						$tpl->setVariable("PAGERSTART",$pager->links);
						$tpl->setVariable("PAGEREND",$pager->links);
					}
					$tpl->edParseBlock("thumbnails");
					 
					$tmptext=$tpl->get();
					 
					$TOPTPL->addTab(array("MAINDATA"=>$tmptext,"MAINID"=>'fotoBox'));
				}
			} else {
				//---detail foto
				FBuildPage::addTab(array("MAINDATA"=>$galery->printDetail($user->itemVO->itemId),"MAINID"=>'fotoBox'));
			}
		}
	}
}