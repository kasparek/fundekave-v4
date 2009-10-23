<?php
class FAjax_page {
	static function fuup($data) {
		$user = FUser::getInstance();
    	//---call galery refresh
    	$cache = FCache::getInstance( 's' );
		$pageId = $cache->getData('pageId','selectedPage');
		
		$galery = new FGalery();
		$items = $galery->refreshImgToDb($pageId);
		
		$newStr = '';
		$updatedStr = '';
		if(isset($items['new'])) $newStr = implode(',',$items['new']);
		if(isset($items['updated'])) $updatedStr = implode(',',$items['updated']);
		FAjax::addResponse('function','call','galeryRefresh;'.$newStr.';'.$updatedStr.';'.$items['total']);
	}
	
	static function edit($data) {
		
		page_PageEdit::process($data);
		
	}

}