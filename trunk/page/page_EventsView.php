<?php
include_once('iPage.php');
class page_EventsView implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		if($user->pageParam == 'u') {
			page_EventsEdit::process($data);
		}
		else 
		{
			FForum::process($data);
		}

	}

	static function build() {
		$user = FUser::getInstance();

		if(empty($user->pageParam)) {
			FSystem::secondaryMenuAddItem(FUser::getUri('','eveac'),FLang::$LABEL_EVENTS_ARCHIV);
		}

		if($user->pageParam=='u') {
			
			page_EventsEdit::build();
			
		} else {
			
			if($user->itemVO->itemId > 0) {

				$itemVO = new ItemVO($user->itemVO->itemId,true ,array('type'=>'event','showComments'=>true) );
				$tpl = new FTemplateIT('events.tpl.html');
				$tpl->setVariable('ITEMS',$itemVO->render());
				$tmpText = $tpl->get();

			} else {
				
				$tmpText = FEvents::show();
				
			}
			FBuildPage::addTab(array("MAINDATA"=>$tmpText,"MAINID"=>'fajaxContent'));
		}
	}
}
