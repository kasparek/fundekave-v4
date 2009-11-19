<?php
include_once('iPage.php');
class page_GaleryDetail implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		if($user->itemVO) {
			$data['itemIdTop'] = $user->itemVO->itemId;
			FForum::process($data, "FGalery::callbackForumProcess");
		}

	}

	static function build($data=array()) {
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
			$itemRenderer->openPopup = ($user->userVO->zgalerytype == 0)?(false):(true);

			$fItems = new FItems('galery',false,$itemRenderer);
			$fItems->setWhere('pageId="'.$pageId.'"');
			$fItems->addWhere('itemIdTop is null');
			$totalItems = $fItems->getCount();

			if($totalItems==0){

				FError::addError(FLang::$ERROR_GALERY_NOFOTO);

			} else {
				
				$perPage = $user->pageVO->perPage();

				$fItems->setOrder($user->pageVO->itemsOrder());

				$pager = new FPager($totalItems,$perPage);
				$od = ($pager->getCurrentPageID()-1) * $perPage;

				$fItems->getList($od,$perPage);

				//---nahledy
				$tpl = FSystem::tpl('galery.thumbnails.tpl.html');

				$x=0;
				while($fItems->data && $x < $perPage) {
					$fItems->parse();
					$tpl->setVariable("THUMBNAIL",$fItems->show());
					$tpl->parse('cell');
				}
				
				$tpl->setVariable("GALERYTEXT",$user->pageVO->description);
				$tpl->setVariable("GALERYHEAD",$user->pageVO->content);
				
				if($perPage < $totalItems) {
					$tpl->setVariable("PAGERSTART",$pager->links);
					$tpl->setVariable("PAGEREND",$pager->links);
				}
				$tpl->parse('thumbnails');

				$tmptext=$tpl->get();

				FBuildPage::addTab(array("MAINDATA"=>$tmptext,"MAINID"=>'fotoBox'));
			}
		} else {

			//---detail foto
			$itemId = $user->itemVO->itemId;
			
			$itemVO = new ItemVO($itemId,true,array('typeId'=>'galery'));
			$itemVO->hit();

			$pageVO = $user->pageVO;
				
			$onPageNum = $itemVO->onPageNum();

			$tpl = FSystem::tpl('galery.detail.tpl.html');

			$tpl->setVariable("IMGALT", $pageVO->name.' '.$itemVO->enclosure );
			$tpl->setVariable("IMGDIR", $itemVO->detailUrl );
			if(!empty($itemVO->text)) $tpl->setVariable("INFO",$itemVO->text);
						
			$tpl->setVariable("HITS",$itemVO->hit);
			if($user->idkontrol===true) {
				$tpl->setVariable('TAG',FItemTags::getTag($itemVO->itemId,$user->userVO->userId,'galery'));
				$tpl->setVariable('POCKET',FPocket::getLink($itemVO->itemId));
			}
			if(($itemNext = $itemVO->getNext(true))!==false) {
				
				$nextUri = FSystem::getUri('m=galery-show&d=item:'.$itemNext,$pageId);
				
				$tpl->touchBlock('nextlinkclose');
				$tpl->setVariable('NEXTLINK',$nextUri);
			}
			if(($itemPrev = $itemVO->getPrev(true))!==false) $prevUri = FSystem::getUri('m=galery-show&d=item:'.$itemPrev,$pageId);
			$backUri = FSystem::getUri(FConf::get('pager','urlVar').'='.$onPageNum, $itemVO->pageId);
			
			//TODO: deeplinking for comments
			$itemIdForum = 0;
			$tpl->setVariable('COMMENTS',FForum::show($itemVO->itemId,$pageVO->prop('forumSet'),$itemIdForum,array('simple'=>true,'showHead'=>false)));
			$ret = $tpl->get();
			
			//update page name
			$user->pageVO->name = ($itemVO->getPos()+1) . ' / ' . $itemVO->getTotal() . ' - ' . $user->pageVO->name;
						
			if(!empty($data['__ajaxResponse'])) {
				FAjax::addResponse('backButt','href',$backUri);
				FAjax::addResponse('prevButt','href',$prevUri);
				FAjax::addResponse('nextButt','href',$nextUri);
				
				FAjax::addResponse('pageHead','$html',FBuildPage::getHeading());
				FAjax::addResponse('document','title',FBuildPage::getTitle());
				
				FAjax::addResponse('fotoBox','$html',$ret);
				FAjax::addResponse('function','call','fajaxa');
			} else {
				FMenu::secondaryMenuAddItem($backUri,FLang::$BUTTON_PAGE_BACK,0,'backButt');
				if($itemPrev!==false) FMenu::secondaryMenuAddItem($prevUri,FLang::$BUTTON_PAGE_PREV,0,'prevButt','fajaxa hash showBusy');
				if($itemNext!==false) FMenu::secondaryMenuAddItem($nextUri,FLang::$BUTTON_PAGE_NEXT,0,'nextButt','fajaxa hash showBusy');
				
				FBuildPage::addTab(array("MAINDATA"=>$ret,"MAINID"=>'fotoBox'));
			}
		}

	}
}