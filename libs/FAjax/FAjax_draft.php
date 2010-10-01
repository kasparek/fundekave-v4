<?php
class FAjax_draft extends FAjaxPluginBase {
	static function save($data) {

		FUserDraft::save($data['place'],$data['text']);

	}
	static function check($data) {
		$draft = FUserDraft::get($data['result']);
		if(!empty($draft)) {
			FAjax::addResponse('call', 'draftableSaveTA', $data['result']);
			FAjax::addResponse($data['result'], $data['resultProperty'], htmlentities($draft,ENT_QUOTES,'UTF-8'));
			FAjax::addResponse($data['result'], '$addClass', 'draft');
			FAjax::addResponse($data['result'], '$before', '<a id="draftdrop'.$data['result'].'" href="?ta='.$data['result'].'" style="margin-left: -20px; padding-right:4px;" title="Zahodit draft" onClick="dropDraft(event)"><img src="css/skin/default/img/clean.png" /></a>');
		}
	}
	static function drop($data) {
		FUserDraft::clear($data['result']);
		FAjax::addResponse('function', 'void', 'draft dropped');
	}

}