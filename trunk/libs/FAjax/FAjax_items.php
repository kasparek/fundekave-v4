<?php
class FAjax_items extends FAjaxPluginBase {
	static function tool($data) {
		FAjax::addResponse('thumbToolbar','$html',FItemsToolbar::getTagToolbar(false));
	}
	static function delete($data) {
		FForum::messDel($data['item']);
		FAjax::addResponse('function','call','remove;i'.$data['item']);
	}
}