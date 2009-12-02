<?php
class FForum extends FDBTool {

	static function messDel($id,$auditId=0) {
		if(!is_array($id)) $id = array($id);
		if(!empty($id)) {
			$userId = FUser::logon();
			foreach ($id as $delaud) {
				$itemVO = new ItemVO($delaud);
				if($itemVO->load()) {
					if(FRules::getCurrent(2) || $userId == $itemVO->userId) {
						$itemVO->delete();
					}
				}
				unset($itemVO);
			}
		}
	}

	static function messWrite($itemVO) {
		$itemVO->typeId = 'forum';
		if(empty($itemVO->pageId)) {
			$user = FUser::getInstance();
			$itemVO->pageId = $user->pageVO->pageId;
		}
		$ret = $itemVO->save();
		return $ret;
	}

	static function updateReadedReactions($itemId,$userId) {
		return FDBTool::query("insert into sys_pages_items_readed_reactions (itemId,userId,cnt,dateCreated) values ('".$itemId."','".$userId."',(select cnt from sys_pages_items where itemId='".$itemId."'),now()) on duplicate key update cnt=(select cnt from sys_pages_items where itemId='".$itemId."')");
	}

	static function setUnreadedMess($arrMessId) {
		$cache = FCache::getInstance('s');
		$unread = $cache->getData('unreadItems');
		if($unread===false) {
			$unread = array();
			$cache->setData($unread);
		}
		if(is_array($arrMessId)) {
			$cache->setData(array_merge($unread,$arrMessId));
		}
	}

	static function isUnreadedMess($messId,$unset=true){
		$ret=false;
		$cache = FCache::getInstance('s');
		$unread = $cache->getData('unreadItems');
		if($unread===false) $unread = array();
		if(in_array($messId,$unread)) {
			$ret = true;
			if( $unset ) {
				unset($unread[array_search($messId, $unread)]);
				$cache->setData($unread);
			}
		}
		return $ret;
	}

	static function clearUnreadedMess() {
		$cache = FCache::getInstance('s');
		$cache->invalidateData('unreadItems');
	}

	static function getSetUnreadedForum($id,$itemId){
		$user = FUser::getInstance();
		if($itemId == 0) $unreadedCnt = $user->pageVO->cnt - $user->pageVO->favoriteCnt;
		else {
			$dot = 'select i.cnt-r.cnt from sys_pages_items as i join sys_pages_items_readed_reactions as r on i.itemId=r.itemId and r.userId="'.$user->userVO->userId.'" and i.itemId="'.$itemId.'"';
			$unreadedCnt = FDBTool::getOne($dot);
		}
		$unreadedCnt = (($unreadedCnt < POSTS_UNREAD_MAX)?($unreadedCnt):(POSTS_UNREAD_MAX));
		if($unreadedCnt > 0 && $user->idkontrol) {
			$arrIds = FDBTool::getCol("select itemId from sys_pages_items
			where pageId='".$id."'".(($itemId>0)?(" and itemIdTop='".$itemId."'"):(''))." order by itemId desc limit 0,".$unreadedCnt);
			if(!empty($arrIds)) {
				$cache = FCache::getInstance('s');
				$unread = $cache->getData('unreadItems');
				if($unread===false) $unread = array();
				foreach ($arrIds as $messId) if(!in_array($messId,$unread)) $arrTmp[] = $messId;
				if(!empty($arrTmp)) FForum::setUnreadedMess($arrTmp);
			}
		}
		return $unreadedCnt;
	}

	/**
	 * process forum post
	 *
	 * @param array $data
	 * @param string $callbackFunction - function name
	 */
	static function process( $data, $callbackFunction=false) {
		$redirectParam = '';
		$user = FUser::getInstance();
		$pageId = $user->pageVO->pageId;
		$logon = $user->idkontrol;
		 
		if($user->itemVO) {
			$data['itemIdTop'] = $user->itemVO->itemId;
		}

		$redirect = false;

		if(isset($data["send"])) {

			$cache = FCache::getInstance('s',0);
			$cache->invalidateGroup('forumFilter');

			if (!empty($data["del"])) {
				FForum::messDel($data['del'],$pageId);
				$redirect = true;
			}

			if($logon !== true) {
				$captcha = new FCaptcha();
				if($captcha->validate_submit($data['captchaimage'],$data['pcaptcha'])) $cap = true; else $cap = false;
				unset($captcha);
			} else $cap = true;

			if(FUser::logon()) $jmeno = $user->userVO->name;
			elseif(isset($data["jmeno"])) $jmeno = trim($data["jmeno"]);

			if(isset($data["zprava"])) $zprava = trim($data["zprava"]);

			if($cap) {
				if((!empty($zprava))) {
					if(empty($jmeno)){
						FError::addError(FLang::$MESSAGE_NAME_EMPTY);
						$redirect = true;
					}
					if (FUser::isUsernameRegistered($jmeno) && $logon !== true){
						FError::addError(FLang::$MESSAGE_NAME_USED);
						$redirect = true;
					}

					if(!FError::isError()) {
						$jmeno = FSystem::textins($jmeno,array('plainText'=>1));
						if($logon === true) {
							$zprava = FSystem::textins($zprava);
						} else {
							$zprava = FSystem::textins($zprava,array('plainText'=>1));
						}

						//---insert
						$itemVO = new ItemVO();
						$itemVO->pageId = $pageId;
						$itemVO->userId = $user->userVO->userId;
						$itemVO->name = $jmeno;
						$itemVO->text = $zprava;
						if(!empty($itemIdBottom)) $itemVO->itemIdBottom = $itemIdBottom;
						if(!empty($pageIdBottom)) $itemVO->pageIdBottom = $pageIdBottom;
						if(!empty($data['itemIdTop'])) $itemVO->itemIdTop = $data['itemIdTop'];

						FForum::messWrite($itemVO);

						$cache = FCache::getInstance('s',0);
						$cache->invalidateData($pageId,'form');

						//---on success
						$redirectParam = '#dd';
						$redirect = true;
						unset($itemVO);
					}
				}
			} else {
				FError::adderror(FLang::$ERROR_CAPTCHA);
			}
			if(FError::isError()) {
				$formData = array("zprava"=>$zprava,"name"=>$jmeno);
				$cache = FCache::getInstance('s',0);
				$cache->setData($formData, $pageId, 'form');
			}
		}
		//---filtrovani
		if(isset($data["filtr"])) {
			$cache = FCache::getInstance('s',0);
			$cache->setData(FSystem::textins($data["zprava"],array('plainText'=>1)), $pageId, 'filter');
		}
		//---per page
		if (isset($data["perpage"])) {
			$perPage = $user->pageVO->perPage();
			if($data["perpage"] != $perPage) {
				$user->pageVO->perPage( $data["perpage"] );
			}
		}
		//---redirect
		if($redirect==true) {
			$cache = FCache::getInstance('f');
			$cache->invalidateData('lastForumPost');
			if($callbackFunction) call_user_func($callbackFunction);
			FHTTP::redirect(FSystem::getUri($redirectParam));
		}
	}

	/*
	 * forum Print
	 public write - 0:no write,1:public,2:only registered
	 */
	function show($itemId = 0,$publicWrite=1,$itemIdInside=0,$paramsArr=array()) {
		$itemId = (int) $itemId;
		$user = FUser::getInstance();
		$pageId = $user->pageVO->pageId;
		$logged = $user->idkontrol;
		FProfiler::profile('FForum::show--INSTANCES');
		$simple = false;
		if(isset($paramsArr['simple'])) $simple = $paramsArr['simple'];
			
		$zprava = '';
		//---available params
		$formAtEnd = false;
		$showHead = true;
		extract($paramsArr);
			
		if($logged === false && $publicWrite > 0) { $captcha = new FCaptcha(); }
			
		$perPage = $user->pageVO->perPage();
		
		$unreadedCnt = 0;	
		if( $logged === true ) {
			$unreadedCnt = FForum::getSetUnreadedForum($user->pageVO->pageId,$itemId);
			if($unreadedCnt > 0) {
				if($unreadedCnt > 20 || $perPage <= $unreadedCnt) $perPage = $unreadedCnt + 5;
				elseif($unreadedCnt > 100) $perPage = 100;
			}
		}

		//---DEEPLINKING
		$manualCurrentPage = 0;
		if($user->itemVO || $itemIdInside > 0) {
			if($user->itemVO->itemId > 0 && $user->itemVO->typeId=='forum') {
				$itemIdInside = $user->itemVO->itemId;
			}
			//---find a page of this item to have link to it
			if($itemIdInside > 0) {
				$itemVO = new ItemVO($itemIdInside,true);
				$manualCurrentPage = $itemVO->onPageNum();
				unset($itemVO);
				$perPage = $user->pageVO->perPage();

			}
		}
		FProfiler::profile('FForum::show--DEEPLINKING');

		/* ........ vypis nazvu auditka .........*/
		//--FORM
		$tpl = FSystem::tpl('forum.view.tpl.html');
		if($showHead===true) {
			$desc = $user->pageVO->content;
			if(!empty($desc)) $tpl->setVariable('PAGEDESC',FSystem::postText($desc));
		}
		if($user->pageVO->locked == 0 && $publicWrite > 0) {
			$tpl->setVariable('FORMACTION',FSystem::getUri());
			$name = "";
			$cache = FCache::getInstance('s',0);
			$formData = $cache->getData( $user->pageVO->pageId, 'form');
			if($formData !== false) {
				$zprava = $formData['zprava'];
				$name = $formData['name'];
				$cache->invalidateData($user->pageVO->pageId, 'form');
			}
			if ($logged===true) {
				$tpl->setVariable('USERNAMELOGGED',$user->userVO->name);
			} else {
				$tpl->setVariable('USERNAMENOTLOGGED',$name);
				$src = $captcha->get_b2evo_captcha();
				unset($captcha);
				$tpl->setVariable('CAPTCHASRC',$src);
			}
			$tpl->setVariable('TEXTAREAID','forum'.$user->pageVO->pageId);

			$cache = FCache::getInstance('s',0);
			$filter = $cache->getData( $user->pageVO->pageId, 'filter');
			$tpl->setVariable('TEXTAREACONTENT',(($filter!==false)?($filter):($zprava)));

			if ($logged===true) {
				if($simple===false) {
					$tpl->touchBlock('userlogged2');
					$tpl->setVariable('PERPAGE',$perPage);
				}
			}
		} elseif($publicWrite == 2) {
			$tpl->setVariable('READONLY',FLang::$MESSAGE_FORUM_REGISTEREDONLY);
		} else {
			$tpl->setVariable('READONLY',FLang::$MESSAGE_FORUM_READONLY);
		}
		FProfiler::profile('FForum::show--FORM');
		//---END FORM
		
		
		if($itemId>0) {
			$itemVO = new ItemVO($itemId,true);
			$total = $itemVO->cnt;
		} else {
			$total = $user->pageVO->cnt;
		}
		
		$cached = false;
		if(empty($filterTxt)) {
			$ppUrlVar = FConf::get('pager','urlVar');
			$pageNum = 1;
			if(isset($_GET[$ppUrlVar])) $pageNum = (int) $_GET[$ppUrlVar];
			$cache = FCache::getInstance('f',0);
			$cacheKey = $user->pageVO->pageId.'-'.$pageNum.'-'.$itemId.'-'.(int) $user->userVO->userId.'-'.$perPage;
			$cacheGrp = 'pagelist';
			$cached = $cache->getData($cacheKey,$cacheGrp);
		}
		
		if($cached===false) {
			$cached = array();
		
			$itemRenderer = new FItemsRenderer();
			$fItems = new FItems('forum',false,$itemRenderer);
			$fItems->addWhere("pageId='".$user->pageVO->pageId."'");
			if(!empty($itemId)) $fItems->addWhere("itemIdTop='".$itemId."'");
			if(!empty($filterTxt)) {
				$fItems->addWhereSearch(array('name','text','enclosure','dateCreated'),$filterTxt,'or');
			}
			$fItems->setOrder("dateCreated DESC");
			
			//TODO:thumbs removed for while
			//FItemsToolbar::setQueryTool(&$fItems);
	
			if(!empty($user->whoIs)) $arrPagerExtraVars = array('who'=>$who); else $arrPagerExtraVars = array();
			if($itemIdInside > 0) $arrPagerExtraVars['k'] = $user->pageVO->pageId;
			$pager = new FPager(0,$perPage,array('extraVars'=>$arrPagerExtraVars,'noAutoparse'=>1,'bannvars'=>array('i'),'manualCurrentPage'=>$manualCurrentPage));
	
			$from = ($pager->getCurrentPageID()-1) * $perPage;
			$fItems->getList($from,$perPage+1);
			$total = count($fItems->data);
	
			$maybeMore = false;
			if($total > $perPage) {
				$maybeMore = true;
				unset($fItems->data[(count($fItems->data)-1)]);
			}
	
			if($from > 0) $total += $from;
	
			if($total > 0) {
				/*.........zacina vypis prispevku.........*/
				$pager->totalItems = $total;
				$pager->maybeMore = $maybeMore;
				$pager->getPager();
				if ($total > $perPage) {
					$cached['TOPPAGER'] = $pager->links;
					$cached['BOTTOMPAGER'] = $pager->links;
				}
				$mess = '';
	
				$cached['MESSAGES'] = $fItems->render();
      	if(isset($cacheKey)) if($unreadedCnt==0) $cache->setData($cached,$cacheKey,$cacheGrp);
			}
		}
		
		$tpl->setVariable($cached); 
		
		if($total > 0) {
			/*......aktualizace novych a prectenych......*/
			if($itemId > 0) {
				FForum::updateReadedReactions($itemId,$user->userVO->userId);
			} else {
				FItems::aFav($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->cnt);
			}
		} else {
		  $tpl->touchBlock('messno');
		}

		$ret = $tpl->get();
		unset($itemRenderer);
		unset($fItems);
		unset($pager);
		unset($tpl);
		return $ret;

	}

}