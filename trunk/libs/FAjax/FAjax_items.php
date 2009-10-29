<?php
class FAjax_items extends FAjaxPluginBase {
	static function tool($data) {
		FAjax::addResponse('thumbToolbar','html',FItems::getTagToolbar(false));
	}

}