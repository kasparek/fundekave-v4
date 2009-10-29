<?php
class FAjax_galery extends FAjaxPluginBase {
	static function editThumb($data) {
		$user = FUser::getInstance();
		$pageId = $user->pageId;
				 
		$itemRenderer = new FItemsRenderer();
		$itemRenderer->setCustomTemplate( 'item.galery.edit.tpl.html' );
		$itemRenderer->thumbPreventCache = true;
		
		$fItems = new FItems('galery',false,$itemRenderer);
		if(isset($data['item'])) {
			$fItems->setWhere("itemId='".$data['item']."'");
			$ret = $fItems->render(0,1);	
		} else {
			$pageVO = new PageVO($pageId,true);	
			$fItems->setWhere("pageId='".$pageId."'");
			$fItems->setOrder($pageVO->itemsOrder());
			$ret = $fItems->render((int) $data['seq'],1);
		}
		FAjax::addResponse($data['result'],$data['resultProperty'],$ret);
	}
	static function delete($data) {
		FGalery::removeFoto($data['itemId']);
	}
}