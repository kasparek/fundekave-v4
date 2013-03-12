<?php
class fajax_Page extends FAjaxPluginBase {

	static function fuup($data) {
		$user = FUser::getInstance();
		//---call galery refresh
		$items = $user->pageVO->refreshImages();
		$newStr = '';
		$updatedStr = '';
		if(isset($items['new'])) $newStr = implode(';',$items['new']);
		if(isset($items['updated'])) $updatedStr = implode(';',$items['updated']);
		FAjax::addResponse('call','GaleryEdit.refresh',$newStr.','.$updatedStr.','.$items['total']);
	}

	static function avatar($data) {
		$user = FUser::getInstance();
		$pageVO = FactoryVO::get('PageVO',$user->pageId,true);
		$tpl=FSystem::tpl('page.edit.tpl.html');
		$tpl->setVariable('PAGEICOLINK',URL_PAGE_AVATAR.$pageVO->pageIco.'?r='.rand());
		$tpl->parse('pageavatar');
		$avatar = $tpl->get('pageavatar');
		FAjax::addResponse($data['result'], $data['resultProperty'], $avatar);
	}

	static function edit($data) {
		page_PageEdit::process($data);
		if(FAjax::isRedirecting()===false) {
			page_PageEdit::build($data);
		}
	}
}