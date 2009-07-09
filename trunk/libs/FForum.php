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
			 
			if(isset($data["objekt"])) {
				$objekt = trim($data["objekt"]);
			}
			 
			if($cap) {

				if(isset($objekt)) {
					//---check for item
					if(preg_match("/[&?|]i=([0-9]*)/" , $objekt, $matches)) {
						//check if it is item
						if(FItems::itemExists($matches[1])) {
							$itemIdBottom = $matches[1];
							$objekt = '';
						}
					} else if(preg_match("/[&?|]k=([0-9a-zA-Z]*)/" , $objekt, $matches)) {
						if(FPages::page_exist('pageId',$matches[1])) {
							$pageIdBottom = $matches[1];
							$objekt = '';
						}
					}
				}
			
				if((!empty($zprava) || !empty($objekt))) {
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
							if(isset($objekt)) {
								$objekt = FSystem::textins($objekt,array('plainText'=>1));
							}
						} else {
							$zprava = FSystem::textins($zprava,array('plainText'=>1));
							unset($objekt);
						}
				
						//---insert
						$itemVO = new ItemVO();
						$itemVO->pageId = $pageId;
						$itemVO->userId = $user->userVO->userId;
						$itemVO->name = $jmeno;
						$itemVO->text = $zprava;
						if(!empty($objekt)) {
							$itemVO->enclosure = $objekt;
						}
						if(!empty($itemIdBottom)) $itemVO->itemIdBottom = $itemIdBottom;
						if(!empty($pageIdBottom)) $itemVO->pageIdBottom = $pageIdBottom;
						if(!empty($data['itemIdTop'])) $itemVO->itemIdTop = $data['itemIdTop'];

						FForum::messWrite($itemVO);

						$cache = FCache::getInstance('s',0);
						$cache->invalidateData($pageId,'form');

						//---on success
						if(FUser::logon()) FUserDraft::clear('forum'.$pageId);
						$redirect = true;
						unset($itemVO);
					}
				}
			} else {
				FError::adderror(FLang::$ERROR_CAPTCHA);
			}
			if(FError::isError()) {
				$formData = array("zprava"=>$zprava,"objekt"=>(isset($objekt))?($objekt):(''),"name"=>$jmeno);
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
		$cache = FCache::getInstance('s',0);
		if(($perPage = $cache->getData($pageId,'pp')) === false) $perPage = $user->pageVO->perPage();
		
		if (isset($data["perpage"]) && $data["perpage"] != $perPage) {
			$perPage = $data["perpage"]*1;
			if($perPage < 2) $perPage = 10;
			$cache->setData($perPage, $pageId,'pp');
		}
		//---redirect
		if($redirect==true) {
			$cache = FCache::getInstance('f');
			$cache->invalidateData('lastForumPost');
			if($callbackFunction) call_user_func($callbackFunction);
			FHTTP::redirect(FUser::getUri());
		}
	}

	/*
	 * forum Print
	 public write - 0:no write,1:public,2:only registered
	 */
	function show($itemId = 0,$publicWrite=1,$itemIdInside=0,$paramsArr=array()) {
		$user = FUser::getInstance();
		$pageId = $user->pageVO->pageId;
		FProfiler::profile('FForum::show--INSTANCES');
	  
		$zprava = '';
		//---available params
		$formAtEnd = false;
		$showHead = true;
		extract($paramsArr);
	  
		if(FUser::logon() === false && $publicWrite > 0) { $captcha = new FCaptcha(); }
	  
		$cache = FCache::getInstance('s',0);
		if(($perPage = $cache->getData($pageId,'pp')) === false) $perPage = $user->pageVO->perPage('forum');
		FProfiler::profile('FForum::show--PERPAGE');
	  
		if( FUser::logon() ) {
			$unreadedCnt = FForum::getSetUnreadedForum($user->pageVO->pageId,$itemId);
			if($unreadedCnt > 0) {
				if($unreadedCnt > 20 || $perPage <= $unreadedCnt) $perPage = $unreadedCnt + 5;
				elseif($unreadedCnt > 100) $perPage = 100;
			}
		}
		FProfiler::profile('FForum::show--UNREADED');
		
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
		$tpl = new FTemplateIT('forum.view.tpl.html');
		
		if($showHead===true) {
			$desc = $user->pageVO->content;
			if(!empty($desc)) $tpl->setVariable('PAGEDESC',$desc);
		}
		if($user->pageVO->locked == 0 && $publicWrite > 0) {
			$tpl->setVariable('FORMACTION',FUser::getUri());
			$name = "";
			if($user->idkontrol) $zprava = FUserDraft::get('forum'.$user->pageVO->pageId);
			 
			$cache = FCache::getInstance('s',0);
			$formData = $cache->getData( $user->pageVO->pageId, 'form');
			if($formData !== false) {
				$zprava = $formData['zprava'];
				$objekt = $formData['objekt'];
				$name = $formData['name'];
				$cache->invalidateData($user->pageVO->pageId, 'form');
			}
			if ($user->idkontrol) {
				$tpl->setVariable('USERNAMELOGGED',$user->userVO->name);
			} else {
				$tpl->setVariable('USERNAMENOTLOGGED',$name);
				$src = $captcha->get_b2evo_captcha();
				unset($captcha);
				$tpl->setVariable('CAPTCHASRC',$src);
			}
			$tpl->setVariable('TEXTAREAID','forum'.$user->pageVO->pageId);
			$tpl->addTextareaToolbox('TEXTAREATOOLBOX','forum'.$user->pageVO->pageId);

			$cache = FCache::getInstance('s',0);
			$filter = $cache->getData( $user->pageVO->pageId, 'filter');
			$tpl->setVariable('TEXTAREACONTENT',(($filter!==false)?($filter):($zprava)));

			if ($user->idkontrol) {
				$tpl->touchBlock('userlogged');
				$tpl->touchBlock('userlogged2');
				$tpl->setVariable('PERPAGE',$perPage);
			}
		} elseif($publicWrite == 2) {
			$tpl->setVariable('READONLY',FLang::$MESSAGE_FORUM_REGISTEREDONLY);
		} else {
			$tpl->setVariable('READONLY',FLang::$MESSAGE_FORUM_READONLY);
		}
		FProfiler::profile('FForum::show--FORM');
		//---END FORM
		$itemRenderer = new FItemsRenderer();
		$fItems = new FItems('forum',false,$itemRenderer);
		$fItems->addWhere("pageId='".$user->pageVO->pageId."'");
		if(!empty($itemId)) $fItems->addWhere("itemIdTop='".$itemId."'");
		if(!empty($filterTxt)) {
			$fItems->addWhereSearch(array('name','text','enclosure','dateCreated'),$filterTxt,'or');
		}
		$fItems->setOrder("dateCreated DESC");
		FItemsToolbar::setQueryTool(&$fItems);
		
		if(!empty($user->whoIs)) $arrPagerExtraVars = array('who'=>$who); else $arrPagerExtraVars = array();
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
		
		FProfiler::profile('FForum::show--ITEMS INIT');

		if($total > 0) {
			/*.........zacina vypis prispevku.........*/
			$pager->totalItems = $total;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();
			if ($total > $perPage) {
				$tpl->setVariable('TOPPAGER',$pager->links);
				$tpl->setVariable('BOTTOMPAGER',$pager->links);
			}
			$mess = '';
			 
			$tpl->setVariable('MESSAGES',$fItems->render());
			if($formAtEnd===true) {
				//---remove posts block and place it on POSTSONTOP
				//TODO: think if needed form on end
				//$tpl->moveBlock('posts','POSTSONTOP');
				 
			}
			FProfiler::profile('FForum::show--ITEMS DONE');
			/*......aktualizace novych a prectenych......*/
			if($itemId>0) FForum::updateReadedReactions($itemId,$user->userVO->userId);
			else FItems::aFav($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->cnt);
			FProfiler::profile('FForum::show--READED UPDATE');
		} else $tpl->touchBlock('messno');

    $ret = $tpl->get();
    unset($itemRenderer);
    unset($fItems);
    unset($pager);
    unset($tpl);
	return $ret;
		
	}

}