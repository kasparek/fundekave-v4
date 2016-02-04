<?php
class fajax_Page extends FAjaxPluginBase {

	static function thumbs() {
		$user = FUser::getInstance();
		$fItems = new FItems('galery',$user->userVO->userId);
		$fItems->addWhere("pageId = '". $user->pageVO->pageId ."'");
		$fItems->setOrder($user->pageVO->itemsOrder());
		$listArr = page_ItemsList::buildList($fItems,$user->pageVO,array('nopager'=>true));
		FAjax::addResponse('galeryFeed','$html',$listArr['vars']['ITEMS']);
	}

	static function listByDate($data) {
		$pageListOut = page_PagesList::build(array(),array('typeId'=>$date->type,'return'=>true,'nopager'=>true,'limit'=>0,
			'inDate'=>$data['year'].(!empty($data['month'])?'-'.$data['month']:'').(!empty($date['date'])?'-'.$date['date']:''),
			'categoryId'=>!empty($date['cat'])?$date['cat']:null));
		$pageListOut = str_replace(' class="well" id="pagesList"','',$pageListOut);
		$pageListOut = str_replace('img src','img src="'.URL_CSS.'images/bg.png" data-src',$pageListOut);
		FAjax::addResponse('pagesList', '$html', str_replace(',', ' ', $pageListOut));
		FAjax::addResponse('call', 'calendarPagesLoaded', $data['year']);
	}

	static function fuup($data) {
		$user = FUser::getInstance();
		//---call galery refresh
		$user->pageVO->refreshImages();
		//---get galery item list
		$fItems = new FItems('galery',$user->userVO->userId);
		$fItems->addWhere("pageId = '". $user->pageVO->pageId ."'");
		$fItems->setOrder($user->pageVO->itemsOrder());
		$listArr = page_ItemsList::buildList($fItems,$user->pageVO,array('nopager'=>true));
		if(!empty($listArr['vars']['ITEMS'])) FAjax::addResponse('galeryFeed','$html',$listArr['vars']['ITEMS']);
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