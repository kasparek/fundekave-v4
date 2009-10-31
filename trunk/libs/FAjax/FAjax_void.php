<?php
class FAjax_void extends FAjaxPluginBase {
	static function markitup($data) {
				
		FAjax::addResponse('function','css','js/markitup/skins/simple/style.css?r='.rand());
		FAjax::addResponse('function','css','js/markitup/sets/default/style.css?r='.rand());
		FAjax::addResponse('function','getScript','js/markitup/jquery.markitup.pack.js');
		FAjax::addResponse('function','getScript','js/markitup/sets/default/set.js;markItUpInit');
			
	}
	
}