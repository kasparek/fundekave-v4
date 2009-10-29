<?php
class FAjax_draft extends FAjaxPluginBase {
	static function save($data) {

		FUserDraft::save($data['place'],$data['text']);

	}

}