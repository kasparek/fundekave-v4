<?php
class FAjax_galery extends FAjaxPluginBase {
	static function editThumb($data) {
		$user = FUser::getInstance();
		$pageId = $user->pageId;
		$itemRenderer = new FItemsRenderer();
		$itemRenderer->setCustomTemplate( 'item.galery.edit.tpl.html' );
		$fItems = new FItems('galery',false,$itemRenderer);
		if(isset($data['item'])) {
			$itemId = (int) $data['item'];
			$fItems->setWhere("itemId='".$itemId."'");
			$ret = $fItems->render(0,1);
			if($data['resultProperty']=='$replaceWith') $count=-1;
			else $count=1;
		} else {
			$pageVO = new PageVO($pageId,true);
			$fItems->setWhere("pageId='".$pageId."' and (itemIdTop is null or itemIdTop=0)");
			$fItems->setOrder($pageVO->itemsOrder());
			$fItems->getList((int) $data['seq'],(int) $data['offset']);
			$count = count($fItems->data);
			$ret = $fItems->render();
		}
		FAjax::addResponse($data['result'],$data['resultProperty'],$ret);
		FAjax::addResponse('call','GaleryEdit.loadHandler',$count);
	}
}