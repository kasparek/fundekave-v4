<?php
class FAjax_event {
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
		
		if($itemId === false) {
			//---item deleted
			$fajax->addResponse('function','call','redirect;'.FUser::getUri('','event'));
			
		} else {
			
			$fajax->addResponse($data['result'], 'html', FEvents::editForm($itemVO->itemId));
					
			$fajax->addResponse('function','call','draftSetEventListeners');
			$fajax->addResponse('function','call','datePickerInit');
			$fajax->addResponse('function','call','fajaxform');
			$fajax->addResponse('function','call','markItUpInit');
			
		}
	}

}