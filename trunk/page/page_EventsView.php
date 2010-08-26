<?php
include_once('iPage.php');
class page_EventsView implements iPage {

	static function process($data) {
		
		FEvents::process( $data );

	}

	static function build($data=array()) {
		$user = FUser::getInstance();

		if(empty($user->pageParam)) {
			FMenu::secondaryMenuAddItem(FSystem::getUri('','eveac'),FLang::$LABEL_EVENTS_ARCHIV);
		}

		FEvents::view();
	}
}
