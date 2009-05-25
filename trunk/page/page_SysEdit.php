<?php
include_once('iPage.php');
class page_SysEdit implements iPage {

	static function process() {

	}

	static function build() {
		fSystem::secondaryMenuAddItem(FUser::getUri('','sbann'),'Banany');
		fSystem::secondaryMenuAddItem(FUser::getUri('','skate'),'Kategorie');
		fSystem::secondaryMenuAddItem(FUser::getUri('','spaka'),'Stranky');
		fSystem::secondaryMenuAddItem(FUser::getUri('','sbanr'),'Bannery');
		fSystem::secondaryMenuAddItem(FUser::getUri('','spoll'),'Ankety');
		fSystem::secondaryMenuAddItem(FUser::getUri('','sleft'),'Sidepanel');
		fSystem::secondaryMenuAddItem(FUser::getUri('','sfunc'),'Sidepanel fce');
	}
}