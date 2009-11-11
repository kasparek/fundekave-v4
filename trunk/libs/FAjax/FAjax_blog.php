<?php
class FAjax_blog extends FAjaxPluginBase {
	static function edit($data) {

		$ret = FBlog::getEditForm($data['item']);

		if($data['__ajaxResponse']===true) {
			FAjax::addResponse('editnew', 'html', $ret);
			FAjax::addResponse('function','call','draftSetEventListeners');

			FAjax::addResponse('function','getScript','js/jquery-ui.datepicker.js;datePickerInit');
			FAjax::addResponse('function','getScript','js/i18n/ui.datepicker-cs.js');
			FAjax::addResponse('function','css','css/themes/ui-lightness/jquery-ui-1.7.2.custom.css');

			FAjax::addResponse('function','call','addTASwitch');
			FAjax::addResponse('function','call','fajaxform');
		} else {
			FBuildPage::addTab(array("TABID"=>'bloged',"MAINDATA"=>$ret));	
		}
	}
	static function submit($data) {

		$itemId = FBlog::process( $data );
		
/*
		FAjax::addResponse('bloged', 'html', FBlog::listAll($data['item'],true));
		FAjax::addResponse('function','call','draftSetEventListeners');
		FAjax::addResponse('function','call','initInsertToTextarea');
		FAjax::addResponse('function','call','datePickerInit');
		*/
	}

}