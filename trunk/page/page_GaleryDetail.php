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

		if(FRules::getCurrent(2)) {
			if($user->pageParam == 'e') FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,''),BUTTON_PAGE_BACK);
			else FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,'e'),FLang::$LABEL_SETTINGS);
		}
		if($user->idkontrol) {
			if($user->pageParam=='' && $user->pageVO->userIdOwner != $userId) {
				FSystem::secondaryMenuAddItem('#book',((0 == $user->isPageFavorite())?(FLang::$LABEL_BOOK):(FLang::$LABEL_UNBOOK)),"xajax_forum_auditBook('".$pageId."','".$userId."');",0,'bookButt');
			}
			FSystem::secondaryMenuAddItem(FUser::getUri('p=a'),FLang::$LABEL_POCKET_PUSH,"xajax_pocket_add('".$pageId."','1');return false;",0);
		}
		if($user->pageParam == 'e') {
			page_PageEdit::build();
		} else {
			if(FRules::getCurrent(2)) {
				//---run just wher owner access
				$galery = new FGalery();
				$galery->refreshImgToDb($pageId);
			}


			if($user->itemVO->itemId == 0) {
			
			$itemRenderer = new FItemsRenderer();
      $itemRenderer->showTooltip = false;



				$fItems = new FItems('galery',false,$itemRenderer);
				$fItems->setWhere('pageId="'.$pageId.'"');
				$fItems->addWhere('itemIdTop is null');
				$totalItems = $fItems->getCount();
				$perPage = $galery->gPerpage;

				if($totalItems==0){
					fError::addError(ERROR_GALERY_NOFOTO);
					$user->pageAccess = false;
				} else {

					if($galery->gOrderItems==0) $fItems->setOrder('i.enclosure');
					else $fItems->setOrder('i.dateCreated desc');

					$pager = FSystem::initPager($totalItems,$perPage);
					$od = ($pager->getCurrentPageID()-1) * $perPage;

					$fItems->openPopup = ($user->userVO->zgaltype==0)?(false):(true);
					$fItems->getData($od,$perPage);

					//---nahledy
					$tpl = new fTemplateIT('galery.thumbnails.tpl.html');
					$tpl->setCurrentBlock("thumbnails");

					/*
					 $category = new FCategory('sys_pages_category','categoryId');
					 	
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
				$itemId = $user->itemVO->itemId;
//TODO:need complete refactor
				FForum::process($itemId,"FGalery::callbackForumProcess");
				$fItems = new FItems();
				$itemId = $fItems->initDetail($itemId);

				if(!empty($itemId)) $this->getFoto($itemId);
				if(!empty($this->_fId)) {
					//---get pid for linkback
					$this->fotoHit();
					if(!$ret = $user->cacheGet('fotodetail',$itemId)) {
						$orderBy = $user->pageVO->getPageParam('enhancedsettings/orderitems');
						$arrItemId = $db->getCol("select itemId from sys_pages_items where pageId='".$this->_fGaleryId."' order by ".((($orderBy==0)?('enclosure'):('dateCreated'))));
							
						$arr = array_chunk($arrItemId,$this->gPerpage);
						foreach ($arr as $k=>$arrpage) {
							if(in_array($this->_fId,$arrpage)) {
								$pid = $k + 1;
								break;
							}
						}
						
						$tpl = new fTemplateIT('galery.detail.tpl.html');
						$backLink = '?k='.$user->currentPageId.'&amp;'.$conf['pager']['urlVar'].'='.$pid;
						$tpl->setVariable("LINKBACKTOP",$backLink);

						$tpl->setVariable("IMGALT", $this->_fGaleryName.' '.$this->_fDetail);
						$tpl->setVariable("IMGDIR", $this->getDetailUrl());
						if($this->_showComment && !empty($itemVO->text)) $tpl->setVariable("INFO",$this->_fComment);
							
						if(!empty($this->_fName)) {
							$user->currentPage["name"] = $this->_fName . ' - ' . $user->currentPage["name"];
						} else {
							$user->currentPage["name"] = $this->_fDetail . ' - ' . $user->currentPage["name"];
						}
							
						$tpl->setVariable("HITS",$this->_fHits);
						if($user->idkontrol) {
							$tpl->setVariable('TAG',FItems::getTag($itemId,$user->userVO->userId,'galery'));
							$tpl->setVariable('POCKET',fPocket::getLink($itemId));
						}
							
						$arrImgId = FSystem::array_neighbor($this->_fId,$arrItemId);
							
						$fItems->initData('galery');
						$fItems->setWhere('i.itemId in ('.$arrImgId['prev'].','.$arrImgId['next'].')');

						$fItems->showRating = false;
						$fItems->showTooltip = false;
						$fItems->openPopup = false;
						$fItems->showText = false;
						$fItems->showTag = false;
						$fItems->showPocketAdd = false;
						$fItems->xajaxSwitch = true;

						$fItems->getData();
							
						if(!empty($arrImgId['prev'])) {
							$fItems->parse($arrImgId['prev']);
							$tpl->setVariable("THUMBPREVIOUS",$fItems->show());
						}
						if(!empty($arrImgId['next'])) {
							$fItems->parse($arrImgId['next']);
							$tpl->setVariable("THUMBNEXT",$fItems->show());
							$tpl->touchBlock('nextlinkclose');
							if($user->idkontrol) $tpl->touchBlock('xajaxSwitch');
							$tpl->setVariable('NEXTLINK',$user->getUri('i='.$arrImgId['next']));
						}
							
						//TODO: comments in galery are switched offf in this release
						//$tpl->setVariable('COMMENTS',FForum::show($itemId,$user->idkontrol,$fItems->itemIdInside,array('formAtEnd'=>$true,'showHead'=>false)));

						$ret = $tpl->get();
						$user->cacheSave($ret);
					}

				}

				FBuildPage::addTab(array("MAINDATA"=>$ret,"MAINID"=>'fotoBox'));
			}
		}
	}
}