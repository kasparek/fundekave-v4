<?php
include_once('iPage.php');
class page_ItemDetail implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		if(!empty($data['item'])) $data['i']=$data['item'];
		if(!empty($data['i'])) {
			$itemVO = new ItemVO($data['i'] * 1);
			if(!$itemVO->load()) $itemVO=null;
		}
		if(empty($itemVO)) {
			if(empty($user->itemVO)) return false;
			$itemVO = $user->itemVO;
		}
		
		if($itemVO->public>1) {
			if(!FRules::getCurrent(2)) {
				//user does not have access to given item
				return;
			}
		}
		
		//generic links
		$backUri = FSystem::getUri('', $itemVO->pageId,'');
		if(($itemNext = $itemVO->getNext(true,$itemVO->typeId=='galery'))!==false) $nextUri = FSystem::getUri('i='.$itemNext,$itemVO->pageId);
		if(($itemPrev = $itemVO->getPrev(true,$itemVO->typeId=='galery'))!==false) $prevUri = FSystem::getUri('i='.$itemPrev,$itemVO->pageId);
		
		//generic vars for all item details
		/**
		 *BLOG ITEM and EVENT ITEM
		 **/
		if($itemVO->typeId!='galery') {
			$itemVO->options['showDetail'] = true;
			$user->pageVO->htmlTitle = $itemVO->addon.' - '.$user->pageVO->name;
			$user->pageVO->showHeading=false;
			$itemRender = $itemVO->render();
			if($data['__ajaxResponse']) {
				FAjax::addResponse('itemDetail','$html',$itemRender);
				//TODO: fixFAjax::addResponse('prevButt','href',isset($prevUri) ? $prevUri : $backUri);
				//TODO: fixFAjax::addResponse('nextButt','href',isset($nextUri) ? $nextUri : $backUri);
			} else {
				$output = $itemRender;
			}
		} else {
		/**
		 *GALERY ITEM
		 **/
			$goomapThumb = FItemsRenderer::gmaps($itemVO,true);
			$goomap = FItemsRenderer::gmaps($itemVO);
			$arrVars = array(
				"PAGEDESCRIPTION"=>$user->pageVO->content,
				"TEXT"=>$itemVO->text,
				"IMGALT"=>$itemVO->enclosure,
				"IMGTITLE"=>$itemVO->pageVO->name.' '.$itemVO->enclosure,
				"IMGDIR"=>$itemVO->detailUrl,
				"HITS"=>$itemVO->hit,
				"ALBUMURL"=>$backUri,
				"PREVBUTT"=>$nextUri,
				"NEXTBUTT"=>$prevUri,
				"TEXT"=>(!empty($itemVO->text) ? $itemVO->text : null),
				"NEXTLINK"=>isset($nextUri) ? $nextUri : $backUri,
				"GOOMAPTHUMB"=>$goomapThumb,
				"GOOMAP"=>$goomap,
			);
			//no sidebar	
			$user->pageVO->tplVars['NUMCOLMAIN'] = 12;
			$user->pageVO->showSidebar = false;
			$user->itemVO->htmlName = ($itemVO->getPos()+1) . '/' . $itemVO->getTotal();
			$user->pageVO->htmlTitle = $user->itemVO->htmlName .' - '.$user->pageVO->name; 
			//$user->pageVO->showHeading=false;
			if($data['__ajaxResponse']) {
				//next image
				$nextVO = new ItemVO($itemNext,true);
				FAjax::addResponse('call','ImgNext.xhrHand',$itemVO->itemId.','.$nextVO->itemId.','.$itemPrev.','.$itemVO->detailUrl.','.$nextVO->detailUrl);
				FAjax::addResponse('backButt','href',$backUri);
				FAjax::addResponse('prevButt','href',$nextUri);
				FAjax::addResponse('nextButt','href',$prevUri);
				FAjax::addResponse('detailNext','href',isset($nextUri) ? $nextUri : $backUri);
				FAjax::addResponse('ti','value',(int) $user->itemVO->itemId);
				FAjax::addResponse('description','$html',isset($arrVars['TEXT'])?$arrVars['TEXT']:'');
				FAjax::addResponse('mapThumb','$html',$arrVars['GOOMAPTHUMB']);
				FAjax::addResponse('map','$html',$arrVars['GOOMAP']);
			} else {
				$tpl = FSystem::tpl('galery.detail.tpl.html');
				$tpl->setVariable($arrVars);
				$output = $tpl->get();
			}
		}
		//---GALERY END
		
		if(!empty($output)) {
			return $output;
		} 	
	}
}