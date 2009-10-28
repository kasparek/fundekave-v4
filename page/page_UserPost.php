<?php
include_once('iPage.php');
class page_UserPost implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		$cache = FCache::getInstance('s');
		//---action part - cache -pp,filtr
		$redir = false;
		if(isset($data['__get']['filtr'])) {
			if($data['__get']['filtr'] == 'cancel') {
				$cache->invalidateGroup('filtrPost');
				$redir = true;
			}
		}

		$perPage = $cache->getData($user->pageVO->pageId,'pp');
		if (isset($data["perpage"]) && $data["perpage"] != $perPage) {
			$perPage = $data["perpage"]*1;
			$cache->setData($perPage, $user->pageVO->pageId,'pp');
			$redir = true;
		}

		if (isset($data["filtr"])) {
			if(!empty($data["zprava"])) $cache->setData(FSystem::textins($data["zprava"]), 'text', 'filtrPost');
			$cache->setData(FSystem::textins($data["prokoho"],array('plainText'=>1)), 'name', 'filtrPost');
			$redir = true;
		}
		
		//---SEND MESSAGE
		if(isset($data["send"]) && $data["zprava"]!='') {
			$cache->invalidateGroup('filtrPost');
			if (!empty($data["prokoho"])) {
				$protmp=Explode(",",$data["prokoho"]);
				foreach ($protmp as $usrname) {
					if($pro = FUser::getUserIdByName(Trim($usrname))) $arrto[] = $pro;
					else $errjm[] = Trim($usrname);
				}
				if(!empty($errjm)) FError::addError(implode(", ",$errjm)." :: ".FLang::$MESSAGE_USERNAME_NOTEXISTS);
			}

			if (empty($arrto)) {
				FError::addError('postnoto');
				FUserDraft::save('postText',$data["zprava"]);
			} else {
				$zprava = FSystem::textins($data["zprava"]);
				if(!empty($zprava)) {
					foreach ($arrto as $komu){
						FMessages::send($komu,$zprava,$user->userVO->userId);
					}
					FUserDraft::clear('postText');
					$redir = true;
				}
			}
		}
				
		//---mazani zprav
		if ((isset($data["delo"]) || isset($data["delbe"])) && !empty($data["del"])) {
			if(isset($data["delbe"]) && Count($data["del"]) > 1){
				$cache = FCache::getInstance('s');
				$arrdelex = $cache->getData('displayed','post');
				$cache->invalidateData('displayed','post');
				$de=false;
				for($x=0;$x<count($arrdelex);$x++){
					if($arrdelex[$x]==$data["del"][0] && $de==false) $de=true;
					if($de==true) $arrdel[]=$arrdelex[$x];
					if($arrdelex[$x]==$data["del"][(Count($del)-1)]) {$de=false; break;}
				}
			} else {
				$arrdel = $data["del"];
			}
			FMessages::delete($arrdel);
			$redir = true;
		}
		
		//---redirect				
		if ($redir == true) {
			FHTTP::redirect(FSystem::getUri());
		}
		
	}

	static function build() {
		
		$user = FUser::getInstance();
		$cache = FCache::getInstance('s');

		$perPage = POST_PERPAGE;
		if(($pp = $cache->getData($user->pageVO->pageId,'pp')) !== false) $perPage = $pp;
		if($perPage < 2) $perPage = POST_PERPAGE;

		//load draft
		$zprava = FUserDraft::get('postText');
		//load from filter
		if(($filterText = $cache->getData('text','filtrPost')) !== false) $zprava = $filterText;

		//---filtering
		$pagerExtraVars = array();

		$totalItems = FMessages::load($user->userVO->userId,0,0,true);

		if(!empty($user->whoIs)) $pagerExtraVars['who'] = $user->whoIs;

		$od = 0;
		if($totalItems > $perPage) {
			$pager = new FPager($totalItems,$perPage,array('extraVars'=>$pagerExtraVars));
			$od=($pager->getCurrentPageID()-1) * $perPage;
		}

		$arrpost = FMessages::load($user->userVO->userId, $od, $perPage);

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
		if(!empty($user->whoIs)) {
			if($recipients = FUser::getgidname($user->whoIs)) $recipientId = $user->whoIs;
		}
		//override recipients if filtering
		if($filterUsername = $cache->getData('name','filtrPost')) $recipients = $filterUsername;


		$tpl = new FTemplateIT('users.post.tpl.html');

		$tpl->setVariable('FORMACTION',FSystem::getUri());
		$tpl->touchBlock('selectedfriend');
		$tpl->touchBlock('friendscombo');

		if($recipientId>0) {
			$tpl->setVariable('SELECTEDFRIENDAVATAR',FAvatar::showAvatar($recipientId));
			$tpl->setVariable('SELECTEDFRIENDNAME',FUser::getgidname($recipientId));
		}

		$tpl->setVariable('RECIPIENTS',$recipients);
		$tpl->setVariable('MESSAGE',$zprava);
		$tpl->addTextareaToolbox('MESSAGETOOLBOX','postText');
		$tpl->setVariable('HIDDENWHO',$user->whoIs);
		$tpl->setVariable('PERPAGE',$perPage);

		if ($filterText) {
			$tpl->setVariable('FILTERTEXT',$filterText);
		}
		if ($filterUsername) {
			$tpl->setVariable('FILTERUSERNAME',$filterUsername);
		}
		if ($filterText || $filterUsername) {
			$tpl->setVariable('FILTRCANCELLINK',FSystem::getUri('filtr=cancel'));
		}
		
		if($totalItems > $perPage) {
			$tpl->setVariable('TOPPAGER',$pager->links);
			$tpl->setVariable('TOTAL',$totalItems);
			$tpl->setVariable('BOTTOMPAGER',$pager->links);
		}

		if(!empty($arrFriends)) {
			foreach ($arrFriends as $v) {
				$tpl->setCurrentBlock("friendscombovalue");
				$tpl->setVariable("FRIENDCOMBOID", $v);
				$tpl->setVariable("FRIENDCOMBONAME", FUser::getgidname($v));
				$tpl->parseCurrentBlock();
			}
		}

		$displayedPostsArr=array();
		//---data printing

		if(!empty($arrpost)) {
			foreach ($arrpost as $post) {
				$tpl->setCurrentBlock("message");
				if($post['userIdFrom'] != $user->userVO->userId) $tpl->setVariable("AVATAR", FAvatar::showAvatar($post['userIdFrom']));
				if($post["readed"]!=1) {
					$tpl->touchBlock("unread");
					$tpl->touchBlock("unreadmess");
				}
				$tpl->setVariable("EDITID", $post['postId']);
				$tpl->setVariable("DATELOCAL", $post["datumcz"]);
				$tpl->setVariable("DATEISO", $post["datum"]);
				if($post["userIdFrom"]==$user->userVO->userId) {
					$tpl->setVariable("SENTLINK", FSystem::getUri("who=".$post["userIdTo"],'finfo'));
					$tpl->setVariable("SENTNAME", FUser::getgidname($post["userIdTo"]));
				} else {
					$tpl->setVariable("RECEIVEDLINK", FSystem::getUri("who=".$post["userIdTo"],'finfo'));
					$tpl->setVariable("RECEIVEDNAME", FUser::getgidname($post["userIdFrom"]));
				}
				$tpl->setVariable("TEXT", $post["text"]);
				$tpl->parseCurrentBlock();

				/*prectena*/
				if ($post["userIdFrom"]!=$user->userVO->userId && $post["readed"]==0) {
					$dot = "update sys_users_post set readed='1' where postId='".$post["postId"]."' || postIdFrom='".$post["postId"]."'";
					FDBTool::query($dot);
				}
				$displayedPostsArr[]=$post["postId"];
			}
			$cache = FCache::getInstance('s');
			$cache->setData($displayedPostsArr,'displayed','post');
		}
		
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
		$tpl = FBuildPage::getInstance();
		$tpl->touchBlock('userPostEND');
	}
}