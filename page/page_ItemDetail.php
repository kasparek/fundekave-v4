<?php
include_once('iPage.php');
class page_ItemDetail implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		if(isset($data['item'])) $data['i']=$data['item'];
		if(isset($data['i'])) {
			$id = (int) $data['i'];
			$itemVO = new ItemVO($id);
			if(!$itemVO->load()) $itemVO=null;
		}
		if(empty($itemVO)) {
			if(empty($user->itemVO)) return false;
			$itemVO = $user->itemVO;
		}
		if($itemVO->public!=1) {
			if(!FRules::getCurrent(2)) return;
		}
		//generic links
		$backUri = FSystem::getUri('', $itemVO->pageId,'');
		if(($itemNext = $itemVO->getNext(true,$itemVO->typeId=='galery'))!==false) $nextUri = FSystem::getUri('m=item-show&d=item:'.$itemNext,$itemVO->pageId);
		if(($itemPrev = $itemVO->getPrev(true,$itemVO->typeId=='galery'))!==false) $prevUri = FSystem::getUri('m=item-show&d=item:'.$itemPrev,$itemVO->pageId);
		
		//generic vars for all item details
		
		/**
		 *BLOG ITEM and EVENT ITEM
		 **/
		if($itemVO->typeId!='galery') {
			$itemVO->options['showDetail'] = true;
			$user->pageVO->htmlTitle = $itemVO->addon.' - '.$user->pageVO->name;
			$user->pageVO->showHeading=false;
			$itemRender = $itemVO->render();
			if(!empty($data['__ajaxResponse'])) {
			  FAjax::addResponse('itemDetail','$html',$itemRender);
			  FAjax::addResponse('backButt','href',$backUri);
				FAjax::addResponse('prevButt','href',isset($prevUri) ? $prevUri : $backUri);
				FAjax::addResponse('nextButt','href',isset($nextUri) ? $nextUri : $backUri);
			} else {
				$output = $itemRender;
			}
		} 		 		
		
		/**
		 *GALERY ITEM
		 **/		 		
		if($itemVO->typeId=='galery') {
			$arrVars = array(
				"TEXT"=>$itemVO->text,
				"IMGALT"=>$itemVO->enclosure,
				"IMGTITLE"=>$itemVO->pageVO->name.' '.$itemVO->enclosure,
				"IMGDIR"=>$itemVO->detailUrl,
				"HITS"=>$itemVO->hit,
				"TAG"=>FItemTags::getTag($itemVO->itemId,$user->userVO->userId,'galery'),
				"TEXT"=>(!empty($itemVO->text) ? $itemVO->text : null),
				"NEXTLINK"=>isset($nextUri) ? $nextUri : $backUri,
			);
			$arrVars = array_merge($arrVars,FItemsRenderer::gmaps($itemVO));
			//no sidebar	
			$user->pageVO->showSidebar = false;
			$user->itemVO->htmlName = ($itemVO->getPos()+1) . '/' . $itemVO->getTotal();
			$user->pageVO->htmlTitle = $user->itemVO->htmlName .' - '.$user->pageVO->name; 
			$user->pageVO->showHeading=false;
			if(!empty($data['__ajaxResponse'])) {
				//next image
				$nextVO = new ItemVO($itemNext,true);
				FAjax::addResponse('call','ImgNext.xhrHand',$itemVO->detailUrl.','.$nextVO->detailUrl);
				FAjax::addResponse('backButt','href',$backUri);
				FAjax::addResponse('prevButt','href',isset($prevUri) ? $prevUri : $backUri);
				FAjax::addResponse('nextButt','href',isset($nextUri) ? $nextUri : $backUri);
				FAjax::addResponse('detailNext','href',isset($nextUri) ? $nextUri : $backUri);
				FAjax::addResponse('tag','$html',$arrVars['TAG']);
				FAjax::addResponse('hit','$html',$itemVO->hit);
				FAjax::addResponse('description','$html',isset($arrVars['TEXT'])?$arrVars['TEXT']:'');
				$tpl = FSystem::tpl('galery.detail.tpl.html');
				$tpl->setVariable($arrVars);
				$tpl->parse('map');
				FAjax::addResponse('map','$html',$tpl->get('map'));
			} else {
				$tpl = FSystem::tpl('galery.detail.tpl.html');
				$tpl->setVariable($arrVars);
				$output = $tpl->get();
			}
		}
		//---GALERY END
				
		if(!empty($output)) {
			FMenu::secondaryMenuAddItem($backUri,FLang::$BUTTON_PAGE_BACK,0,array('id'=>'backButt'));
			if($itemVO->typeId!='galery') {
				if($itemPrev!==false) FMenu::secondaryMenuAddItem($prevUri, FLang::$BUTTON_PAGE_NEWER ,array('id'=>'prevButt','class'=>$itemVO->typeId=='galery'?'fajaxa progress hash keepscroll':'','parentClass'=>'opposite'));
				if($itemNext!==false) FMenu::secondaryMenuAddItem($nextUri, FLang::$BUTTON_PAGE_OLDER ,array('id'=>'nextButt','class'=>$itemVO->typeId=='galery'?'hash keepscroll galerynext':'','parentClass'=>'opposite'));
			} else {
				if($itemNext!==false) FMenu::secondaryMenuAddItem($nextUri, $itemVO->typeId!='galery' ? FLang::$BUTTON_PAGE_NEWER : FLang::$BUTTON_PAGE_NEXT,array('id'=>'nextButt','class'=>$itemVO->typeId=='galery'?'hash keepscroll galerynext':'','parentClass'=>'opposite'));
				if($itemPrev!==false) FMenu::secondaryMenuAddItem($prevUri, $itemVO->typeId!='galery' ? FLang::$BUTTON_PAGE_OLDER : FLang::$BUTTON_PAGE_PREV,array('id'=>'prevButt','class'=>$itemVO->typeId=='galery'?'fajaxa progress hash keepscroll':'','parentClass'=>'opposite'));
			}
			return $output;
		} 	
	}
}