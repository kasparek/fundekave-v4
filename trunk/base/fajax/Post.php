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
		$xs = false;
		if(empty($data['msgType'])) $data['msgType'] = 'def';
		if($data['msgType']=='xs') $xs = true;

		$data = page_UserPost::process($data);
		
		if($xs) {
			return;
		}
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
		
		//online user list
		$q = "SELECT l.userId,u.name FROM sys_users_logged as l join sys_users as u on u.userId=l.userId "
			."WHERE subdate(NOW(),interval ".USERVIEWONLINE." second)<l.dateUpdated and l.userId!='".$user->userVO->userId."' "
			."ORDER BY l.dateUpdated desc";
		$userList = '';
		if (false !== ($arrpra = FDBTool::getAll($q))) {
			if(!empty($arrpra)) {
				foreach ($arrpra as $pra){
					$userList .= '<li><a href="?k=finfo&who='.$pra[0].'"><img src="'.FAvatar::getAvatarUrl($pra[0]).'" /> '.$pra[1].'</a></li>';
				}
			}
		}
		if(!empty($userList)) {
			FAjax::addResponse('onlineUsersDropdown','$removeClass','hidden');
			FAjax::addResponse('onlineUsersNum','$text',count($arrpra));
			FAjax::addResponse('onlineUsersList','$html',$userList);
		} else {
			FAjax::addResponse('onlineUsersDropdown','$addClass','hidden');
			FAjax::addResponse('onlineUsersNum','$text','');
			FAjax::addResponse('onlineUsersList','$html','');
		}
		
		//messages
		$msgs = new FMessages($user->userVO->userId);
		$arrpost = $msgs->load(0, 0, false, 0);
		$msgsOut='';
		$xsShow='';
		if(!empty($data['xsShow'])) $xsShow = $data['xsShow'];
		while($arrpost) {
			$post = array_pop($arrpost);
			if(strpos($data['xsShow'],$post['postId'])===false) {
				$msgsOut.='<div class="msg-xs" id="messxs'.$post['postId'].'"><label class="label label-info">'.$post['fromName'].'</label>'.$post['text'].'</div>';
			}
		}
		if(!empty($msgsOut)) {
			FAjax::addResponse('msgRecipient','$val',$post['fromName']);
			FAjax::addResponse('msgList','$append',$msgsOut);
			FAjax::addResponse('call','scrollToBottom','msgList');
			FAjax::addResponse('call','Msg.chatInit','');
			FAjax::addResponse('call','Fajax.init','');
		}
	}

}