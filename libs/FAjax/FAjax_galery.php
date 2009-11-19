<?php
class FAjax_galery extends FAjaxPluginBase {

	static function validate($data) {
		if($data['function']=='show') return true;
		return parent::validate($data);
	}

	static function show($data) {
		$itemId = $data['item'];
		if($data['__ajaxResponse']===true) {
			$user = FUser::getInstance();
			$user->itemVO = new ItemVO($itemId,true);
			page_GaleryDetail::build($data);	
		} else {
			FHTTP::redirect(FSystem::getUri('i='.$itemId,'',''));
		}
	}
	
	static function editThumb($data) {
		$user = FUser::getInstance();
		$pageId = $user->pageId;
				 
		$itemRenderer = new FItemsRenderer();
		$itemRenderer->setCustomTemplate( 'item.galery.edit.tpl.html' );
		$itemRenderer->thumbPreventCache = true;
		$itemRenderer->openPopup = true;
		
		$fItems = new FItems('galery',false,$itemRenderer);
		if(isset($data['item'])) {
			$itemId = (int) $data['item'];
			$fItems->setWhere("itemId='".$itemId."'");
			$ret = $fItems->render(0,1);
		} else {
			$pageVO = new PageVO($pageId,true);	
			$fItems->setWhere("pageId='".$pageId."' and itemIdTop is null");
			$fItems->setOrder($pageVO->itemsOrder());
			$fItems->getList((int) $data['seq'],1);
			$itemId = $fItems->data[0]->itemId;
			$ret = $fItems->render();
		}
		FAjax::addResponse($data['result'],$data['resultProperty'],$ret);
		FAjax::addResponse('function', 'call', 'draftSetEventListeners;fotodesc'.$itemId);
	}
	
	static function delete($data) {
		FGalery::removeFoto($data['itemId']);
	}
	
}