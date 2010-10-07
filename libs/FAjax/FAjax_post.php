<?php
class FAjax_post extends FAjaxPluginBase {
	static function page($data) {
		if(isset($data['p'])) $data['p'] = $data['p']*1; else $data['p']=1;
		if($data['__ajaxResponse']===true) {
			$data['refreshPager']=true;
			page_UserPost::build($data);
			FAjax::addResponse('call','shiftTo','0');
			FAjax::addResponse('call','initPager','');
			FAjax::addResponse('call','fajaxaInit','');
		} else {
		   FHTTP::redirect(FSystem::getUri($data['p']>1?'p='.$data['p']:'','',''));
		}
	}

	static function submit($data) {
		$data = page_UserPost::process($data);
		page_UserPost::build($data);
		FAjax::addResponse('postText','value','');
		FAjax::addResponse('call','fajaxaInit','');
		FAjax::addResponse('call','fajaxformInit','');
	}
	
  static function avatarFromInput($data) {
		$recipientId = FUser::getUserIdByName($data['username']) * 1;
		FAjax::addResponse('recipientavatar','$html',FAvatar::showAvatar($recipientId).'<br/>'.FUser::getgidname($recipientId));
	}
	
	static function hasNewMessage($data,$wait=false) {
	  $user = FUser::getInstance();
	  
	  if(isset($data['unreadSentList'])) {
	  		if($readed=FMessages::sentReaded($data['unreadSentList']))
	  			FAjax::addResponse('call','messageSentReaded',$readed);
	  			$wait = false;
		}
	  $check = $user->userVO->hasNewMessages();
	  if($check) $wait = false;
	  
		if($wait) {
			sleep(5); //wait a bit and try again so client does not poll that often
	  	FAjax_post::hasNewMessage($data,false);
	  	return;
	  }
	  
	  if($check) {
		  FAjax::addResponse('call','messageCheckHandler',$user->userVO->newPost.','.$user->userVO->newPostFrom);
		  if($user->pageVO) {
		  if($user->pageVO->pageId=='fpost') {
		  	//if search - reset search - $data['refreshPage']=true;
		  	$cache = FCache::getInstance('s');
		  	if($cache->getData('text','filtrPost')) {
		  		$cache->invalidateData($user->pageVO->pageId, 'filter');
		  		$data['refreshPage']=true;
		  	}
		  	//if page > 0 $data['refreshPager']=true;  - reset hash
		  	if(isset($data['p'])) {
		  	if($data['p'] > 1) {
		  	  $data['refreshPager']=true;
					FAjax::addResponse('call','hashReset',''); 
		  	}
		  	}
		  	
		  	page_UserPost::build($data);
			}
			}
	  } else {
	  	FAjax::addResponse('call','messageCheckHandler','0');
		}
	}
	
}