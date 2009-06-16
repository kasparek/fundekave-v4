<?php
include_once('iPage.php');
class page_SysEdit implements iPage {

	static function process($data) {

	}

	static function build() {
		FSystem::secondaryMenuAddItem(FUser::getUri('','sbann'),'Banany');
		FSystem::secondaryMenuAddItem(FUser::getUri('','skate'),'Kategorie');
		FSystem::secondaryMenuAddItem(FUser::getUri('','spaka'),'Stranky');
		FSystem::secondaryMenuAddItem(FUser::getUri('','sbanr'),'Bannery');
		FSystem::secondaryMenuAddItem(FUser::getUri('','spoll'),'Ankety');
		FSystem::secondaryMenuAddItem(FUser::getUri('','sleft'),'Sidepanel');
		FSystem::secondaryMenuAddItem(FUser::getUri('','sfunc'),'Sidepanel fce');
	}
}