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
		$itemId = $itemVO->itemId;

		$isComment = false;
		if(isset($data['__get']['do']) && $data['__get']['do']=='comment') $isComment = true;

		//generic links
		$backUri = FSystem::getUri('', $itemVO->pageId,'');

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
				"TEXT"=>(!empty($itemVO->text) ? $itemVO->text : null),
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
				FAjax::addResponse('backButt','href',$backUri);
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

				if($isComment === true) {
					ImageConfig::$sideDefault = 800;
					$tpl->setVariable('BS_COLS','12');//'6'); //TODO: adopt template to flow content
					$user->itemVO->prepare();
					$items = array($user->itemVO);
					$itemIds = array($user->itemVO->itemId);
				} else {
					$tpl->setVariable('BS_COLS','12');
					//get all the images
					$fItems = new FItems('galery',$user->userVO->userId);
					$fItems->addWhere("pageId = '". $user->pageVO->pageId ."'");
					$fItems->setOrder($user->pageVO->itemsOrder());
					$items = $fItems->getList();

					$itemIds = array();
					foreach ($items as $item) {
						$itemIds[] = $item->itemId;
					}
					if($items[0]->itemId != $itemVO->itemId) {
						//sort out so detail is first
						while($items[0]->itemId != $itemVO->itemId) {
							array_push($items,array_shift($items));
						}
					}
				}

				//get comments
				$fComments = new FDBTool('sys_pages_items','itemId');
				$fComments->setSelect('itemIdTop,count(itemIdTop)');
				$fComments->addWhere("itemIdTop in ('".implode("','", $itemIds)."')");
				$fComments->setGroup("itemIdTop");
				$fComments->setOrder("dateCreated desc");
				$c = FDBTool::getAll($fComments->buildQuery());
				$comments = array();
				foreach ($c as $row) {
					$comments[$row[0]] = $row[1];
				}

				$c=0;
				$index = 0;
				$isFirst = true;
				foreach ($items as $key => $item) {
					//var_dump($item);die();
					$isFirst = false;
					$tpl->setVariable('ITEMID',$item->itemId);
					$tpl->setVariable('NUM_COMMENTS',isset($comments[$item->itemId])?$comments[$item->itemId]:'');
					if(!empty($item->text)) {
						$tpl->setVariable('IMGDESC',$item->text);
						$tpl->setVariable('ITEMIDDESC',$item->itemId);
					}
					if(FRules::getCurrent(2)) {
						$tpl->setVariable('ITEMIDDESC',$item->itemId);
						$tpl->setVariable('ITEMIDEDIT',$item->itemId);
					}
					if($item->itemId == $user->itemVO->itemId) {
						$tpl->setVariable('IMGURL',$item->detailUrl);
					} else {
						$tpl->setVariable('IMGURL',URL_CSS.'images/1px.png');
						$tpl->setVariable('IMGURLLAZY',$item->detailUrl);
					}
					$tpl->setVariable('TEXT',$item->text);
					$tpl->parse('cell');
					$index++;
					if($isComment) break;
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