<?php
class FAjax_void extends FAjaxPluginBase {
	static function markitup($data) {

		FAjax::addResponse('function','css',URL_JS.'markitup/skins/simple/style.css?r='.rand());
		FAjax::addResponse('function','css',URL_JS.'markitup/sets/default/style.css?r='.rand());
		FAjax::addResponse('function','getScript',URL_JS.'markitup/jquery.markitup.pack.js');
		FAjax::addResponse('function','getScript',URL_JS.'markitup/sets/default/set.js;markItUpInit');
			
	}
	static function datepicker($data) {
		
		FAjax::addResponse('function','getScript', URL_JS.'jquery-ui.datepicker.js;datePickerInit');
		FAjax::addResponse('function','getScript',URL_JS.'i18n/ui.datepicker-cs.js');
		FAjax::addResponse('function','css','css/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		
	}

}