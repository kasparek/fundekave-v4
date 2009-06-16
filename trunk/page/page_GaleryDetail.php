<?php
include_once('iPage.php');
class page_GaleryDetail implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		if($user->itemVO->itemId > 0) {
			$data['itemIdTop'] = $user->itemVO->itemId;
			FForum::process($data, "FGalery::callbackForumProcess");
		}

	}

	static function build() {
		$user = FUser::getInstance();
		$pageId = $user->pageVO->pageId;
		$userId = $user->userVO->userId;
		
		FItems::aFav($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->cnt);

		if(empty($user->itemVO->itemId)) {
			
			if(FRules::getCurrent(2)) {
				//---run just wher owner access
				$galery = new FGalery();
				$galery->refreshImgToDb($pageId);
			}
				
			$itemRenderer = new FItemsRenderer();
			$itemRenderer->showTooltip = false;

			$fItems = new FItems('galery',false,$itemRenderer);
			$fItems->setWhere('pageId="'.$pageId.'"');
			$fItems->addWhere('itemIdTop is null');
			$totalItems = $fItems->getCount();

			if($totalItems==0){
				FError::addError(ERROR_GALERY_NOFOTO);
				$user->pageAccess = false;
			} else {
				$perPage = $user->pageVO->perPage();

				if($user->pageVO->getPageParam('enhancedsettings/orderitems') == 0) {
					$fItems->setOrder('enclosure');	
				} else {
					$fItems->setOrder('dateCreated desc');	
				}

				$pager = FSystem::initPager($totalItems,$perPage);
				$od = ($pager->getCurrentPageID()-1) * $perPage;

				$fItems->openPopup = ($user->userVO->zgalerytype==0)?(false):(true);
				$fItems->getList($od,$perPage);

				//---nahledy
				$tpl = new FTemplateIT('galery.thumbnails.tpl.html');
				$tpl->setCurrentBlock("thumbnails");

				$tpl->setVariable("GALERYTEXT",$user->pageVO->description);
				$tpl->setVariable("GALERYHEAD",$user->pageVO->content);

				$x=0;
				while($fItems->data && $x < $perPage) {
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

				FBuildPage::addTab(array("MAINDATA"=>$tmptext,"MAINID"=>'fotoBox'));
			}
		} else {

			//---detail foto
			$itemVO = $user->itemVO->itemId;
			$itemVO->typeId = 'galery';
			$itemVO->hit();

			$cache = FCache::getInstance('s',120);
			if(($ret = $cache->getData($itemVO->itemId,'fotoDetail')) === false) {
					
				$itemVO->load();
					
				$pageVO = $user->pageVO;
					
				$onPageNum = $itemVO->onPageNum();

				$tpl = new FTemplateIT('galery.detail.tpl.html');
				$tpl->setVariable("LINKBACKTOP", FUser::getUri(FConf::get('pager','urlVar').'='.$onPageNum, $itemVO->pageId));

				$tpl->setVariable("IMGALT", $pageVO->name.' '.$itemVO->enclosure );
				$tpl->setVariable("IMGDIR", $itemVO->detailUrl );
					
				if(!empty($itemVO->text)) $tpl->setVariable("INFO",$itemVO->text);

				if(!empty($itemVO->addon)) {
					$user->pageVO->name = $this->addon . ' - ' . $user->pageVO->name;
				} else {
					$user->pageVO->name = $this->enclosure . ' - ' . $user->pageVO->name;
				}

				$tpl->setVariable("HITS",$itemVO->hits);
				if($user->idkontrol) {
					$tpl->setVariable('TAG',FItemTags::getTag($itemVO->itemId,$user->userVO->userId,'galery'));
					$tpl->setVariable('POCKET',FPocket::getLink($itemVO->itemId));
				}
					
				$itemRenderer = new FItemsRenderer();
				$itemRenderer->showRating = false;
				$itemRenderer->showTooltip = false;
				$itemRenderer->openPopup = false;
				$itemRenderer->showText = false;
				$itemRenderer->showTag = false;
				$itemRenderer->showPocketAdd = false;


				if(($itemVOPrev = $itemVO->getPrev())!==false) {
					$itemVOPrev->load();
					$tpl->setVariable("THUMBPREVIOUS",$itemVOPrev->render($itemRenderer));
				}
					
				if(($itemVONext = $itemVO->getNext())!==false) {
					$itemVONext->load();
					$tpl->setVariable("THUMBNEXT",$itemVOPrev->render($itemRenderer));

					$tpl->touchBlock('nextlinkclose');
					$tpl->setVariable('NEXTLINK',FUser::getUri('i='.$itemVONext->itemId));
				}

				//TODO: comments in galery are switched offf in this release
				//$tpl->setVariable('COMMENTS',FForum::show($itemId,$user->idkontrol,$fItems->itemIdInside,array('formAtEnd'=>$true,'showHead'=>false)));

				$ret = $tpl->get();
				$cache->setData($ret);
			}


			FBuildPage::addTab(array("MAINDATA"=>$ret,"MAINID"=>'fotoBox'));
		}

	}
}