<?php
//TODO: refactor to use FAjax_item
class FAjax_event extends FAjaxPluginBase {

	static function delFlyer($data) {
		if($data['item']>0) {
			$itemVO = new ItemVO($data['item'],true);
			if($itemVO->enclosure!='') {
				$rootFlyer = ROOT_FLYER.$itemVO->enclosure;
				$rootFlyerThumb = ROOT_FLYER_THUMB.$itemVO->enclosure;
				if(file_exists($rootFlyer)) unlink($rootFlyer);
				if(file_exists($rootFlyerThumb)) unlink($rootFlyerThumb);
			}
			$itemVO->enclosure = 'null';
			$itemVO->save();
		} else {
			//delete temporary probably

		}
		FAjax::addResponse('flyerDiv', '$html', '');
	}

	static function edit($data) {

		$user = FUser::getInstance();

		if($data['__ajaxResponse']==false) {
			return;
		}

		FAjax::addResponse($data['result'], '$html', FEvents::editForm($data['item']));
		FAjax::addResponse('function','call','draftInit');

		FAjax::addResponse('function','call','datePickerInit');
		
		FAjax::addResponse('function','call','fuupInit');
		FAjax::addResponse('function','call','fajaxformInit');
		FAjax::addResponse('function','call','fconfirmInit');
		FAjax::addResponse('function','call','markItUpSwitchInit');
	}

	static function submit($data) {
		$action = '';
		if(isset($data['action'])) $action = $data['action'];
			
		$itemVO = FEvents::processForm( $data, false );

		if($action=='delFlyer') {
			FAjax::addResponse('flyerDiv', '$html', '');
			return;
		}

		if($itemVO === false) {
			//---item deleted
			FAjax::errorsLater();
			FAjax::addResponse('function','call','redirect;'.FSystem::getUri('','event',''));

		} else {
		
			//if updating just message
			if(!FError::is()) {
				FAjax::addResponse('function','call','msg;ok;'.FLang::$MESSAGE_SUCCESS_SAVED);
			}
			
			$itemId=0;
			if($itemVO) $itemId = $itemVO->itemId;
			FAjax::addResponse('fajaxContent', '$html', FEvents::editForm($itemId));

			FAjax::addResponse('function','call','draftInit');
			FAjax::addResponse('function','call','datePickerInit');
			FAjax::addResponse('function','call','fajaxformInit');
			FAjax::addResponse('function','call','fconfirmInit');
			FAjax::addResponse('function','call','slimboxInit');
			FAjax::addResponse('function','call','tabsInit');
			FAjax::addResponse('function','call','fuupInit');
			

		}


	}

	static function flyer($data) {
		$user = FUser::getInstance();
		$thumb = '';

		if(!isset($data['item'])) {
				
			//only temporary thumbnail
			$cache = FCache::getInstance('d');
			$filename = $cache->getData('event','user-'.$user->userVO->userId);

			$tpl = FSystem::tpl('events.edit.tpl.html');
			$tpl->setVariable('FLYERURL','pic.php?f=tmp/upload/'.$user->userVO->name.'/'.$filename);
			$tpl->setVariable('FLYERTHUMBURL','pic.php?r=tmp/upload/'.$user->userVO->name.'/'.$filename);
			$tpl->setVariable('DELFLY',FSystem::getUri('m=event-delFlyer&d=item:0'));
			$tpl->parse('flyer');
			$thumb = $tpl->get('flyer');

		} else {

			$itemVO = FEvents::processForm( $data );
			if(!empty($itemVO->enclosure)) {
				$tpl = FSystem::tpl('events.edit.tpl.html');
				$tpl->setVariable('FLYERURL',FEvents::flyerUrl($itemVO->enclosure));
				$tpl->setVariable('FLYERTHUMBURL',FEvents::thumbUrl($itemVO->enclosure));
				$tpl->setVariable('DELFLY',FSystem::getUri('m=event-delFlyer&d=item:'.$data['item']));
				$tpl->parse('flyer');
				$thumb = $tpl->get('flyer');
			}
				
				
		}

		FAjax::addResponse($data['result'], $data['resultProperty'], $thumb);
	}

}