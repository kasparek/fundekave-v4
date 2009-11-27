<?php
include_once('iPage.php');
class page_UserFriends implements iPage {

	static function process($data) {
		
	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;

		
		$tpl = FSystem::tpl('user.friends.tpl.html');
		$tpl->setVariable('FRIENDSLIST' , FUser::usersList( $user->userVO->loadFriends(), 'friend', 'Pratele' ) );
		
		$tpl->setVariable('REQUESTSLIST' , FUser::usersList( $user->userVO->loadRequests(), 'request', 'Requests' ) );
		
		$tpl->setVariable('ONLINELIST' , FUser::usersList( $user->userVO->loadOnlineFriends(), 'online', 'Online' ) );
		
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));

		
	}
}