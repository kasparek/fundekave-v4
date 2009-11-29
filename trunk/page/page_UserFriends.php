<?php
include_once('iPage.php');
class page_UserFriends implements iPage {

	static function process($data) {
		
	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;
		
		$emptyMsg = true;
		
		$tpl = FSystem::tpl('user.friends.tpl.html');

		$arr = $user->userVO->loadFriends();
		if(!empty($arr)) {
			$emptyMsg = false;
			$tpl->setVariable('FRIENDSLIST' , FUser::usersList( $arr, 'friend', 'Pratele' ) );
		}
		
		$arr = $user->userVO->loadRequests();
		if(!empty($arr)) {
			$emptyMsg = false;
			$tpl->setVariable('REQUESTSLIST' , FUser::usersList( $arr, 'request', 'Requests' ) );
		}
		
		$arr = $user->userVO->loadOnlineFriends();
		if(!empty($arr)) {
			$emptyMsg = false;
			$tpl->setVariable('ONLINELIST' , FUser::usersList( $arr, 'online', 'Online' ) );
		}
		
		if($emptyMsg===true) {
			$tpl->touchBlock('nofriends');
		}
		
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));

		
	}
}