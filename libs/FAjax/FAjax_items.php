<?php
class FAjax_items {
	static function tool($data) {
		$fajax = FAfax::getInstance();
		$fajax->addResponse('thumbToolbar','html',FItems::getTagToolbar(false));
	}

}