<?php
class FAjax_pages extends FAjaxPluginBase {
	static function book($data) {
		if (FDBTool::getOne("select book from sys_pages_favorites where pageId = '".$data['page']."' AND userId = '".$data['user']."'")) {
			$book = 0;
			$data = LABEL_BOOK;
		} else {
			$book = 1;
			$data = LABEL_UNBOOK;
		}
		FDBTool::query("update sys_pages_favorites set book='".$book."' where pageId='".($data['page'])."' AND userId='" . $data['user']."'");
		
		FAjax::addResponse('bookButt','html',$data);
	}

	static function booked($data) {
		$user = FUser::getInstance();
		if($user->$userVO->isFriend($data['user'])) $user->whoIs = $data['user'];
		$fPages = new FPages($typeId,$user->$userVO->userId);
		
		FAjax::addResponse('bookedContent','html',$fPages->printBookedList(true));
	}

}