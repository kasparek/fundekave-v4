<?php
class fajax_post extends FAjaxPluginBase {
	
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
		$recipientId = FUser::getUserIdByName($data['username']) * 1;
		FAjax::addResponse('recipientavatar','$html',FAvatar::showAvatar($recipientId).'<br/>'.FUser::getgidname($recipientId));
	}

	static function hasNewMessage($data) {
		$user = FUser::getInstance();
		if(isset($data['unreadedSent'])) {
			if($readed=FMessages::sentReaded($data['unreadedSent'])) {
				FAjax::addResponse('call','Msg.sentReaded',$readed);
			}
		}
		$check = $user->userVO->hasNewMessages();
		if($check) {
			FAjax::addResponse('call','Msg.checkHandler',$user->userVO->newPost.','.$user->userVO->newPostFrom);
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
			  	  	FAjax::addResponse('call','Hash.reset','');
			  		}
			  	}
			  	page_UserPost::build($data);
			  }
			}
		} else {
			FAjax::addResponse('call','Msg.checkHandler','0');
		}
	}

}