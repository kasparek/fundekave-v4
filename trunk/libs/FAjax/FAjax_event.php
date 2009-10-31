<?php
class FAjax_event extends FAjaxPluginBase {
	static function delFlyer($data) {
		if($data['item']>0) {
			$itemVO = new ItemVO($data['item'],true);
			if($itemVO->enclosure!='') {
				if(file_exists(FConf::get('events','flyer_source').$itemVO->enclosure)) unlink(FConf::get('events','flyer_source').$itemVO->enclosure);
				if(file_exists(FConf::get('events','flyer_cache').$itemVO->enclosure)) unlink(FConf::get('events','flyer_cache').$itemVO->enclosure);
			}
			$itemVO->enclosure = 'null';
			$itemVO->save();
			
			FAjax::addResponse('flyerDiv', 'html', '');
		}
	}
	static function edit($data) {
		
		$user = FUser::getInstance();
		$user->itemVO->itemId = $data['item'];
		if($data['__ajaxResponse']==false) {
			return;
		}
		
		FAjax::addResponse($data['result'], 'html', FEvents::editForm());
		FAjax::addResponse('function','call','draftSetEventListeners');
		
		FAjax::addResponse('function','css','js/markitup/skins/simple/style.css');
		FAjax::addResponse('function','css','js/markitup/sets/default/style.css');
		FAjax::addResponse('function','getScript','js/markitup/jquery.markitup.pack.js');
		FAjax::addResponse('function','getScript','js/markitup/sets/default/set.js;markItUpInit');
		
		FAjax::addResponse('function','getScript','js/jquery-ui.datepicker.js;datePickerInit');
		FAjax::addResponse('function','getScript','js/i18n/ui.datepicker-cs.js');
		FAjax::addResponse('function','css','css/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		
		FAjax::addResponse('function','call','fajaxform');
		
	}
	static function submit($data) {
		
		$itemVO = FEvents::processForm( $data, false );
		
		if(isset($data['uploadify'])) {
			//---handle flyer upload
			if($itemVO) {
				$itemId = $itemVO->itemId;
				FAjax::addResponse('flyerDiv', 'html', FEvents::editForm($itemId,'flyer'));
				FAjax::addResponse('function','call','fajaxa');
				FAjax::addResponse('item', 'value', $itemId);
			}
		} elseif($itemVO === false) {
			//---item deleted
			FAjax::addResponse('function','call','redirect;'.FSystem::getUri('','event'));
			
		} else {
			$itemId=0;
			if($itemVO) $itemId = $itemVO->itemId;
			FAjax::addResponse('fajaxContent', 'html', FEvents::editForm($itemId));
					
			FAjax::addResponse('function','call','draftSetEventListeners');
			FAjax::addResponse('function','call','datePickerInit');
			FAjax::addResponse('function','call','fajaxform');
			FAjax::addResponse('function','call','markItUpInit');
			
		}
	}

}