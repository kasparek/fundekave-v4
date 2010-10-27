<?php
include_once('iPage.php');
class page_ItemsList implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {
		//form is processed in FAjax_item::submit
	}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
		if(isset($data['__get']['date'])) {
			$date = FSystem::checkDate($data['__get']['date']);
		}
		//var setup
		$user = FUser::getInstance();
		if($user->itemVO) {
			if($user->itemVO->itemId>0) $itemVO = $user->itemVO;
		}
		if(!empty($data['itemId'])) {
			$itemVO = new ItemVO($data['itemId']);
			if(!$itemVO->load()) $itemVO = null;
		}

		$pageVO = $user->pageVO;
		if(!empty($itemVO)) {
			$pageVO = $itemVO->pageVO;
		}

		if(FRules::getCurrent(2)) {
			if(empty($user->pageParam) && empty($user->itemVO) && $pageVO->typeId=='blog') {
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:blog',$user->pageVO->pageId), FLang::$LABEL_ADD,array('class'=>'fajaxa'));
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:forum',$user->pageVO->pageId), FLang::$LABEL_FORUM_NEW,array('class'=>'fajaxa'));
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:event',$user->pageVO->pageId), FLang::$LABEL_EVENT_NEW,array('class'=>'fajaxa'));
			}

		}

		//perpage based on unreaded items
		if( $user->idkontrol ) {
			$unreadedCnt = FItems::cacheUnreadedList();
			if($unreadedCnt > 0 && $unreadedCnt > $pageVO->perPage()) $pageVO->perPage($unreadedCnt + 3);
		}

		//---DEEPLINKING for forum pages
		$manualCurrentPage = 0;
		if(!empty($itemVO)) {
			if($itemVO->typeId=='forum' && $itemVO->pageVO->get('typeId')=='forum') {
				$manualCurrentPage = $itemVO->onPageNum();
			}
		}

		$output = '';
		$template = 'page.items.list.tpl.html';
		$touchedBlocks = array();
		$vars = array();
		$itemId = 0;

		$categoryId=0;
		if(isset($data['__get']['c'])) {
			$categoryId = (int) $data['__get']['c']; //for category filtering
		}
		$arrPagerExtraVars = array();
		if($categoryId>0) $arrPagerExtraVars['c'] = $categoryId;
		if(!isset($_REQUEST['k'])) $arrPagerExtraVars['k'] = $user->pageVO->pageId;
		if(!empty($user->whoIs)) $arrPagerExtraVars['who'] = $who;
		$pagerOptions = array('manualCurrentPage'=>$manualCurrentPage);
		if(!empty($itemVO)) {
			$arrPagerExtraVars['k'] = $pageVO->pageId;
			$arrPagerExtraVars['i'] = $itemId;
		} else {
			$pagerOptions['bannvars']=array('i');
		}
		$pagerOptions['extraVars']=$arrPagerExtraVars;

		/**
		 *FORM FOR EDIT ITEM
		 *- if in edit mode show edit form - blog,event from will redirect to detail view, only forum/foto form will show here
		 *if in edit mode - param u or forum display form
		 **/
		$vars['EDITFORM']='';
		if($user->pageParam=='u' && !empty($itemVO)) {
			if(FRules::getCurrent(2)) {
				$vars['EDITFORM'] = FItemsForm::show($itemVO);
			}
		}

		/**
		 *ITEM DETAIL
		 **/
		if(!empty($itemVO)) {
			if($itemVO->pageVO->get('typeId')!='forum') {
				//show item detail
				$vars['DETAIL'] = page_ItemDetail::build($data);
			} else {
				$itemVO = null;
			}
		}

		//continue only if empty $user->pageParam
		if(empty($user->pageParam) || $user->pageParam=='o') { //TODO: not great implementation
		//filter-search
		$cache = FCache::getInstance('s',0);
		$searchStr = $cache->getData( $pageVO->pageId, 'filter');
		/**
		 *FORUM FORM
		 */
		$writePerm=1;
		if($pageVO->typeId!='top') { //no show for live, main etc.
			if($pageVO->typeId == 'forum' && $pageVO->locked>0) $writePerm=0;
			if($pageVO->typeId == 'blog' || $pageVO->typeId == 'galery' || $pageVO->typeId == 'event') {
				$writePerm = $pageVO->prop('forumSet');
				if(!empty($itemVO)) {
					if($writePerm==1) $writePerm = $itemVO->prop('forumSet');
					$data['simple'] = true;
				} else {
					$writePerm=0;
				}
			}
			if($writePerm==1 || ($writePerm==2 && $user->idkontrol)) {
				$formItemVO = new ItemVO();
				$formItemVO->typeId = 'forum';
				$formItemVO->pageId = $pageVO->pageId;
				$data['perpage'] = $pageVO->perPage();
				if($searchStr!==false) $data['text'] = $searchStr;
				$vars['MESSAGEFORM'] = FItemsForm::show($formItemVO,$data);
			}
			if($writePerm == 2 && !$user->idkontrol) {
				$vars['MESSAGE'] = FLang::$MESSAGE_FORUM_REGISTEREDONLY;
			}
		}

		//HEADER
		if(empty($itemVO) && !empty($pageVO->content)) {
			$vars['CONTENT'] = FSystem::postText($pageVO->content);
		}

		if(empty($itemVO) || $writePerm>0) {

			//LIST ITEMS
			$fItems = new FItems('',FUser::logon());
			if($pageVO->typeId!='top') {
				$fItems->setPage($pageVO->pageId);
				$fItems->hasReactions($pageVO->typeId!='forum' && empty($itemVO) ? false : true);
			}
			if($categoryId > 0) {
				$fItems->addWhere("categoryId='". $categoryId ."'");
			}
			if(!empty($searchStr)) {
				$fItems->addWhereSearch(array('name','text','enclosure','dateCreated','location','addon'),$searchStr,'or');
			}
			if(!empty($itemVO)) {
				$itemId = $itemVO->itemId;
				$fItems->addWhere("itemIdTop='".$itemVO->itemId."'"); //displaying reactions
			}
			if(SITE_STRICT == 1) {
				$fItems->addWhere("pageIdTop = '".HOME_PAGE."'");
			}

			if(!empty($date)) {
				//used for sorting
				$fItems->addSelect("if( sys_pages_items.typeId='forum', sys_pages_items.dateCreated, sys_pages_items.dateStart) as dateLive");
				$fItems->addWhere("(sys_pages_items.typeId='forum' and '".$date."'=date_format(sys_pages_items.dateCreated,'%Y-%m-%d')) "
				."or (sys_pages_items.typeId in ('blog','galery') and '".$date."'=date_format(sys_pages_items.dateStart,'%Y-%m-%d')) "
				."or (sys_pages_items.typeId='event' and '".$date."'>=date_format(sys_pages_items.dateStart,'%Y-%m-%d') and '".$date."'<=date_format(sys_pages_items.dateEnd,'%Y-%m-%d'))"
				);
				$fItems->setOrder('dateLive desc');
			} else {
				//ORDER
				if($pageVO->pageId=='event') {
					$fItems->addWhere("typeId='event'");
					if($user->pageParam=='o') {
						//---archiv
						FMenu::secondaryMenuAddItem(FSystem::getUri('','',''),FLang::$BUTTON_PAGE_BACK);
						$fItems->addWhere("dateStart < date_format(NOW(),'%Y-%m-%d')");
						$fItems->setOrder('dateStart desc');
					} else {
						//---future
						FMenu::secondaryMenuAddItem(FSystem::getUri('','','o'),FLang::$LABEL_EVENTS_ARCHIV);
						$fItems->addWhere("(dateStart >= date_format(NOW(),'%Y-%m-%d') or (dateEnd is not null and dateEnd >= date_format(NOW(),'%Y-%m-%d')))");
						$fItems->setOrder('dateStart');
					}
				} else {
					if(!empty($itemVO)) {
						//reactions
						$fItems->setOrder('dateCreated desc');
					} else {
						$fItems->setOrder($pageVO->itemsOrder());
					}
				}
			}

			if(!empty($itemVO)) {
				$itemVO->updateReaded($user->userVO->userId);
			} else {
				$pageVO->updateReaded($user->userVO->userId);
			}

			$listArr = page_PageItemList::buildList($fItems,$pageVO,$pagerOptions);
			$vars = array_merge($vars,$listArr['vars']);
			if(!empty($listArr['blocks'])) $touchedBlocks = array_merge($touchedBlocks,$listArr['blocks']);

		}
		}
		if(!empty($itemVO)) {
			$touchedBlocks[]='comm';
		}
		if(!empty($data['__ajaxResponse'])) {
			FAjax::addResponse('commentForm','action',FSystem::getUri('','',false,array('short'=>1)));
			FAjax::addResponse('itemFeed','$html',empty($vars['ITEMS']) ? '' : $vars['ITEMS']);
			FAjax::addResponse('topPager','$html',empty($vars['TOPPAGER']) ? '' : $vars['TOPPAGER']);
			FAjax::addResponse('bottomPager','$html',empty($vars['BOTTOMPAGER']) ? '' : $vars['BOTTOMPAGER']);
			FAjax::addResponse('pageHead','$html',FBuildPage::getHeading());
			FAjax::addResponse('document','title',FBuildPage::getTitle());
			FAjax::addResponse('call','fajaxInit');
			FAjax::addResponse('call','GooMapi.init');
		} else {
			//render to template
			$tpl = FSystem::tpl($template);
			if(!empty($touchedBlocks)) $tpl->touchBlock( $touchedBlocks );
			$tpl->setVariable($vars);
			$output .= $tpl->get();
			//output
			FBuildPage::addTab(array("MAINDATA"=>$output));
		}
	}

	static function buildList($fItems,$pageVO,$pagerOptions=array()) {
		$touchedBlocks = array();
		$vars = array();
		$pagerOptions['noAutoparse']=1;
		$perPage = $pageVO->perPage();
		$pager = new FPager(0,$perPage,$pagerOptions);
		$from = ($pager->getCurrentPageID()-1) * $perPage;

		$uid = $fItems->getUID($from, $perPage+1);
		$grpid = 'itemlist'.($pageVO->typeId!='top'?$pageVO->pageId:'');
		$cache = FCache::getInstance('f');
		$data = $cache->getData($uid,$grpid);

		if($data===false) {
			$fItems->getList($from, $perPage+1);
			$pager->totalItems = count($fItems->data);
			if($pager->totalItems > $perPage) {
				$pager->maybeMore = true;
				array_pop($fItems->data);
			}
			$vars['TOTALITEMS'] = $pager->maybeMore ? $perPage.'+' : count($fItems->data);
			if($from > 0) $pager->totalItems += $from;
			if($pager->totalItems > 0) {
				$pager->getPager();
				if ($pager->totalItems > $perPage) {
					$vars['TOPPAGER'] = $pager->links;
					$vars['BOTTOMPAGER'] = $pager->links;
				}
				if(!$fItems->fItemsRenderer) $fItems->fItemsRenderer = new FItemsRenderer();
				$itemPrev=null;
				$typeIdPrev=null;
				$itemIdTopPrev=null;
				$pageIdPrev=null;
				while ($itemVO = array_shift($fItems->data)) {
					if($pageVO->pageId != $itemVO->pageId) {
						$fItems->fItemsRenderer->showPage=false;
						if(!$itemPrev || $pageIdPrev != $itemVO->pageId || $itemIdTopPrev!=$itemVO->itemIdTop) {
							//setup renderer if needed
							if($itemVO->itemIdTop > 0) {
								//show top item
								$itemTop = new ItemVO($itemVO->itemIdTop);
								//figure out how to display showPage for galery items
								if($itemTop->get('typeId')=='galery') $fItems->fItemsRenderer->showPage=true;
								$itemTop->render($fItems->fItemsRenderer);
								$last = $fItems->fItemsRenderer->getLast();
								$last = str_replace($itemVO->itemIdTop.'" class="hentry',$itemVO->itemIdTop.'" class="hentry opacity',$last);
								$fItems->fItemsRenderer->setLast($last);
								$fItems->fItemsRenderer->showPage=false;
							} elseif(($itemVO->typeId=='forum' || $itemVO->typeId=='galery')) {
								$fItems->fItemsRenderer->showPage=true;
							}
						}
					}
					$fItems->parse($itemVO);
					if($itemVO->itemIdTop > 0 && $pageVO->pageId != $itemVO->pageId) {
						$last = $fItems->fItemsRenderer->getLast();
						$last = str_replace('class="hentry','class="hentry reaction',$last);
						$fItems->fItemsRenderer->setLast($last);
					}

					$itemPrev = $itemVO;
					$typeIdPrev = $itemVO->typeId;
					$itemIdTopPrev = $itemVO->itemIdTop;
					$pageIdPrev = $itemVO->pageId;
					if($itemVO->isUnreaded) {
						$readed[] = $itemVO->itemId;
					}

					if($itemVO->typeId=='event') if(!in_array('fcalendar',$touchedBlocks)) $touchedBlocks[]='fcalendar';
				}
				$vars['ITEMS'] = $fItems->show();
			} else {
				if(!empty($writePerm)) $touchedBlocks[]='feedempty';
			}
			$data = array('vars'=>$vars,'blocks'=>$touchedBlocks);
			if(!empty($readed)) {
				FCommand::run(ITEM_READED,$readed);
			} else {
				$cache->setData($data,$uid,$grpid);
			}
		}
		return $data;
	}
}