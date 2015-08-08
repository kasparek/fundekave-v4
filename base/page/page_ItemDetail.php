<?php
include_once('iPage.php');
class page_ItemDetail implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$itemVO = null;
		if(!empty($data['item'])) $data['i']=$data['item'];
		if(!empty($user->itemVO)) $itemVO = $user->itemVO;
		if(!empty($data['i']) && $data['i']!=$itemVO->itemId) $itemVO = FactoryVO::get('ItemVO',(int) $data['i'],true);
		if(empty($itemVO)) return false;
		
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
			if(!empty($data['__ajaxResponse'])) {
				FAjax::addResponse('itemDetail','$html',$itemRender);
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
				FAjax::addResponse('editForm','$html','');
				FAjax::addResponse('detailFotoSrc','href',$itemVO->detailUrl);
				
				//$tpl = FSystem::tpl('galery.detail.tpl.html');
				//$tpl->setVariable($arrVars);
				//$output = $tpl->get();
				//FAjax::addResponse('itemDetail','$html',$output);
			} else {
				$tpl = FSystem::tpl('galery.detail.tpl.html');
				$tpl->setVariable($arrVars);

				//get all the images
				//TODO: set active based on item
				$fItems = new FItems('galery',$user->userVO->userId);
				$fItems->addWhere("pageId = '". $user->pageVO->pageId ."'");
				$fItems->setOrder($user->pageVO->itemsOrder());
				$items = $fItems->getList();
				$c=0;
				foreach ($items as $key => $item) {
					$tpl->setVariable('ITEMID',$item->itemId);
					$tpl->setVariable('TEXT',$item->text);
					$tpl->setVariable('IMGURL',$item->detailUrl);
					//$tpl->setVariable('IMGURL',$item->thumbUrl);
					$tpl->parse('cell');
				}



				$output = $tpl->get();
			}
		}
		//---GALERY END
		
		if(!empty($output)) {
			return $output;
		} 	
	}
}