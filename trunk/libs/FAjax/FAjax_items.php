<?php
class FAjax_items {
	static function tool($data) {
		$fajax = FAjax::getInstance();
		$fajax->addResponse('thumbToolbar','html',FItems::getTagToolbar(false));
	}

}