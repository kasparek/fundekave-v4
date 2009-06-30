<?php
class FAjax_items {
	static function tool($data) {
		FAjax::addResponse('thumbToolbar','html',FItems::getTagToolbar(false));
	}

}