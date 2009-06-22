<?php
class FAjax_event {
	static function edit($data) {
		$user = FUser::getInstance();
		$user->itemVO->itemId = $data['item'];
		if($data['__ajaxResponse']==false) {
			return;
		}

		$fajax = FAjax::getInstance();
		
		$fajax->addResponse($data['result'], 'html', FEvents::editForm());

		$fajax->addResponse('function','call','draftSetEventListeners');
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