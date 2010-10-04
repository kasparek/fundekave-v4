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
			if(empty($user->pageParam) && !$user->itemVO) {
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:blog',$user->pageVO->pageId,'a'), FLang::$LABEL_ADD,0,'','fajaxa');
			}
				
		}

		//perpage based on unreaded items
		if( $user->idkontrol ) {
			$unreadedCnt = FItems::cacheUnreadedList($user->pageVO->pageId,$itemId);
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
			
		$perPage = $pageVO->perPage(); //TODO: get some global/local perpage
		$pageNumUrlVar = FConf::get('pager','urlVar');
		$categoryId=0;
		if(isset($data['c'])) $categoryId = (int) $data['c']; //for category filtering
		$arrPagerExtraVars = array();
		if(!empty($user->whoIs)) $arrPagerExtraVars = array('who'=>$who);

		/**
		 *FORM FOR EDIT ITEM
		 *- if in edit mode show edit form - blog,event from will redirect to detail view, only forum/foto form will show here
		 *if in edit mode - param u or forum display form
		 **/
		if($user->pageParam=='u' && $itemVO) {
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
			}
		}

		//continue only if empty $user->pageParam
		if(empty($user->pageParam)) {

			//filter-search
			$cache = FCache::getInstance('s',0);
			$searchStr = $cache->getData( $pageVO->pageId, 'filter');
			/**
			 *FORUM FORM
			 */
			if($pageVO->typeId=='forum') {
				$writePerm = $pageVO->prop('forumSet'); //TODO: base on write perm show or not
				if($writePerm==1 || (writePerm==2 && $user->idkontrol)) {
					$formItemVO = new ItemVO();
					$formItemVO->typeId = 'forum';
					$formItemVO->pageId = $pageVO->pageId;
					$data['perpage'] = $perPage;
					if($searchStr!==false) $data['text'] = $searchStr;
					$vars['MESSAGEFORM'] = FItemsForm::show($formItemVO,$data); //TODO: implement simple switch
				}
				if($writePerm == 2)
				$vars['MESSAGE'] = FLang::$MESSAGE_FORUM_REGISTEREDONLY; //TODO: implement message on templates
			}
				
			//HEADER
			if(empty($itemVO) && !empty($pageVO->content)) $vars['CONTENT'] = FSystem::postText($pageVO->content);
			//LIST ITEMS
			$fItems = new FItems('',FUser::logon());
			$fItems->setPage($pageVO->pageId);
			$fItems->hasReactions($pageVO->typeId!='forum' || empty($itemVO) ? false : true);
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

			//ORDER
			if($pageVO->pageId=='event') {
				if($archiv===false) { //TODO:archive base on pageparam
				//---future
				$fItems->addWhere("(dateStart >= date_format(NOW(),'%Y-%m-%d') or (dateEnd is not null and dateEnd >= date_format(NOW(),'%Y-%m-%d')))");
				$fItems->setOrder('dateStart');
				} else {
					//---archiv
					$fItems->addWhere("dateStart < date_format(NOW(),'%Y-%m-%d')");
					$fItems->setOrder('dateStart desc');
				}
			} else {
				$fItems->setOrder("if(dateStart,dateStart,dateCreated) desc, itemId desc");
			}

			if($itemId > 0) {
				$arrPagerExtraVars['k'] = $pageVO->pageId;
				$arrPagerExtraVars['i'] = $itemId;
				$itemVO->updateReaded( $user->userVO->userId );
			} else {
				$pageVO->updateReaded($user->userVO->userId);
			}
			$pager = new FPager(0,$perPage,array('extraVars'=>$arrPagerExtraVars,'noAutoparse'=>1,'bannvars'=>array('i'),'manualCurrentPage'=>$manualCurrentPage));
			$from = ($pager->getCurrentPageID()-1) * $perPage;
			$fItems->getList($from,$perPage+1);
			$pager->totalItems = count($fItems->data);

			if($pager->totalItems > $perPage) {
				$pager->maybeMore = true;
				array_pop($fItems->data);
			}
			if($from > 0) $pager->totalItems += $from;

			if($pager->totalItems>0) {
				$pager->getPager();
				if ($pager->totalItems > $perPage) {
					$vars['TOPPAGER'] = $pager->links;
					$vars['BOTTOMPAGER'] = $pager->links;
				}
				$vars['ITEMS'] = $fItems->render();
			} else {
				$touchedBlocks[]='feedempty';
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