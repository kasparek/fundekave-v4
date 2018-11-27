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
		//-move file
		require_once(LIBS.'fpapi/FilePond/RequestHandler.class.php');
		$target_dir = FConf::get('galery','sourceServerBase') . $user->pageVO->galeryDir;
		if($user->pageVO->typeId === 'galery') {
			$res = FilePond\RequestHandler::save(array($data['file_id']), $target_dir);
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
		if($user->pageVO->typeId === 'forum' || $user->pageVO->typeId === 'blog') {
			$target_dir = $user->pageVO->get('galeryDir');
	        if (empty($target_dir)) {
	            $user->pageVO->set('galeryDir', 'page/' . $user->pageVO->pageId . '-' . FText::safeText($user->pageVO->get('name')));
	            $user->pageVO->save();
	            $target_dir = $user->pageVO->get('galeryDir');
	        }
			$file = FilePond\RequestHandler::getTempFile($data['file_id']);
			$file_name_parts = explode('.',$file['name']);
			$extension = strtolower(array_pop($file_name_parts));
			$filename = $user->userVO->userId . '-' .date("Ymd").'-'.substr(FText::safeText(implode('.', $file_name_parts)), 0,32).'.'.$extension;
			$res = FilePond\RequestHandler::moveFileById($data['file_id'], $target_dir, $filename);
			FAjax::addResponse('attachement','value',$filename);
		}
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