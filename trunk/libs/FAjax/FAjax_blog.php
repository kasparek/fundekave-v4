<?php
class FAjax_blog extends FAjaxPluginBase {
	static function edit($data) {
		//TODO: check if user is logged else save draft data and return that user need to login, use popup, save draft
		
		FAjax::addResponse('editnew', 'html', FBlog::getEditForm($data['item']));
		FAjax::addResponse('function','call','draftSetEventListeners');
				
		FAjax::addResponse('function','css','js/markitup/skins/markitup/style.css');
		FAjax::addResponse('function','css','js/markitup/sets/default/style.css');
		FAjax::addResponse('function','getScript','js/markitup/jquery.markitup.pack.js');
		FAjax::addResponse('function','getScript','js/markitup/sets/default/set.js;markItUpInit');
		
		FAjax::addResponse('function','getScript','js/jquery-ui.datepicker.js;datePickerInit');
		FAjax::addResponse('function','getScript','js/i18n/ui.datepicker-cs.js');
		FAjax::addResponse('function','css','css/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		
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