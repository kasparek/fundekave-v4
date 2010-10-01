<?php
//TODO: refactor to use FAjax_item
class FAjax_event extends FAjaxPluginBase {

	static function deleteImage($data) {
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
		FAjax::addResponse('imageHolder', '$html', '');
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
			FAjax::addResponse('imageHolder', '$html', '');
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

	

}