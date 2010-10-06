<?php
include_once('iPage.php');
class page_UserPost implements iPage {

	static function process($data) {
		$redirParam = '';
		$user = FUser::getInstance();
		$cache = FCache::getInstance('s');
		//---action part - cache -pp,filtr
		$redir = false;
		if(isset($data['__get']['filtr'])) {
			if($data['__get']['filtr'] == 'cancel') {
				$cache->invalidateData($user->pageVO->pageId, 'filter');
			}
		}

		//---SEND MESSAGE
		if(isset($data["send"])) {
			$data["zprava"] = FSystem::textins($data["zprava"],array('plainText'=>1)));
			$data["recipient"] = FSystem::textins($data["recipient"]));
			if(empty($data["zprava"])) FError::add(FLang::$MESSAGE_EMPTY);
			if(!empty($data["recipient"])) {
				$recipientList=explode(",",$data["recipient"]);
				foreach ($recipientList as $usrname) {
					$usrname = trim($usrname);
					if($pro = FUser::getUserIdByName($usrname)) $arrto[] = $pro;
					else $errjm[] = $usrname;
				}
				if(!empty($errjm)) FError::add(implode(", ",$errjm)." :: ".FLang::$MESSAGE_USERNAME_NOTEXISTS);
			}
			if(empty($arrto)) {
				FError::add(FLang::$MESSAGE_RECIPIENT_EMPTY);
				FUserDraft::save('postText',$data["zprava"]);
			}
			
			if(!Ferror::is()) {
					foreach ($arrto as $userId){
						FMessages::send($userId,$data["zprava"],$user->userVO->userId);
					}
					$redirParam = '#dd';
					$cache->invalidateData($user->pageVO->pageId, 'filter');
			}
		}

		if(isset($data['special'])) {
			 switch($data['saction']) {
			 	case 'setpp':
					$user->pageVO->perPage($data["perpage"]);
					break;
				case 'search':
					$cache->setData(array(FSystem::textins($data["recipient"],array('plainText'=>1)),FSystem::textins($data["zprava"],array('plainText'=>1))), $user->pageVO->pageId, 'filter');
				break;
				case 'delete':
				case 'deletebetween':
				if(empty($data["del"])) break;  
				if($saction=='deletebetween' && count($data["del"]) > 1) {
					$displayedMsgs = &$cache->getPointer('user');
					if(empty($displayedMsgs)) break;
					$firstIndex = array_search($first,$data["del"][0]);
					$len = array_search($last,$data["del"][(count($data["del"])-1)]) - $firstIndex;
					$data["del"] = array_slice($displayedMsgs, $firstIndex, $len); 
					$displayedMsgs = null;
				}
				FMessages::delete($data["del"]);
			 break;
			}
		}
		//---redirect
		if(empty($data['__ajaxResponse'])) {
			FHTTP::redirect(FSystem::getUri($redirParam));
		}

	}

	static function build($data=array()) {
		$user->pageVO->showHeading = false;                                         
		$user = FUser::getInstance();
		$cache = FCache::getInstance('s');
		
		

		$msgs = new FMessages($user->userVO->userId);
		//load from filter
		if(($filter = $cache->getData('text','filtrPost')) !== false) {
			$msgs->searchUser = $filter[0]; 
			$msgs->searchText = $filter[1];
		}

		$totalItems = $msgs->total();

    $pagerExtraVars = array();
		if(!empty($user->whoIs)) {
			$pagerExtraVars['who'] = $user->whoIs;
		}

		$perPage = $user->pageVO->perPage();
		$from = 0;
		if($totalItems > $perPage) {
			$pager = new FPager($totalItems,$perPage,array('extraVars'=>$pagerExtraVars));
			$from=($pager->getCurrentPageID()-1) * $perPage;
		}

		$arrpost = $msgs->load($from, $perPage);

		//---set default recipient
		$arrFriends = $user->userVO->getFriends();
		$recipientId = 0;
		$recipients = '';
		if(!empty($arrpost)) {
			if($arrpost[0]['userIdFrom']!=$user->userVO->userId) {
				$recipients = FUser::getgidname($arrpost[0]['userIdFrom']);
				$recipientId = $arrpost[0]['userIdFrom'];
			}
			elseif ($arrpost[0]['userIdTo']!=$user->userVO->userId) {
				$recipients =  FUser::getgidname($arrpost[0]['userIdTo']);
				$recipientId = $arrpost[0]['userIdTo'];
			}
		}
		//override recipient if from parameter
		if(!empty($user->whoIs)) {
			if($recipients = FUser::getgidname($user->whoIs)) $recipientId = $user->whoIs;
		}
		
		$tpl = FSystem::tpl('users.post.tpl.html');

		$tpl->setVariable('FORMACTION',FSystem::getUri());
		$tpl->touchBlock('selectedfriend');
		$tpl->touchBlock('friendscombo');

		if($recipientId > 0) {
			$tpl->setVariable('SELECTEDFRIENDAVATAR',FAvatar::showAvatar($recipientId));
			$tpl->setVariable('SELECTEDFRIENDNAME',FUser::getgidname($recipientId));
		}

		$tpl->setVariable('RECIPIENTS',$recipients);
		$tpl->setVariable('PERPAGE',$perPage);

		if ($msgs->searchText) $tpl->setVariable('FILTERTEXT',$msgs->searchText);
		if ($msgs->searchUser) $tpl->setVariable('FILTERUSERNAME',$msgs->searchUser);
		if ($msgs->searchText || $msgs->searchUser) {
			$tpl->setVariable('FILTRCANCELLINK',FSystem::getUri('filtr=cancel'));
		}

		if($totalItems > $perPage) {
			$tpl->setVariable('TOPPAGER',$pager->links);
			$tpl->setVariable('TOTAL',$totalItems);
			$tpl->setVariable('BOTTOMPAGER',$pager->links);
		}

		if(!empty($arrFriends)) {
			foreach ($arrFriends as $v) {
				$tpl->setVariable("FRIENDCOMBOID", $v);
				$tpl->setVariable("FRIENDCOMBONAME", FUser::getgidname($v));
				$tpl->parse("friendscombovalue");
			}
		}
		
		//---data printing
		if(!empty($arrpost)) {
			$cache = FCache::getInstance('s');
			$displayedMsgs = &$cache->getPointer('user');
			$displayedMsgs = array();
			foreach ($arrpost as $post) {
				$displayedMsgs[] = $post['postId'];
				if($post['userIdFrom'] != $user->userVO->userId) $tpl->setVariable("AVATAR", FAvatar::showAvatar($post['userIdFrom']));
				if($post["readed"]!=1) {
					$tpl->touchBlock("unread");
					$tpl->touchBlock("unreadmess");
				}
				$tpl->setVariable("ITEMIDHTML", $post['postId']);
				$tpl->setVariable("EDITID", $post['postId']);
				$tpl->setVariable("DATELOCAL", $post["datumcz"]);
				$tpl->setVariable("DATEISO", $post["datum"]);
				if($post["userIdFrom"]==$user->userVO->userId) {
					$tpl->touchBlock('sent');
					$mulink = FSystem::getUri("who=".$post["userIdTo"],'finfo');
					$muname = FUser::getgidname($post["userIdTo"]);
				} else {
					$tpl->touchBlock('received');
					$mulink = FSystem::getUri("who=".$post["userIdTo"],'finfo');
					$muname = FUser::getgidname($post["userIdFrom"]);
				}
				$tpl->setVariable("MULINK", $mulink);
				$tpl->setVariable("MUNAME", $muname);
				$tpl->setVariable("TEXT", FSystem::postText($post["text"]));
				$tpl->parse("message");

				/*prectena*/
				if ($post["userIdFrom"]!=$user->userVO->userId && $post["readed"]==0) {
					FDBTool::query("update sys_users_post set readed='1' where postId='".$post["postId"]."' || postIdFrom='".$post["postId"]."'");
				}
			}
		}

		if(!empty($data['__ajaxResponse'])) {
			if(isset($data['refreshPage')) {
			  FAjax::addResponse('messagesBox','$html',$tpl->get());
			} else {
				FAjax::addResponse('messages','$html',$tpl->get('message'));
			}
		} else {
			FBuildPage::addTab(array("MAINDATA"=>$tpl->get(),"MAINID"=>'messagesBox'));
			$tpl = FBuildPage::getInstance();
			$tpl->touchBlock('userPostInit');
		}
	}
}