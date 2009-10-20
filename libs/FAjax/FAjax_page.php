<?php
class FAjax_page {
	static function fuup($data) {
		$user = FUser::getInstance();
    //---call galery refresh
		$galery = new FGalery();
		$numNewFoto = $galery->refreshImgToDb($user->pageVO->pageId);
		if($numNewFoto > 0) {
			FAjax::addResponse('function','call','galeryLoadThumb;'.$numNewFoto);
		}
	}
	
	static function edit($data) {
		
		page_PageEdit::process($data);
		
	}

}