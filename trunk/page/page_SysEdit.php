<?php
include_once('iPage.php');
class page_SysEdit implements iPage {

	static function process($data) {

	}

	static function build() {
		FMenu::secondaryMenuAddItem(FUser::getUri('','sbann'),'Banany');
		FMenu::secondaryMenuAddItem(FUser::getUri('','skate'),'Kategorie');
		FMenu::secondaryMenuAddItem(FUser::getUri('','spaka'),'Stranky');
		FMenu::secondaryMenuAddItem(FUser::getUri('','sbanr'),'Bannery');
		FMenu::secondaryMenuAddItem(FUser::getUri('','spoll'),'Ankety');
		FMenu::secondaryMenuAddItem(FUser::getUri('','sleft'),'Sidepanel');
		FMenu::secondaryMenuAddItem(FUser::getUri('','sfunc'),'Sidepanel fce');
	}
}