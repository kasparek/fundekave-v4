<?php
class FAjax_item extends FAjaxPluginBase {

	static function show($data) {
		$itemId = $data['i'];
		if($data['__ajaxResponse']===true) {
			$user = FUser::getInstance();
			$user->itemVO = new ItemVO($itemId,true);
			page_ItemsList::build($data);

			$breadcrumbs = FBuildPage::getBreadcrumbs();
			$tpl = FSystem::tpl(TPL_MAIN);
			foreach($breadcrumbs as $crumb) {
				$tpl->setVariable('BREADNAME',$crumb['name']);
				if(isset($crumb['url'])) {
					$tpl->setVariable('BREADURL',$crumb['url']);
					$tpl->touchBlock('breadlinkend');
				}
				$tpl->parse('bread');
			}
			$tpl->parse('breadcrumbslist');
			FAjax::addResponse('breadcrumbs','$html',$tpl->get('breadcrumbslist'));
		} else {
			FHTTP::redirect(FSystem::getUri('i='.$itemId,'',''));
		}
	}

	static function edit($data,$itemVO=null) {
		if(empty($itemVO) && !empty($data['i'])) $itemVO = new ItemVO($data['i'],true);
		if(empty($itemVO)) {
			$user = FUser::getInstance();
			$itemVO = new ItemVO();
			$itemVO->pageId = $user->pageVO->pageId; 
			$itemVO->set('typeId', $data['t']);	
		}
		if(empty($itemVO->typeId) && isset($data['t'])) $itemVO->set('typeId', $data['t']);
		$ret = FItemsForm::show($itemVO);
		if($data['__ajaxResponse']===true) {
			FAjax::addResponse('editForm', '$html', $ret);
			FAjax::addResponse('call','jUIInit','');
		} else {
			FBuildPage::addTab(array("MAINID"=>'editForm',"MAINDATA"=>$ret));
		}
	}

	static function submit($data) {
	
		FItemsForm::process($data);
		
		if($data['__ajaxResponse']===true)
		if(FAjax::isRedirecting()===false) {
			Fajax_item::edit($data);
			if(!empty($data['i'])) { 
			$itemVO = new ItemVO((int) $data['i']);
			if($itemVO->load()) {
				page_ItemDetail::build($data);
			}
			}
		}
	}

	static function delete($data) {
		$itemVO = new ItemVO($data['i']);
		if(!$itemVO->load()) return;
		$user = FUser::getInstance();
		if(FRules::getCurrent(2)===true
		|| (FRules::getCurrent(1)===true && $itemVO->typeId=='forum' && $itemVO->userId==$user->userVO->userId)) {
			$type = $itemVO->typeId;
			$itemVO->delete();
			if($type=='forum') {
				FAjax::addResponse('call','remove','i'.$data['i']);
			} elseif($type!='galery') {
				FAjax::redirect(FSystem::getUri('',$user->pageVO->pageId,'')); //deleted item
			}
		}

	}
	
	static function image($data) {
		$user = FUser::getInstance();
		if($user->pageVO->pageId=='fedit') {
			FAjax_user::avatar($data);
			return true;
		}
		if(empty($data['i'])) {
			//only temporary thumbnail
			$filename = FFile::getTemplFilename();
			if($filename===false) return;
			$tpl = FSystem::tpl('form.event.tpl.html');
			$tpl->setVariable('IMAGEURL',FConf::get('galery','sourceUrlBase').$filename);
			$tpl->setVariable('IMAGETHUMBURL',FConf::get('galery','targetUrlBase').'170x0/prop/'.$filename);
			$tpl->parse('image');
			FAjax::addResponse('imageHolder', '$html', $tpl->get('image'));
			FAjax::addResponse('call','tempStoreDeleteInit');
		} else {
			if($itemVO=FItemsForm::moveImage($data)) {
				$tpl = FSystem::tpl('form.'.$itemVO->typeId.'.tpl.html');
				$tpl->setVariable('IMAGEURL',FConf::get('galery','sourceUrlBase').$itemVO->pageVO->get('galeryDir').'/'.$itemVO->enclosure);
				$tpl->setVariable('IMAGETHUMBURL',$itemVO->getImageUrl(null,'170x0/prop'));
				$tpl->touchBlock('confirm');
				$tpl->parse('image');
				FAjax::addResponse('imageHolder', '$html', $tpl->get('image'));
			}
		}
		if(!empty($data['i'])) {
			FAjax::addResponse('i'.$data['i'], 'replaceWith', page_ItemDetail::build($data));
		}
	}
	
	static function tempStoreFlush($data) {
	  FFile::flushTemplFile();
	}

}