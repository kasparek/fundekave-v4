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
		} else {
			$pageVO = new PageVO($pageId,true);
			$fItems->setWhere("pageId='".$pageId."' and (itemIdTop is null or itemIdTop=0)");
			$fItems->setOrder($pageVO->itemsOrder());
			$fItems->getList((int) $data['seq'],(int) $data['offset']);
			$count = count($fItems->data);
			$ret = $fItems->render();
			FAjax::addResponse('call', 'fotoFeeded', $count);
		}
		//TODO:f.add('result', 'fotoList');f.add('resultProperty', '$append'); if new or multiple
		//TODO:f.add('result', 'foto-'+item);f.add('resultProperty', '$replaceWith'); if updated
		FAjax::addResponse($data['result'],$data['resultProperty'],$ret);
	}
}