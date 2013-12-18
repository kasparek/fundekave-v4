<?php
class fajax_Post extends FAjaxPluginBase {
	
	static function page($data) {
		if(isset($data['p'])) $data['p'] = $data['p']*1; else $data['p']=1;
		if($data['__ajaxResponse']) {
			$data['refreshPager']=true;
			page_UserPost::build($data);
			FAjax::addResponse('call','shiftTo','0');
			FAjax::addResponse('call','fajaxInit','');
		} else {
			FHTTP::redirect(FSystem::getUri($data['p']>1?'p='.$data['p']:'','',''));
		}
	}

	static function submit($data) {
		$data = page_UserPost::process($data);
		if($data['__ajaxResponse']) {
			page_UserPost::build($data);
			FAjax::addResponse('postText','value','');
			FAjax::addResponse('call','fajaxInit','');
		}
	}

	static function avatarFromInput($data) {
		$nameList = explode(",",$data['username']);
		$ret = '';
		foreach($nameList as $uname) {
			$uid = FUser::getUserIdByName($uname);
			$ret .= FAvatar::showAvatar($uid);
		}
		FAjax::addResponse('recipientavatar','$html',$ret);
	}

	static function poll($data) {
		$user = FUser::getInstance();
		if(isset($data['unreadedSent'])) {
			if($readed=FMessages::sentReaded($data['unreadedSent'])) {
				foreach($readed as $id) {
					FAjax::addResponse('mess'.$id,'$removeClass','unread');
				}
			}
		}
		$check = $user->userVO->hasNewMessages();
		if($check) {
			FAjax::addResponse('numMsg','$text',$user->userVO->newPost);
			FAjax::addResponse('recentSender','$text',$user->userVO->newPostFrom);
			FAjax::addResponse('message-new','$removeClass','hidden');
			if($user->pageVO) {
			  if($user->pageVO->pageId=='fpost') {
			  	//reset search
			  	$cache = FCache::getInstance('s');
			  	if($cache->getData('text','filtrPost')) {
			  		$cache->invalidateData($user->pageVO->pageId, 'filter');
			  		$data['refreshPage']=true;
			  	}
			  	//reset pager
			  	if(isset($data['p'])) {
			  		if($data['p'] > 1) {
						$data['refreshPager']=true;
			  		}
			  	}
			  	page_UserPost::build($data);
			  }
			}
		} else {
			FAjax::addResponse('message-new','$addClass','hidden');
		}
	}

}