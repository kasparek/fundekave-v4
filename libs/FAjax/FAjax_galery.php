<?php
class FAjax_galery {
	static function editThumb($data) {
		
		$user = FUser::getInstance();
		$pageVO = $user->pageVO; 
		$itemRenderer = new FItemsRenderer();
		$itemRenderer->setCustomTemplate( 'item.galery.edit.tpl.html' );
		
		$fItems = new FItems('galery',false,$itemRenderer);
		$fItems->setWhere("pageId='".$pageVO->pageId."'");
		$fItems->setOrder($pageVO->itemsOrder());
		$ret = $fItems->render((int) $data['seq'],1);
		
		FAjax::addResponse($data['result'],$data['resultProperty'],$ret);
	}
	static function delete($data) {
		FGalery::removeFoto($data['itemId']);
	}
}