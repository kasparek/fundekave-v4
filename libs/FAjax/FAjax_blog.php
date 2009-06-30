<?php
class FAjax_blog {
	static function edit($data) {
		//TODO: check if user is logged else save draft data and return that user need to login, use popup, save draft
		
		FAjax::addResponse('editnew', 'html', FBlog::getEditForm($data['item']));
		FAjax::addResponse('function','call','draftSetEventListeners');
		//$fajax->addResponse('function','call','initInsertToTextarea');
		FAjax::addResponse('function','call','datePickerInit');
		FAjax::addResponse('function','call','fajaxform');
		FAjax::addResponse('function','call','markItUpInit');
	}
	static function submit($data) {
		//TODO: check if user is logged else save draft data and return that user need to login, use popup, save draft
		$itemId = FBlog::process( $data );
		
		FAjax::addResponse('bloged', 'html', FBlog::listAll($itemId,true));
		FAjax::addResponse('function','call','draftSetEventListeners');
		FAjax::addResponse('function','call','initInsertToTextarea');
		FAjax::addResponse('function','call','datePickerInit');
	}

}