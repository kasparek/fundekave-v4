<?php
class fajax_Item extends FAjaxPluginBase {

	static function show($data) {
		if(isset($data['i'])) $itemId = (int) $data['i'];
		if(isset($data['item'])) $itemId = (int) $data['item'];
		if(empty($itemId)) {
			FError::write_log("FAjax_item::show - EMPTY ITEMID");
			return;
		}
		if($data['__ajaxResponse']) {
			$user = FUser::getInstance();
			$user->itemVO = FactoryVO::get('ItemVO',$itemId,true);
			if(!$user->itemVO) return;//not valid item
			
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

	static function showupload($data) {
		if(!empty($data['__ajaxResponse'])) {
			$user = FUser::getInstance();
			if(FRules::getCurrent(2)) {
				$utpl = FSystem::tpl('form.fuup.tpl.html');
				$utpl->touchBlock('__global__');
			}
			FAjax::addResponse('editForm', '$html', $utpl->get());
			FAjax::addResponse('call','jUIInit','');
		}
	}
	
	static function edit($data,$itemVO=null) {
		if(!$data['__ajaxResponse']) return;
		$user = FUser::getInstance();
		if(FRules::getCurrent(2) || ($user->pageVO->pageId=='event' && FRules::getCurrent(FConf::get('settings','perm_add_event')))) {
			if(empty($itemVO) && !empty($data['i'])) $itemVO = new ItemVO($data['i'],true);
			if(empty($itemVO)) {
				$itemVO = new ItemVO();
				$itemVO->pageId = $user->pageVO->pageId; 
				$itemVO->set('typeId', $data['t']);	
			}
			if(empty($itemVO->typeId) && isset($data['t'])) $itemVO->set('typeId', $data['t']);
			$ret = FItemsForm::show($itemVO);
			FAjax::addResponse('editForm', '$html', $ret);
			FAjax::addResponse('call','jUIInit','');
		} else {
			FError::add(FLang::$ERROR_ACCESS_DENIED);
		}
	}

	static function submit($data) {
		//save item
		$user = FUser::getInstance();
		if(!empty($data['ti'])) {
			$user->itemVO = new ItemVO($data['ti']*1);
			if(!$user->itemVO->load())
				return; //ERROR invalid top id
		}
		
		FItemsForm::process($data);

		if(!empty($data['__ajaxResponse'])) {
			if(FAjax::isRedirecting()===false) {
				if($data['t']=='forum') {
					//return list of items
					$tpl = page_ItemsList::buildPrep(array('__ajaxResponse'=>true,'itemId'=>$itemId,'onlyComments'=>true,'fajaxform'=>true));
					$tpl->parse('messageForm');
					$tpl->parse('itemlist');
					FAjax::addResponse('messageForm','$replaceWith', $tpl->get('messageForm'));
					FAjax::addResponse('forumFeed','$replaceWith', $tpl->get('itemlist'));
					FAjax::addResponse('call','gooMapiInit');
					FAjax::addResponse('call','fajaxInit');
				} else {
					Fajax_item::edit($data);
					if(!empty($data['i'])) { 
						if($itemVO = FactoryVO::get('ItemVO',(int) $data['i'])) {
							//reload updated item
							$itemVO->load();
							page_ItemDetail::build($data);
						}
					}
				}
			}
		}
	}

	static function delete($data) {
		$itemId = isset($data['i'])?$data['i']:$data['item'];
		if(!$itemVO = FactoryVO::get('ItemVO',$itemId,true)) return;
		if(!$itemVO->editable) return;
		$type = $itemVO->typeId;
		$itemVO->delete();
		if($type=='forum') {
			FAjax::addResponse('call','remove','i'.$itemId);
		} elseif($type!='galery') {
			$user = FUser::getInstance();
			FAjax::redirect(FSystem::getUri('',$user->pageVO->pageId,'')); //deleted item
		}
	}
	
	static function image($data) {
		$user = FUser::getInstance();
		if($user->pageVO->pageId=='fedit') {
			fajax_User::avatar($data);
			return;
		}
		if(empty($data['i'])) {
			//only temporary thumbnail
			$filename = FFile::getTemplFilename();
			$tpl = FSystem::tpl('image.tempStore.tpl.html');
			$tpl->setVariable('URL',FConf::get('galery','sourceUrlBase').$filename);
			$tpl->setVariable('THUMBURL',FConf::get('galery','targetUrlBase').'170x170/prop/'.$filename);
			FAjax::addResponse('imageHolder', '$html', $tpl->get());
			FAjax::addResponse('call','tempStoreDeleteInit');
		} else if($itemVO=FItemsForm::moveImage($data)) {
			$tpl = FSystem::tpl('form.'.$itemVO->typeId.'.tpl.html');
			$tpl->setVariable('IMAGEURL',FConf::get('galery','sourceUrlBase').$itemVO->pageVO->get('galeryDir').'/'.$itemVO->enclosure);
			$tpl->setVariable('IMAGETHUMBURL',$itemVO->getImageUrl(null,'170x170/prop'));
			$tpl->parse('image');
			FAjax::addResponse('imageHolder', '$html', $tpl->get('image'));
			FAjax::addResponse('i'.$data['i'], 'replaceWith', page_ItemDetail::build($data));
		}
	}
  
	static function commentsForm($data) {
		$user = FUser::getInstance();		
		
		if(!empty($data['ti'])) 
			if(!$user->itemVO = FactoryVO::get('ItemVO',(int) $data['ti']))
				return; //ERROR invalid top id
		
		if(FItemsForm::canComment()) {
			$formItemVO = FactoryVO::get('ItemVO');
			$formItemVO->typeId = 'forum';
			$formItemVO->pageId = $user->pageVO->pageId;
			$data['fajaxform']=true;
			$output = FItemsForm::show($formItemVO,$data);
		} else {
			$output = FLang::$MESSAGE_FORUM_REGISTEREDONLY;
		}
		
		FAjax::addResponse('messageForm', '$html', $output);
		FAjax::addResponse('call','gooMapiInit');
		FAjax::addResponse('call','fajaxInit');
		if(!$user->idkontrol) FAjax::addResponse('call','recaptchaStart');
	}
  
	static function comments($data) {
		$itemIdTop = $data['id'] * 1;
		if(empty($itemIdTop)) return;

		$user = FUser::getInstance();
		$user->itemVO = new ItemVO($itemIdTop);
		if(!$user->itemVO->load()) return; //ERROR invalid TOP item id
		
		if(isset($data['stats'])) {
			//return thumb and seen
		}

		$tpl = page_ItemsList::buildPrep(array('__ajaxResponse'=>true,'itemIdTop'=>$itemIdTop,'onlyComments'=>true,'fajaxform'=>true));
		$output = '';
		$tpl->parse('comm');
		$output .= $tpl->get('comm');
		$tpl->parse('messageForm');
		$output .= $tpl->get('messageForm');
		$tpl->parse('itemlist');
		$output .= $tpl->get('itemlist');

		FAjax::addResponse('afterFeed', '$html', $output);
		FAjax::addResponse('call','fajaxInit');
	}
	
	static function tempStoreFlush($data) {
	  FFile::flushTemplFile();
	}
}