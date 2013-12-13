<?php
class fajax_Page extends FAjaxPluginBase {

	static function fuup($data) {
		$user = FUser::getInstance();
		//---call galery refresh
		$user->pageVO->refreshImages();
		//---get galery item list
		$fItems = new FItems('galery',$user->userVO->userId);
		$fItems->addWhere("pageId = '". $user->pageVO->pageId ."'");
		$fItems->setOrder($user->pageVO->itemsOrder());
		$listArr = page_ItemsList::buildList($fItems,$user->pageVO,array('nopager'=>true));
		FAjax::addResponse('galeryFeed','$html',$listArr['vars']['ITEMS']);
		FAjax::addResponse('call','GaleryEdit.init','');
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