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
//		if(isset($_GET[$ppUrlVar])) $pageNum = (int) $_GET[$ppUrlVar];
//		$cache = FCache::getInstance('f',0);
//		$cacheKey = $pageId.'-'.$itemId.'-'.(int) $userId;
//		$cacheGrp = 'pagelist';
//		$ret = $cache->getData($cacheKey,$cacheGrp);
$ret=false;

		if($itemId===0) {
				
			$totalItems = (int) $user->pageVO->cnt;
				
			if($totalItems==0) {

				FError::addError(FLang::$ERROR_GALERY_NOFOTO);

			} else {
				if($ret===false) {
					$fItems = new FItems('galery',false);
					$fItems->setWhere('pageId="'.$pageId.'"');
					$fItems->addWhere('(itemIdTop is null or itemIdTop=0)');
					$fItems->setOrder($user->pageVO->itemsOrder());
					$fItems->getList();

					//---nahledy
					$tpl = FSystem::tpl('galery.thumbnails.tpl.html');
					if(!empty($user->pageVO->content)) {
						$tpl->setVariable("GALERYHEAD", FSystem::postText($user->pageVO->content));
					}
					$thumbs='';
					while($fItems->data) {// && $x < $perPage) {
						$fItems->parse();
						$thumbs .= $fItems->show();
					}
					$tpl->setVariable("THUMBNAILS",$thumbs);
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
				$itemVO = new ItemVO($itemId,true);
			}
			$itemVO->hit();
			$pageVO = $user->pageVO;
			$user->pageVO->showSidebar = false;
				
			$backUri = FSystem::getUri('', $itemVO->pageId);
			if(($itemNext = $itemVO->getNext(true))!==false) $nextUri = FSystem::getUri('m=galery-show&d=item:'.$itemNext,$pageId);
			if(($itemPrev = $itemVO->getPrev(true))!==false) $prevUri = FSystem::getUri('m=galery-show&d=item:'.$itemPrev,$pageId);

			if($ret===false) {
				$arrVars = array(
				"IMGALT"=>$pageVO->name.' '.$itemVO->enclosure,
				"IMGDIR"=>$itemVO->detailUrl,
				"HITS"=>$itemVO->hit,
				"COMMENTS"=>FForum::show($itemVO->itemId,$pageVO->prop('forumSet'),0,array('simple'=>true,'showHead'=>false))
				);
				if(!empty($itemVO->text)) $arrVars["INFO"] = $itemVO->text;
				if($user->idkontrol === true) {
					$arrVars['TAG'] = FItemTags::getTag($itemVO->itemId,$userId,'galery');
				}
			}
				
			//update page name
			$user->itemVO->htmlName = ($itemVO->getPos()+1) . '/' . $itemVO->getTotal();
			$user->pageVO->htmlName = $user->itemVO->htmlName . ' - ' . $user->pageVO->name;

			if(!empty($data['__ajaxResponse'])) {
				FAjax::addResponse('backButt','href',$backUri);
				FAjax::addResponse('prevButt','href',$prevUri);
				FAjax::addResponse('nextButt','href',$nextUri);
				
				FAjax::addResponse('detailNext','href',$nextUri);
				FAjax::addResponse('detailFoto','src',$itemVO->detailUrl);
				FAjax::addResponse('detailFoto','alt',$pageVO->name.' '.$itemVO->enclosure);
				if(isset($arrVars['TAG'])) FAjax::addResponse('tag','$html',$arrVars['TAG']);
				FAjax::addResponse('hit','$html',$itemVO->hit);
				FAjax::addResponse('description','$html',isset($arrVars['INFO'])?$arrVars['INFO']:'');
				FAjax::addResponse('comments','$html',isset($arrVars['COMMENTS'])?$arrVars['COMMENTS']:'');

				FAjax::addResponse('pageHead','$html',FBuildPage::getHeading());
				FAjax::addResponse('document','title',FBuildPage::getTitle());

				FAjax::addResponse('function','call','fajaxaInit');
			} else {
				$tpl = FSystem::tpl('galery.detail.tpl.html');
				$tpl->setVariable($arrVars);
				if(isset($nextUri)) {
					$tpl->touchBlock('nextlinkclose');
					$tpl->setVariable('NEXTLINK',$nextUri);
				}
				$ret = $tpl->get();
				if(isset($cacheKey)) $cache->setData($ret,$cacheKey,$cacheGrp);
				
				FMenu::secondaryMenuAddItem($backUri,FLang::$BUTTON_PAGE_BACK_ALBUM,0,'backButt');

				if($itemNext!==false) FMenu::secondaryMenuAddItem($nextUri,FLang::$BUTTON_PAGE_NEXT,0,'nextButt','fajaxa hash','opposite');
				if($itemPrev!==false) FMenu::secondaryMenuAddItem($prevUri,FLang::$BUTTON_PAGE_PREV,0,'prevButt','fajaxa hash','opposite');

				FBuildPage::addTab(array("MAINDATA"=>$ret,"MAINID"=>'fotoBox'));
			}
				
		}
		
		FItems::aFav($pageId,$userId);
		//TODO: what's this
		$unreadedCnt = $user->pageVO->cnt - $user->pageVO->favoriteCnt;
		if($unreadedCnt > 0) {
			$cacheGrp = 'pagelist';
			$mainCache = FCache::getInstance('f',0);
			$mainCache->invalidateGroup($cacheGrp);
		}
	}
}