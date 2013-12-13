<?php
/**
 *  TODO:
 *  fajax_class
 *  -js/ajax - on send clean textarea, refresh only msgs
 *           - on delete,perpage refresh only msgs
 *           - on search refresf whole page - add data['refreshPage']
 *           - if any checkbox is checked (user is going to delete) do not update msgs list - do not generate list so it is not marked readed
 *  - checked if any sent msgs are readed by recipient and update list also
 *  - if updating message because of unread
 *    reset pager if on more than 1st page
 *  	reset search
 **/
include_once('iPage.php');
class page_UserPost implements iPage {

	static function process($data) {
			
		$redirect = false;
		$redirParam = '';
		$user = FUser::getInstance();
		$cache = FCache::getInstance('s');

		$redir = false;
		if(isset($data['__get']['filtr'])) {
			if($data['__get']['filtr'] == 'cancel') {
				$cache->invalidateData($user->pageVO->pageId, 'filter');
			}
		}
		if(!isset($data['action'])) $data['action'] = false;
		if(isset($data["send"])) $data['action']='send';
    
		//---SEND MESSAGE
		if($data['action']=='send') {
			$data["text"] = FText::preProcess($data["text"]);
			$data["recipient"] = FText::preProcess($data["recipient"],array('plainText'=>1));
			if(empty($data["text"])) FError::add(FLang::$MESSAGE_EMPTY);
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
			}
			if(!Ferror::is()) {
				foreach ($arrto as $userId){
					FMessages::send($userId,$data["text"],$user->userVO->userId);
				}
				$redirect = true;
				$cache->invalidateData($user->pageVO->pageId, 'filter');
			}
		}

		if($data['action']=='special') $data['special'] = 'true';
		
		if(isset($data['special'])) {
			switch($data['saction']) {
				case 'setpp':
					$user->pageVO->perPage($data["perpage"]);
					break;
				case 'search':
					$searchData = array(FText::preProcess($data["recipient"],array('plainText'=>1)),FText::preProcess($data["text"],array('plainText'=>1)));
					$cache->setData($searchData, $user->pageVO->pageId, 'filter');
					$data['refreshPage'] = true;
					break;
				case 'delete':
				case 'deletebetween':
					if(empty($data["del"])) break;
					if($data['saction']=='deletebetween' && count($data["del"]) > 1) {
						$displayedMsgs = &$cache->getPointer('displayedMsgs');
						$to = array_pop($data['del']);
						$from = array_pop($data['del']);
						if(empty($displayedMsgs)) break;
						$firstIndex = array_search($from,$displayedMsgs);
						$lastIndex = array_search($to,$displayedMsgs)+1;
						$len = $lastIndex - $firstIndex;
						$data["del"] = array_slice($displayedMsgs, $firstIndex, $len);
						$displayedMsgs = null;
					}
					FMessages::delete($data["del"]);
					$redirect = true;
					break;
			}
		}

    
		//---redirect
		if(!$data['__ajaxResponse']) {
			if($redirect) FHTTP::redirect(FSystem::getUri($redirParam));
		} else {
			return $data;
		}

	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		//$user->pageVO->showHeading = false;
		$cache = FCache::getInstance('s');

		$msgs = new FMessages($user->userVO->userId);
		//load from filter
		$filter = $cache->getData($user->pageVO->pageId,'filter');
		if($filter !== false) {
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
			$options = array('extraVars'=>$pagerExtraVars,'bannvars'=>array('m','d'),'class'=>''); //TODO: implement history for ajax paging
			if(!empty($data['p'])) $options['manualCurrentPage'] = $data['p'];
			$pager = new FPager($totalItems,$perPage,$options);
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
		$tpl->setVariable('M','post-submit');
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
			$tpl->setVariable('BOTTOMPAGER',$pager->links);
		}

		if(!empty($arrFriends)) {
			foreach ($arrFriends as $v) {
				$tpl->setVariable("FRIENDCOMBOID", $v);
				$tpl->setVariable("FRIENDCOMBONAME", FUser::getgidname($v));
				$tpl->parse("friendscombovalue");
			}
		}

    FProfiler::write('UserPost::build - STEP 3');

		//---data printing
		if(!empty($arrpost)) {
			$cache = FCache::getInstance('s');
			$displayedMsgs = &$cache->getPointer('displayedMsgs');
			$displayedMsgs = array();
			foreach ($arrpost as $post) {
				$displayedMsgs[] = $post['postId'];
				if($post["readed"]!=1) {
					$tpl->touchBlock("unread");
				}
        FProfiler::write('UserPost::build - POST 1');
				$tpl->setVariable("ITEMIDHTML", $post['postId']);
				$tpl->setVariable("EDITID", $post['postId']);
				$tpl->setVariable("DATELOCAL", $post["datumcz"]);
				$tpl->setVariable("DATEISO", $post["datum"]);
				$tpl->setVariable("AVATAR", FAvatar::showAvatar($post['userIdFrom']));
				if($post["userIdFrom"]==$user->userVO->userId) {
					$tpl->touchBlock('sent');
					$mulink = FSystem::getUri("who=".$post["userIdTo"].'#tabs-profil','finfo');
					$muname = FUser::getgidname($post["userIdTo"]);
				} else {
					$tpl->touchBlock('received');
					$mulink = FSystem::getUri("who=".$post["userIdTo"].'#tabs-profil','finfo');
					$muname = FUser::getgidname($post["userIdFrom"]);
				}
        FProfiler::write('UserPost::build - POST 2');
				$tpl->setVariable("MULINK", $mulink);
				$tpl->setVariable("MUNAME", $muname);
				$tpl->setVariable("TEXT", FText::postProcess($post["text"]));
				$tpl->parse("message");

				/*prectena*/
				if ($post["userIdFrom"]!=$user->userVO->userId && $post["readed"]==0) {
					FDBTool::query("update sys_users_post set readed='1' where postId='".$post["postId"]."' or postIdFrom='".$post["postId"]."'");
				}
        FProfiler::write('UserPost::build - POST 3');
			}
		}

    FProfiler::write('UserPost::build - DONE');

		if($data['__ajaxResponse']) {
			if(isset($data['refreshPage'])) {
				FAjax::addResponse('messagesBox','$html',$tpl->get());
			} else {
				FAjax::addResponse('itemlist','$html',$tpl->get('message'));
				if(!empty($data['refreshPager'])) FAjax::addResponse('pager','$html',$pager->links);
			}
		} else {
			FBuildPage::addTab(array("MAINDATA"=>$tpl->get(),"MAINID"=>'messagesBox'));
		}
	}
}