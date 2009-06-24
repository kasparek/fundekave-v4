<?php
class FAjax_event {
	static function delFlyer($data) {
		if($data['item']>0) {
			$itemVO = new ItemVO($data['item'],true);
			if($itemVO->enclosure!='') {
				if(file_exists(FConf::get('events','flyer_source').$itemVO->enclosure)) unlink(FConf::get('events','flyer_source').$itemVO->enclosure);
				if(file_exists(FConf::get('events','flyer_cache').$itemVO->enclosure)) unlink(FConf::get('events','flyer_cache').$itemVO->enclosure);
			}
			$itemVO->enclosure = 'null';
			$itemVO->save();
			
			$fajax = FAjax::getInstance();
			$fajax->addResponse('flyerDiv', 'html', '');
		}
	}
	static function edit($data) {
		
		$user = FUser::getInstance();
		$user->itemVO->itemId = $data['item'];
		if($data['__ajaxResponse']==false) {
			return;
		}

		$fajax = FAjax::getInstance();
		
		$fajax->addResponse($data['result'], 'html', FEvents::editForm());

		$fajax->addResponse('function','call','draftSetEventListeners');
		$fajax->addResponse('function','call','datePickerInit');
		$fajax->addResponse('function','call','fajaxform');
		$fajax->addResponse('function','call','markItUpInit');
		
	}
	static function submit($data) {
		
		$itemVO = FEvents::processForm( $data, false );
		
		$fajax = FAjax::getInstance();
		
		if(isset($data['uploadify'])) {
			//---handle flyer upload
			if($itemVO) {
				$itemId = $itemVO->itemId;
				$fajax->addResponse('flyerDiv', 'html', FEvents::editForm($itemId,'flyer'));
				$fajax->addResponse('function','call','fajaxa');
				$fajax->addResponse('item', 'value', $itemId);
			}
		} elseif($itemVO === false) {
			//---item deleted
			$fajax->addResponse('function','call','redirect;'.FUser::getUri('','event'));
			
		} else {
			$itemId=0;
			if($itemVO) $itemId = $itemVO->itemId;
			$fajax->addResponse('fajaxContent', 'html', FEvents::editForm($itemId));
					
			$fajax->addResponse('function','call','draftSetEventListeners');
			$fajax->addResponse('function','call','datePickerInit');
			$fajax->addResponse('function','call','fajaxform');
			$fajax->addResponse('function','call','markItUpInit');
			
		}
	}

}