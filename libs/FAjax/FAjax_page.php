<?php
class FAjax_page {
	static function fuup($data) {
		$user = FUser::getInstance();
    	//---call galery refresh
    	$cache = FCache::getInstance( 's' );
		$pageId = $cache->getData('pageId','selectedPage');
		
		$galery = new FGalery();
		$items = $galery->refreshImgToDb($pageId);
		
		if(isset($items['new'])) FAjax::addResponse('function','call','alert;'.implode(',',$items['new']));
		if(isset($items['updated'])) FAjax::addResponse('function','call','alert;'.implode(',',$items['updated']));
		//FAjax::addResponse('function','call','galeryLoadThumb;'.$totalFoto);
	}
	
	static function edit($data) {
		
		page_PageEdit::process($data);
		
	}

}