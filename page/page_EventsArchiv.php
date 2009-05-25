<?php
include_once('iPage.php');
class page_EventsArchiv implements iPage {

	static function process() {

	}

	static function build() {
		$user = FUser::getInstance();
		$user->pageParam = 'archiv';
		page_EventsView::build();
	}
}