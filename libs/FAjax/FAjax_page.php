<?php
class FAjax_page extends FAjaxPluginBase {

	static function fuup($data) {
		$user = FUser::getInstance();
		//---call galery refresh
		$pageId = $user->pageId;
		$galery = new FGalery();
		$items = $galery->refreshImgToDb($pageId);
		$newStr = '';
		$updatedStr = '';
		if(isset($items['new'])) $newStr = implode(',',$items['new']);
		if(isset($items['updated'])) $updatedStr = implode(',',$items['updated']);
		FAjax::addResponse('function','call','galeryRefresh;'.$newStr.';'.$updatedStr.';'.$items['total']);
	}

	static function avatar($data) {
		$user = FUser::getInstance();
		$pageVO = new PageVO($user->pageId,true);
		$tpl=FSystem::tpl('page.edit.tpl.html');
		$tpl->setVariable('PAGEICOLINK',WEB_REL_PAGE_AVATAR.$pageVO->pageIco.'?r='.rand());
		$tpl->parse('pageavatar');
		$avatar = $tpl->get('pageavatar');
		FAjax::addResponse($data['result'], $data['resultProperty'], $avatar);
	}

	static function edit($data) {

		page_PageEdit::process($data);

	}

}