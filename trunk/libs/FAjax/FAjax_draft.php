<?php
class FAjax_draft {
	static function save($data) {

		FUserDraft::save($data['place'],$data['text']);

	}

}