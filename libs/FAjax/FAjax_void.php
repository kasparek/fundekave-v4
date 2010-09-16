<?php
class FAjax_void extends FAjaxPluginBase {
	static function markitup($data) {
		FAjax::addResponse('function','css',URL_JS.'markitup/skins/simple/style.css');
		FAjax::addResponse('function','css',URL_JS.'markitup/sets/default/style.css');
		FAjax::addResponse('function','getScript',URL_JS.'markitup/jquery.markitup.pack.js;markItUpInit');
	}
	static function datepicker($data) {
		FAjax::addResponse('function','css','css/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		FAjax::addResponse('function','getScript',URL_JS.'i18n/ui.datepicker-cs.js,datePickerInit');
	}

}