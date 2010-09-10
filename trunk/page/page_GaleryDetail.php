<?php
include_once('iPage.php');
class page_GaleryDetail implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		if($user->itemVO) {
			$data['itemIdTop'] = $user->itemVO->itemId;
			FForum::process($data);
		}

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$pageId = $user->pageVO->pageId;
		$userId = $user->userVO->userId;
		$itemId=0;
		if($user->itemVO) {
			if($user->itemVO->itemId > 0) {
				$itemId = $user->itemVO->itemId;	
			}	
		}
				
		$ret = false;
		
		//---try from cache
		$ppUrlVar = FConf::get('pager','urlVar');
		$pageNum = 1;
		if(isset($_GET[$ppUrlVar])) $pageNum = (int) $_GET[$ppUrlVar];
		$cache = FCache::getInstance('f',0);
		$cacheKey = $pageId.'-'.$pageNum.'-'.$itemId.'-'.(int) $userId;
		$cacheGrp = 'pagelist';
		//$ret = $cache->getData($cacheKey,$cacheGrp);
		
		if($itemId===0) {
				
			if(FRules::getCurrent(2)) {
				//---run just wher owner access
				$galery = new FGalery();
				$galery->refreshImgToDb($pageId);
			}
			
			$totalItems = (int) $user->pageVO->cnt;
			
			if($totalItems==0) {

				FError::addError(FLang::$ERROR_GALERY_NOFOTO);

			} else {
			  if($ret===false) {
					$itemRenderer = new FItemsRenderer();
					$itemRenderer->openPopup = false; //no more popups ($user->userVO->zgalerytype == 0)?(false):(true);
				
					$fItems = new FItems('galery',false,$itemRenderer);
					$fItems->setWhere('pageId="'.$pageId.'"');
					$fItems->addWhere('!itemIdTop');
					$fItems->setOrder($user->pageVO->itemsOrder());
					
					$perPage = $user->pageVO->perPage();
	
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
					if(!empty($user->pageVO->content)) {
						$tpl->setVariable("GALERYHEAD", FSystem::postText($user->pageVO->content));
					}
					
					if($perPage < $totalItems) {
						$tpl->setVariable("PAGERSTART",$pager->links);
						$tpl->setVariable("PAGEREND",$pager->links);
					}
					$tpl->parse('thumbnails');
					$ret = $tpl->get();
					if(isset($cacheKey)) $cache->setData($ret,$cacheKey,$cacheGrp);
				}
				FBuildPage::addTab(array("MAINDATA"=>$ret,"MAINID"=>'fotoBox'));
			}
		} else {
			
			//---detail foto
			if($user->itemVO) {
				if($itemId == $user->itemVO->itemId) {
					$itemVO = $user->itemVO;		
				}
			}
			
			if(!$itemVO) {
				$itemVO = new ItemVO($itemId,true,array('typeId'=>'galery'));
			}
			
			$itemVO->hit();
						
			$pageVO = $user->pageVO;
			$onPageNum = $itemVO->onPageNum();
			
			$backUri = FSystem::getUri(FConf::get('pager','urlVar').'='.$onPageNum, $itemVO->pageId);
			if(($itemNext = $itemVO->getNext(true))!==false) $nextUri = FSystem::getUri('m=galery-show&d=item:'.$itemNext,$pageId);
			if(($itemPrev = $itemVO->getPrev(true))!==false) $prevUri = FSystem::getUri('m=galery-show&d=item:'.$itemPrev,$pageId);

			if($ret===false) {
				$tpl = FSystem::tpl('galery.detail.tpl.html');
				$tpl->setVariable("IMGALT", $pageVO->name.' '.$itemVO->enclosure );
				$tpl->setVariable("IMGDIR", $itemVO->detailUrl );
				if(!empty($itemVO->text)) $tpl->setVariable("INFO",$itemVO->text);
				$tpl->setVariable("HITS",$itemVO->hit);
				$tpl->setVariable("ITEMEYEDIR",FSystem::getSkinCSSFilename() );

				if($user->idkontrol === true) {
					$tpl->setVariable('TAG',FItemTags::getTag($itemVO->itemId,$userId,'galery'));
					//$tpl->setVariable('POCKET',FPocket::getLink($itemVO->itemId));
				}
				
				if(isset($nextUri)) {
					$tpl->touchBlock('nextlinkclose');
					$tpl->setVariable('NEXTLINK',$nextUri);
				}
				
				$itemIdForum = 0;
				$tpl->setVariable('COMMENTS',FForum::show($itemVO->itemId,$pageVO->prop('forumSet'),$itemIdForum,array('simple'=>true,'showHead'=>false)));
				
				$ret = $tpl->get();
				if(isset($cacheKey)) $cache->setData($ret,$cacheKey,$cacheGrp);
			} 
			
			//update page name
			$user->itemVO->htmlName = ($itemVO->getPos()+1) . '/' . $itemVO->getTotal();
			$user->pageVO->htmlName = $user->itemVO->htmlName . ' - ' . $user->pageVO->name;
						
			if(!empty($data['__ajaxResponse'])) {
				FAjax::addResponse('backButt','href',$backUri);
				FAjax::addResponse('prevButt','href',$prevUri);
				FAjax::addResponse('nextButt','href',$nextUri);
				
				FAjax::addResponse('pageHead','$html',FBuildPage::getHeading());
				FAjax::addResponse('document','title',FBuildPage::getTitle());
				
				FAjax::addResponse('fotoBox','$html',$ret);
				FAjax::addResponse('function','call','fajaxa');
			} else {
				FMenu::secondaryMenuAddItem($backUri,FLang::$BUTTON_PAGE_BACK_ALBUM,0,'backButt');
				
				if($itemNext!==false) FMenu::secondaryMenuAddItem($nextUri,FLang::$BUTTON_PAGE_NEXT,0,'nextButt','fajaxa hash showBusy','opposite');
				if($itemPrev!==false) FMenu::secondaryMenuAddItem($prevUri,FLang::$BUTTON_PAGE_PREV,0,'prevButt','fajaxa hash showBusy','opposite');
				
				FBuildPage::addTab(array("MAINDATA"=>$ret,"MAINID"=>'fotoBox'));
			}
			
		}
		FItems::aFav($pageId,$userId);
		$unreadedCnt = $user->pageVO->cnt - $user->pageVO->favoriteCnt;
		if($unreadedCnt > 0) {
			$cacheGrp = 'pagelist';
			$mainCache = FCache::getInstance('f',0);
			$mainCache->invalidateGroup($cacheGrp);
		}
	}
}