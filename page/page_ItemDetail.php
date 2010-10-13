<?php
include_once('iPage.php');
class page_ItemDetail implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		if(empty($user->itemVO)) return false;
		$itemVO = $user->itemVO;
		if(empty($user->pageParam)) {
			if($itemVO->userId != $user->userVO->userId) {
				$itemVO->hit();
			}
		}
		if($itemVO->public!=1) {
			if(!FRules::getCurrent(2)) return;
		}
		//generic links
		$backUri = FSystem::getUri('', $itemVO->pageId,'');
		if(($itemNext = $itemVO->getNext(true))!==false) $nextUri = FSystem::getUri('m=item-show&d=item:'.$itemNext,$itemVO->pageId);
		if(($itemPrev = $itemVO->getPrev(true))!==false) $prevUri = FSystem::getUri('m=item-show&d=item:'.$itemPrev,$itemVO->pageId);
		
		//generic vars for all item details
		
		/**
		 *BLOG ITEM and EVENT ITEM
		 **/
		if($itemVO->typeId=='blog' || $itemVO->typeId=='event') {
			$itemVO->options['showDetail'] = true;
			$user->pageVO->htmlTitle = $itemVO->addon.' - '.$user->pageVO->name;
			//$user->pageVO->htmlName = $itemVO->addon;
			$user->pageVO->showHeading=false;
			$itemRender = $itemVO->render();
			if(!empty($data['__ajaxResponse'])) {
			  FAjax::addResponse('itemDetail','$html',$itemRender);
			} else {
				$output = $itemRender;
			}
		} 		 		
		
		/**
		 *GALERY ITEM
		 **/		 		
		if($itemVO->typeId=='galery') {
			$arrVars = array(
				"IMGALT"=>$itemVO->enclosure,
				"IMGTITLE"=>$itemVO->pageVO->name.' '.$itemVO->enclosure,
				"IMGDIR"=>$itemVO->detailUrl,
				"HITS"=>$itemVO->hit,
				"TAG"=>FItemTags::getTag($itemVO->itemId,$user->userVO->userId,'galery'),
				"TEXT"=>(!empty($itemVO->text) ? $itemVO->text : null),
				"NEXTLINK"=>isset($nextUri) ? $nextUri : $backUri,
				//comment via pageitemlist "COMMENTS"=>page_PageItemList::build(array('itemId'=>$itemVO->itemId)) //TODO: build comments only if there are any or write perm
				);
			//no sidebar	
			$user->pageVO->showSidebar = false;
			$user->itemVO->htmlName = ($itemVO->getPos()+1) . '/' . $itemVO->getTotal();
			$user->pageVO->htmlTitle = $user->itemVO->htmlName . ' ' . $user->pageVO->htmlTitle; 
			$user->pageVO->showHeading=false;
			if(!empty($data['__ajaxResponse'])) {
				//next image
				$nextVO = new ItemVO($itemNext,true);
				FAjax::addResponse('call','galeryNextUrl',$itemVO->detailUrl.','.$nextVO->detailUrl);
				FAjax::addResponse('backButt','href',$backUri);
				FAjax::addResponse('prevButt','href',isset($prevUri) ? $prevUri : $backUri);
				FAjax::addResponse('nextButt','href',isset($nextUri) ? $nextUri : $backUri);
				FAjax::addResponse('detailNext','href',isset($nextUri) ? $nextUri : $backUri);
				FAjax::addResponse('tag','$html',$arrVars['TAG']);
				FAjax::addResponse('hit','$html',$itemVO->hit);
				FAjax::addResponse('description','$html',isset($arrVars['INFO'])?$arrVars['INFO']:'');
			} else {
				$tpl = FSystem::tpl('galery.detail.tpl.html');
				$tpl->setVariable($arrVars);
				$output = $tpl->get();
			}
		}
		//---GALERY END
				
		if(!empty($output)) {
			FMenu::secondaryMenuAddItem($backUri,FLang::$BUTTON_PAGE_BACK,0,array('id'=>'backButt'));
			if($itemNext!==false) FMenu::secondaryMenuAddItem($nextUri,FLang::$BUTTON_PAGE_NEXT,array('id'=>'nextButt','class'=>'fajaxa hash keepscroll galerynext','parentClass'=>'opposite'));
			if($itemPrev!==false) FMenu::secondaryMenuAddItem($prevUri,FLang::$BUTTON_PAGE_PREV,array('id'=>'prevButt','class'=>'fajaxa hash keepscroll progress','parentClass'=>'opposite'));
			return $output;
		} 	
	}
}