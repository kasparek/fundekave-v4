<?php
class FAjax_blog {
	static function edit($data) {
		//TODO: check if user is logged else save draft data and return that user need to login, use popup, save draft
		
		FAjax::addResponse('editnew', 'html', FBlog::getEditForm($data['item']));
		FAjax::addResponse('function','call','draftSetEventListeners');
		//$fajax->addResponse('function','call','initInsertToTextarea');
		
		FAjax::addResponse('function','getScript','js/jquery-ui.datepicker.js');
		FAjax::addResponse('function','css','js/markitup/skins/markitup/style.css');
		FAjax::addResponse('function','css','js/markitup/sets/default/style.css');
		FAjax::addResponse('function','getScript','js/markitup/jquery.markitup.pack.js');
		FAjax::addResponse('function','getScript','js/markitup/sets/default/set.js;markItUpInit');
		
		FAjax::addResponse('function','getScript','js/jquery-ui.datepicker.js;datePickerInit');
		FAjax::addResponse('function','getScript','js/i18n/ui.datepicker-cs.js');
		FAjax::addResponse('function','css','css/themes/base/ui.all.css');
		
		FAjax::addResponse('function','getScript','js/jquery.uploadify.js;uploadifyInit');
		FAjax::addResponse('function','call','fajaxform');
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