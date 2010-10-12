<?php
class FAjax_item extends FAjaxPluginBase {

	static function show($data) {
		$itemId = $data['item'];
		if($data['__ajaxResponse']===true) {
			$user = FUser::getInstance();
			$user->itemVO = new ItemVO($itemId,true);
			page_PageItemList::build($data);

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
		if(empty($itemVO) && !empty($data['item'])) $itemVO = new ItemVO($data['item'],true);
		if(empty($itemVO)) {
			$user = FUser::getInstance();
			$itemVO = new ItemVO();
			$itemVO->pageId = $user->pageVO->pageId; 
			$itemVO->set('typeId', $data['t']);	
		}
		$ret = FItemsForm::show($itemVO);
		if($data['__ajaxResponse']===true) {
			FAjax::addResponse('editForm', '$html', $ret);
			FAjax::addResponse('call','jUIInit','');
		} else {
			FBuildPage::addTab(array("TABID"=>'editForm',"MAINDATA"=>$ret));
		}
	}

	static function submit($data) {
		
		FItemsForm::process($data);
		
		if(FAjax::isRedirecting()===false) {
			Fajax_item::edit(array('__ajaxResponse'=>true,'item'=>$data['item']));
			$user = FUser::getInstance();
			if($itemVO->itemId>0) {
				$user->itemVO = $itemVO;
				FAjax::addResponse('i'.$itemId, '$replaceWith', page_ItemDetail::build($data));
			}
		}
	}

	static function delete($data) {
		$itemVO = new ItemVO($data['item']);
		if(!$itemVO->load()) return;
		$user = FUser::getInstance();
		if(FRules::getCurrent(2)===true
		|| (FRules::getCurrent(1)===true && $itemVO->typeId=='forum' && $itemVO->userId==$user->userVO->userId)) {
			$itemVO->delete();
			if($itemVO->typeId=='forum') {
				FAjax::addResponse('call','remove','i'.$data['item']);
			} else {
				FAjax::redirect(FSystem::getUri('',$user->pageVO->pageId,'')); //deleted item
			}
		}

	}
	
	static function image($data) {
		$user = FUser::getInstance();
		if(empty($data['item'])) {
			//only temporary thumbnail
			$filename = FFile::getTemplFilename();
			if($filename===false) return;
			$tpl = FSystem::tpl('form.event.tpl.html');
			$tpl->setVariable('IMAGEURL',FConf::get('galery','sourceUrlBase').$filename);
			$tpl->setVariable('IMAGETHUMBURL',FConf::get('galery','targetUrlBase').'170x0/prop/'.$filename);
			$tpl->parse('image');
			FAjax::addResponse($data['result'], $data['resultProperty'], $tpl->get('image'));
		} else {
			if($itemVO = FItemsForm::moveImage( $data )) {
				$tpl = FSystem::tpl('events.edit.tpl.html');
				$tpl->setVariable('IMAGEURL',FConf::get('galery','sourceUrlBase').$itemVO->pageVO->galeryDir.'/'.$itemVO->enclosure);
				$tpl->setVariable('IMAGETHUMBURL',$itemVO->getImageUrl(null,'170x0/prop'));
				$tpl->parse('image');
				FAjax::addResponse($data['result'], $data['resultProperty'], $tpl->get('image'));
			}
		}
	}

}