<?php
include_once('iPage.php');
class page_PageItemList implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {
		$user = FUser::getInstance();
		//form is processed in FAjax_item::submit

		//TODO: check rules for writing items
		//-if page is forum write anything but no reactions
		//TODO: process form data base on _GET['t'] typeid parameter from anywhere
		//if not empty user->itemVO it is reaction - no on forum item?

		//TODO: process data depend on form used
		//will be something like FItem::process($data);
		//FForum::process($data);

		//TODO: refactor
		//FEvents::process( $data ); || FEvents::processForm($data, true); for FEvents::editForm($itemId)


	}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
		if(isset($data['__get']['date'])) {
			$date = FSystem::checkDate($data['__get']['date']);
		}
		//vcalendar
		//TODO: page description into page descrption
		//TODO: if any forum item do touch vcalendar?
		//feedempty-x

		//TODO: based on permission button to add event, blog

		//TODO: refactor
		//FMenu::secondaryMenuAddItem(FSystem::getUri('','eveac'),FLang::$LABEL_EVENTS_ARCHIV); - if empty pageparam and only events view
		//FEvents::view();
		//FBuildPage::addTab(array("MAINDATA"=>FBlog::listAll($itemId,(($user->pageParam == 'u')?(true):(false))),"MAINID"=>'bloged'));
		//FBuildPage::addTab(array("MAINDATA"=>FForum::show()));

		//BLOG - new item buttton


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
			if(empty($user->pageParam) && !$user->itemVO && $pageVO->typeId!='top' && $pageVO->typeId!='galery') {
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:blog',$user->pageVO->pageId,'a'), FLang::$LABEL_ADD,array('class'=>'fajaxa'));
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:forum',$user->pageVO->pageId,'a'), FLang::$LABEL_ADD,array('class'=>'fajaxa'));
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:event',$user->pageVO->pageId,'a'), FLang::$LABEL_ADD,array('class'=>'fajaxa'));
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
			if($itemVO->typeId=='forum') {
				$manualCurrentPage = $itemVO->onPageNum();
			}
		}

		$output = '';
		$template = 'page.items.list.tpl.html';
		$touchedBlocks = array();
		$vars = array();
		$itemId = 0;
			
		$perPage = $pageVO->perPage();
		$pageNumUrlVar = FConf::get('pager','urlVar');
		$categoryId=0;
		if(isset($data['c'])) $categoryId = (int) $data['c']; //for category filtering
		$arrPagerExtraVars = array();
		if(!isset($_REQUEST['k']))  $arrPagerExtraVars['k'] = $user->pageVO->pageId;
		if(!empty($user->whoIs)) $arrPagerExtraVars['who'] = $who;

		/**
		 *FORM FOR EDIT ITEM
		 *- if in edit mode show edit form - blog,event from will redirect to detail view, only forum/foto form will show here
		 *if in edit mode - param u or forum display form
		 **/
		if($user->pageParam=='u' && !empty($itemVO)) {
			if(FRules::getCurrent(2)) {
				$vars['EDITFORM'] = FItemsForm::show($itemVO);
			}
		}

		/**
		 *ITEM DETAIL
		 **/
		if(!empty($itemVO)) {
			if($itemVO->typeId!='forum') {
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
			if($pageVO->typeId!='top') { //no show for live, main etc.
				$writePerm=1;
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
					$data['perpage'] = $perPage;
					if($searchStr!==false) $data['text'] = $searchStr;
					$vars['MESSAGEFORM'] = FItemsForm::show($formItemVO,$data);
				}
				if($writePerm == 2 && !$user->idkontrol) {
					$vars['MESSAGE'] = FLang::$MESSAGE_FORUM_REGISTEREDONLY;
				}
			}

			//HEADER
			if(empty($itemVO) && !empty($pageVO->content)) $vars['CONTENT'] = FSystem::postText($pageVO->content);

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
					if($user->pageParam=='o') { //TODO:archive base on pageparam
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
						//TODO: user order from page
						$fItems->setOrder($pageVO->itemsOrder());
					}
				}
			}

			if(!empty($itemVO)) {
				$itemVO->updateReaded($user->userVO->userId);
			} else {
				$pageVO->updateReaded($user->userVO->userId);
			}

			if(!empty($itemVO)) {
				$arrPagerExtraVars['k'] = $pageVO->pageId;
				$arrPagerExtraVars['i'] = $itemId;
			}
			$pager = new FPager(0,$perPage,array('extraVars'=>$arrPagerExtraVars,'noAutoparse'=>1,'bannvars'=>array('i'),'manualCurrentPage'=>$manualCurrentPage));
			$from = ($pager->getCurrentPageID()-1) * $perPage;
			$fItems->getList($from, $perPage+1);
			$pager->totalItems = count($fItems->data);
			if($pager->totalItems > $perPage) {
				$pager->maybeMore = true;
				array_pop($fItems->data);
			}
			if($from > 0) $pager->totalItems += $from;
			if($pager->totalItems > 0) {
				$pager->getPager();
				if ($pager->totalItems > $perPage) {
					$vars['TOPPAGER'] = $pager->links;
					$vars['BOTTOMPAGER'] = $pager->links;
				}
				if(!$fItems->fItemsRenderer) $fItems->fItemsRenderer = new FItemsRenderer();
				$itemPrev=null;
				$pageIdPrev=null;
				while ($itemVO = array_shift($fItems->data)) {
					if($user->pageVO->pageId != $itemVO->pageId) {
						$fItems->fItemsRenderer->showPage=false;
						if(!$itemPrev || $pageIdPrev != $itemVO->pageId) {
							//setup renderer if needed
							if($itemVO->itemIdTop > 0) {
								//show top item
								$itemTop = new ItemVO($itemVO->itemIdTop,true);
								if($itemTop->typeId=='galery') $fItems->fItemsRenderer->showPage=true;
								$itemTop->render($fItems->fItemsRenderer);
								$last = $fItems->fItemsRenderer->getLast();
								$last = str_replace('class="hentry','class="hentry opacity',$last);
								$fItems->fItemsRenderer->setLast($last);
								$fItems->fItemsRenderer->showPage=false;
							} elseif(($itemVO->typeId=='forum' || $itemVO->typeId=='galery')
							&& (!$itemPrev || $pageIdPrev != $itemVO->pageId)
							) {
								$fItems->fItemsRenderer->showPage=true;
							}
						}
					}
					$fItems->parse($itemVO);
					if($itemVO->itemIdTop > 0 && $user->pageVO->pageId != $itemVO->pageId) {
						$last = $fItems->fItemsRenderer->getLast();
						$last = str_replace('class="hentry','class="hentry reaction',$last);
						$fItems->fItemsRenderer->setLast($last);
					}
					$itemPrev = $itemVO;
					$pageIdPrev = $itemVO->pageId;
					FCommand::run(ITEM_READED,$itemVO);
				}
				$vars['ITEMS'] = $fItems->show();
			} else {
				if(!empty($writePerm)) $touchedBlocks[]='feedempty';
			}
		}
		if(!empty($data['__ajaxResponse'])) {
			FAjax::addResponse('itemFeed','$html',empty($vars['ITEMS']) ? '' : $vars['ITEMS']);
			FAjax::addResponse('topPager','$html',empty($vars['TOPPAGER']) ? '' : $vars['TOPPAGER']);
			FAjax::addResponse('bottomPager','$html',empty($vars['BOTTOMPAGER']) ? '' : $vars['BOTTOMPAGER']);
			FAjax::addResponse('pageHead','$html',FBuildPage::getHeading());
			FAjax::addResponse('document','title',FBuildPage::getTitle());
			FAjax::addResponse('call','fajaxaInit');
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
}