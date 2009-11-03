<?php
include_once('iPage.php');
class page_SysEdit implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {
		FMenu::secondaryMenuAddItem(FSystem::getUri('','sbann'),'Banany');
		FMenu::secondaryMenuAddItem(FSystem::getUri('','skate'),'Kategorie');
		FMenu::secondaryMenuAddItem(FSystem::getUri('','spaka'),'Stranky');
		FMenu::secondaryMenuAddItem(FSystem::getUri('','sbanr'),'Bannery');
		FMenu::secondaryMenuAddItem(FSystem::getUri('','spoll'),'Ankety');
		FMenu::secondaryMenuAddItem(FSystem::getUri('','sleft'),'Sidepanel');
		FMenu::secondaryMenuAddItem(FSystem::getUri('','sfunc'),'Sidepanel fce');
	}
}