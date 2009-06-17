<?php
class FAjax_blog {
	static function edit($data) {
		//TODO: check if user is logged else save draft data and return that user need to login, use popup, save draft
		$fajax = FAjax::getInstance();
		$fajax->addResponse('editnew', 'html', FBlog::getEditForm($data['item']));
		$fajax->addResponse('function','call','draftSetEventListeners');
		//$fajax->addResponse('function','call','initInsertToTextarea');
		$fajax->addResponse('function','call','datePickerInit');
		$fajax->addResponse('function','call','fajaxform');
		$fajax->addResponse('function','call','markItUpInit');
	}
	static function submit($data) {
		//TODO: check if user is logged else save draft data and return that user need to login, use popup, save draft
		$itemId = FBlog::process( $data );
		$fajax = FAjax::getInstance();
		$fajax->addResponse('bloged', 'html', FBlog::listAll($itemId,true));
		$fajax->addResponse('function','call','draftSetEventListeners');
		$fajax->addResponse('function','call','initInsertToTextarea');
		$fajax->addResponse('function','call','datePickerInit');
	}

}