<?php
class FAjax_items {
	static function form($data) {
		//TODO: check if user is logged else save draft data and return that user need to login, use popup, save draft
		$fajax = FAfax::getInstance();
		$fajax->addResponse('editnew', 'html', FBlog::getEditForm($data['item']));
		$fajax->addResponse('function','call','draftSetEventListeners');
		$fajax->addResponse('function','call','initInsertToTextarea');
		$fajax->addResponse('function','call','datePickerInit');
	}
	static function submit($data) {
		//TODO: check if user is logged else save draft data and return that user need to login, use popup, save draft
		$itemId = FBlog::process( $data );
		$fajax = FAfax::getInstance();
		$fajax->addResponse('bloged', 'html', FBlog::listAll($itemId,true));
		$fajax->addResponse('function','call','draftSetEventListeners');
		$fajax->addResponse('function','call','initInsertToTextarea');
		$fajax->addResponse('function','call','datePickerInit');
	}

}