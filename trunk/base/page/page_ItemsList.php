<?php
include_once('iPage.php');

function array_insert($array,$pos,$val){
	$array2 = array_splice($array,$pos);
	$array[] = $val;
	$array = array_merge($array,$array2);
	return $array;
}
function array_indexOf($array,$prop,$val) {
	for($i=0;$i<count($array);$i++){
		if($val==$array[$i]->{$prop}) return $i;
	}
	return -1;
}

class page_ItemsList implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
		$user = FUser::getInstance();
		
		
		$tpl = page_ItemsList::buildPrep($data);
		
		if(!empty($data['__ajaxResponse'])) {
			FAjax::addResponse('commentForm','action',FSystem::getUri('','',false,array('short'=>1)));
			$tpl->parse('itemlist');
			FAjax::addResponse('forumFeed','$html',$tpl->get('itemlist'));
			FAjax::addResponse('pageHead','$html',FBuildPage::getHeading());
			FAjax::addResponse('document','title',FBuildPage::getTitle());
			FAjax::addResponse('call','fajaxInit');
			FAjax::addResponse('call','gooMapiInit');
		} else {
			$data = $tpl->get();
			FBuildPage::addTab(array("MAINDATA"=>$data));
			if(isset($grpid)) $cache->setData($data,$dataid,$grpid);
		}
	}	
	 
	static function buildPrep($data=array()) {
		if(!is_array($data)) $data = array();
		//validate input parameters
		$manualCurrentPage = 0;
		if(!isset($data['onlyComments'])) $data['onlyComments']=false;
        	
		if(isset($data['__get']['date'])) {
			$date = FSystem::checkDate($data['__get']['date']);
		}
		
		//var setup
		$user = FUser::getInstance();
		if($user->itemVO) {
			if($user->itemVO->itemId > 0) $itemVO = $user->itemVO;
		}
		if(!empty($data['itemId'])) {
			$itemVO = new ItemVO($data['itemId']);
			if(!$itemVO->load()) $itemVO = null;
		}
		$isDetail = false;
		if(!empty($itemVO)) $isDetail = true;

		$pageVO = $user->pageVO;
		if($isDetail) {
			$pageVO = $itemVO->pageVO;
		}
		if(!$data['onlyComments']) {
			if(FRules::getCurrent(2)) {
				if($pageVO->typeId=='galery') {
					if(!$isDetail) {
					FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-showupload',$user->pageVO->pageId,'u'), FLang::$LABEL_UPLOAD,array('class'=>'fajaxa'));
					} else {
					FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=i:'.$user->itemVO->itemId,$user->pageVO->pageId,'u'), FLang::$LABEL_EDIT_PHOTO,array('class'=>'fajaxa'));
					}
				}
				if(empty($user->pageParam) && empty($user->itemVO) && $pageVO->typeId=='blog') {
					FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=i:0;t:blog',$user->pageVO->pageId), FLang::$LABEL_ADD,array('class'=>'fajaxa'));
					if(FRules::getCurrent(FConf::get('settings','perm_add_event')))FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=i:0;t:event',$user->pageVO->pageId), FLang::$LABEL_EVENT_NEW,array('class'=>'fajaxa'));
				}
			}
			if($user->pageVO->pageId=='event' && $user->userVO->userId>0){
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:event',$user->pageVO->pageId), FLang::$LABEL_EVENT_NEW,array('class'=>'fajaxa'));
			}

			//---DEEPLINKING for forum pages
			if($isDetail) {
				if($itemVO->typeId=='forum' && $itemVO->pageVO->get('typeId')=='forum') {
					$manualCurrentPage = $itemVO->onPageNum();
				}
			}
		} //only comments

		//perpage based on unreaded items
		$diff=0;
		if( $user->idkontrol ) {
			if($pageVO->typeId!='top') {
				$unreadedCnt = FItems::cacheUnreadedList();
				if($unreadedCnt > 0 && $unreadedCnt > $pageVO->perPage()) {
					$pageVO->perPage($unreadedCnt + 3);
					$diff = $unreadedCnt;
				}
			} else {
				//for top pages based on super total item num
				$diff = $user->userVO->prop('itemsNum')-$user->userVO->itemsLastNum;
				if($diff>0) {
					$user->userVO->itemsLastNum = $user->userVO->prop('itemsNum');
					if($diff>$pageVO->perPage()) $pageVO->perPage($diff + 3);
				}
			}
		}

		$output = '';
		$template = 'page.items.list.tpl.html';
		$touchedBlocks = array();
    
		$vars = array();
		if($diff>0) {
			$max = FConf::get('perpage','max');
			$vars['UNREADNUM']= $diff>$max ? $max.'+' : $diff;
		}
		$itemId = 0;
		$typeRequest='';
		if(!empty($data['__get']['type'])) {
			$typeRequest = $data['__get']['type'];
			if(!isset(FLang::$TYPEID[$typeRequest])) $typeRequest='';
		}

		$categoryId=0;
		if($user->categoryVO) {
			$categoryId = $user->categoryVO->categoryId; //for category filtering
		}
		$arrPagerExtraVars = array();
		if($categoryId>0) $arrPagerExtraVars['c'] = $categoryId;
		if(!isset($_REQUEST['k'])) $arrPagerExtraVars['k'] = $user->pageVO->pageId;
		if(!empty($user->whoIs)) $arrPagerExtraVars['who'] = $who;
		$pagerOptions = array('manualCurrentPage'=>$manualCurrentPage);
		if($isDetail && $pageVO->typeId!='forum') {
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
		if($user->pageParam=='u' && $isDetail) {
			if(FRules::getCurrent(2) || $user->userVO->userId==$itemVO->userId) {
				$vars['EDITFORM'] = FItemsForm::show($itemVO);
			}
		}
		
		//show upload widget
		if($user->pageVO->typeId=='galery' && $user->pageParam=='u' && !$isDetail) {
			if(FRules::getCurrent(2)) {
				$utpl = FSystem::tpl('form.fuup.tpl.html');
				$utpl->touchBlock('__global__');
				$vars['EDITFORM'] = $utpl->get();
			}
		}
		

		/**
		 *ITEM DETAIL
		 **/
		if($isDetail) {
			if($itemVO->pageVO->get('typeId')!='forum') {
				//show item detail
				if(!$data['onlyComments']) {
					$vars['DETAIL'] = page_ItemDetail::build($data);
				}
			} else {
				$itemVO = null;
				$isDetail = false;
			}
		}

		//TOPLIST ITEMS
		if($pageVO->typeId=='galery') {
			$vars['TOPFEEDID'] = 'galeryFeed';
			if($isDetail) $touchedBlocks[]='galery-detail-thumbs';
			$fItems = new FItems('galery',$user->userVO->userId);
			$fItems->addWhere("pageId = '". $pageVO->pageId ."'");
			$fItems->setOrder($pageVO->itemsOrder());
			$listArr = page_ItemsList::buildList($fItems,$pageVO,$pagerOptions);
			$listArr['vars']['TOPITEMS'] = $listArr['vars']['ITEMS'];
			unset($listArr['vars']['ITEMS']);
			$vars = array_merge($vars,$listArr['vars']);
			if(!empty($listArr['blocks'])) $touchedBlocks = array_merge($touchedBlocks,$listArr['blocks']);
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
			$forumFormTypeId = $pageVO->typeId=='galery'?'forum':$pageVO->typeId=='galery';
			if(FItemsForm::canComment()) {
				if(empty($data['__ajaxResponse'])) {
					if($isDetail) $data['simple'] = true;
					$formItemVO = new ItemVO();
					$formItemVO->typeId = 'forum';
					$formItemVO->pageId = $pageVO->pageId;
					$data['perpage'] = $pageVO->perPage();
					if($searchStr!==false) $data['text'] = $searchStr;
					$vars['MESSAGEFORM'] = FItemsForm::show($formItemVO,$data);
				} else {
					//TODO: set Lang
					$vars['MESSAGEFORM'] = '<a href="'.FSystem::getUri('m=item-commentsForm&d=ti:'.$itemVO->itemId).'" class="fajaxa">Vlož komentář</a>';
				}
			} else if($isDetail || $forumFormTypeId=='forum') {
				$vars['MESSAGE'] = FLang::$MESSAGE_FORUM_REGISTEREDONLY;
			}
		}

		//HEADER
		if(!$isDetail && !empty($pageVO->content)) {
			$vars['CONTENT'] = FText::postProcess($pageVO->content);
		}
		
		//LIST ITEMS
		$fItems = new FItems($pageVO->typeId=='galery'?'forum':'',$user->userVO->userId);
		if(!empty($data['__get']['tag'])) {
			$tag = (int) $data['__get']['tag'];
			$fItems->addWhere("tag_weight >= '". $tag ."'");
		}
		
		$type = 'item';
		if(!empty($data['__get']['type'])) {
			$type = $data['__get']['type'];
			$fItems->addWhere("typeId = '". $type ."'");
		} else {
			$type = $pageVO->typeId=='blog'?'top':$pageVO->typeId;
		}

		//TODO: update `sys_pages_items` as i join sys_pages as p on p.pageId=i.pageId set i.pageIdTop=p.pageIdTop WHERE length(p.pageIdTop)>0 and i.pageIdTop is null and i.typeId='galery'
		//if(SITE_STRICT && $pageVO->typeId=='top') {
		//	$fItems->addWhere("pageIdTop = '".SITE_STRICT."'");
		//}
		if($pageVO->pageIdTop && $pageVO->typeId=='top') {
			$fItems->addWhere("pageIdTop = '".$pageVO->pageIdTop."'");
		}
		
		if($pageVO->typeId=='blog') {
		
			if($pageVO->pageIdTop) {
				$fItems->addWhere("(sys_pages_items.pageId='".$pageVO->pageId."' or (sys_pages_items.typeId='galery' and sys_pages_items.pageIdTop='".$pageVO->pageIdTop."'))");
			} else {
				$fItems->setPage($pageVO->pageId);
			}
		}
		
		if($pageVO->typeId!='top') {
			if($pageVO->pageId!='event' && $pageVO->typeId!='blog') {
				$fItems->setPage($pageVO->pageId);
			}
			$fItems->hasReactions($pageVO->typeId!='forum' && !$isDetail ? false : true);
		}
		
		if($categoryId > 0) {
			$fItems->addWhere("categoryId='". $categoryId ."'");
		}
		if(!empty($searchStr)) {
			$fItems->addWhereSearch(array('name','text','enclosure','dateCreated','location','addon'),$searchStr,'or');
		}
		if($isDetail || $pageVO->typeId=='galery') {
			$type = 'forum';
		}
		if($isDetail) {
			$itemId = $itemVO->itemId;
			$fItems->addWhere("itemIdTop='".$itemVO->itemId."'"); //displaying reactions
		}
				
		$include = $pageVO->prop('include');
		if(!empty($include)) {
			$includeList = explode(',',$include);
			//$fItems->addWhere("(sys_pages_items.typeId = '".implode("' or sys_pages_items.typeId = '",$includeList)."')");
			$fItems->addWhere("sys_pages_items.typeId in ('".implode("','",$includeList)."')");
		}
		
		if(!empty($date)) {
			//used for sorting
			$fItems->addWhere("(0=1)"
			."or (sys_pages_items.typeId='forum' and '".$date."'=date_format(sys_pages_items.dateCreated,'%Y-%m-%d')) "
			."or (sys_pages_items.typeId in ('blog','galery') and '".$date."'=date_format(sys_pages_items.dateStart,'%Y-%m-%d')) "
			."or (sys_pages_items.textLong='year' and date_format(sys_pages_items.dateStart, '%m-%d')='".substr($date,5)."') "
			."or (sys_pages_items.typeId='event' and sys_pages_items.textLong!='year' and '".$date."'>=date_format(sys_pages_items.dateStart,'%Y-%m-%d') and '".$date."'<=date_format(sys_pages_items.dateEnd,'%Y-%m-%d'))"
			.""
			);
			$fItems->setOrder('dateStart desc');
		} else {
			//ORDER
			if($pageVO->pageId=='event' && !$isDetail) {
				$fItems->addWhere("typeId='event'");
				//add where for repetitive
				if($user->pageParam=='o') {
					//---archiv
					FMenu::secondaryMenuAddItem(FSystem::getUri('','',''),FLang::$BUTTON_PAGE_BACK);
					$fItems->addWhere("dateStart < date_format(NOW(),'%Y-%m-%d')");
					$fItems->setOrder('dateStart desc');
				} else {
					//---future
					FMenu::secondaryMenuAddItem(FSystem::getUri('','','o'),FLang::$LABEL_EVENTS_ARCHIV);
					$fItems->addWhere("((textLong='year' and date_format(dateStart,'%m') >= date_format(NOW(),'%m')) or (dateStart >= date_format(NOW(),'%Y-%m-%d') or (dateEnd is not null and dateEnd >= date_format(NOW(),'%Y-%m-%d'))))");
					$fItems->setOrder('dateStart');
				}
			} else {
				if($isDetail || $pageVO->typeId=='top') {
					//reactions
					$fItems->setOrder('dateCreated desc');
				} else {
					$fItems->setOrder($pageVO->itemsOrder());
				}
			}
		}

		if($isDetail) {
			$itemVO->updateReaded($user->userVO->userId);
		} else {
			$pageVO->updateReaded($user->userVO->userId);
		}

		if($pageVO->typeId=='top' || $pageVO->typeId=='blog') {
			$fItems->userIdForPageAccess=$user->userVO->userId;
			$fItems->cacheResults = 'f';
			$fItems->setTypeLimit('galery',3);
		}
		
		$listArr = page_ItemsList::buildList($fItems,$pageVO,$pagerOptions,$pageVO->typeId=='galery'?'forum':false);
		$vars = array_merge($vars,$listArr['vars']);
		if(!empty($listArr['blocks'])) $touchedBlocks = array_merge($touchedBlocks,$listArr['blocks']);
		
		//based of type of items in feed
		$vars['FEEDID'] = $type.'Feed';

		
		}
		
		if(!$user->pageParam && ($isDetail || $pageVO->typeId=='galery')) {
			$touchedBlocks[]='comm';
		}
		
		$tpl = FSystem::tpl($template);
		if(!empty($touchedBlocks)) $tpl->touchBlock( $touchedBlocks );
		$tpl->setVariable($vars);
		
		return $tpl;
	}

	static function buildList($fItems,$pageVO,$pagerOptions=array(),$overrideTypeId=false) {
		$touchedBlocks = array();
		$vars = array();
		$pagerOptions['noAutoparse']=1;
		
		$typeId = $pageVO->typeId;
		if($overrideTypeId!==false) $typeId=$overrideTypeId;
		
		$perPage = $pageVO->perPage(0,$typeId);
		$from = 0;
		$pager = null;
		if(empty($pagerOptions['nopager'])) {
			$pager = new FPager(0,$perPage,$pagerOptions);
			$from = ($pager->getCurrentPageID()-1) * $perPage;
		}

		$uid = $fItems->getUID($from, $perPage+1);
		$grpid = 'page/'.($typeId!='top'?$pageVO->pageId:'top').'/list';
		$cache = FCache::getInstance('f');
		$data = $cache->getData($uid,$grpid);
		
		if($data===false) {
			$fItems->getList($from, $perPage+1);
			
			$numItems = count($fItems->data);
			if($pager) {
				$pager->totalItems = $numItems;
				if($pager->totalItems > $perPage) {
					$pager->maybeMore = true;
					array_pop($fItems->data);
				}
				$vars['TOTALITEMS'] = $pager->maybeMore ? $perPage.'+' : count($fItems->data);
				if($from > 0) $pager->totalItems += $from;
				$numItems = $pager->totalItems;
			}
			
			if($numItems > 0) {
				if($pager) {
					$pager->getPager();
					if ($pager->totalItems > $perPage) {
						$vars['BOTTOMPAGER'] = $pager->links;
					}
				}
				if(!$fItems->fItemsRenderer) $fItems->fItemsRenderer = new FItemsRenderer();
				$itemPrev=null;
				$itemIdTopPrev=null;
				$pageIdPrev=null;
				
				if($typeId=='top' || $typeId=='blog') {
					//sort by page
					$newArr=array();
					$fItems->data = array_reverse($fItems->data);
					while($itemVO = array_pop($fItems->data)){
						$index = array_indexOf($newArr,'pageId',$itemVO->pageId);
						if($index>-1 && ($itemVO->typeId!='blog' && empty($itemVO->itemIdTop))) $newArr=array_insert($newArr,$index,$itemVO);
						else array_unshift($newArr,$itemVO);
					}
					$fItems->data=array_reverse($newArr);
					/**/
					//sort reaction after top item if present
					$newArr=array();
					while($itemVO = array_shift($fItems->data)){
						if(!empty($itemVO->itemIdTop)) {
							foreach($fItems->data as $k=>$topItem) {
								if($topItem->itemId==$itemVO->itemIdTop) {
									$newArr[]=$topItem;
									unset($fItems->data[$k]);
								}
							}
						}
						$newArr[]=$itemVO;
					}
					$fItems->data=$newArr;
					/**/
					//sort by itemtop
					$newArr=array();
					$sortedItems=array();
					while($itemVO = array_shift($fItems->data)){
						if(!empty($itemVO->itemIdTop)) {
							$index = array_indexOf($newArr,'itemId',$itemVO->itemIdTop);
							if($index==-1) $index = array_indexOf($newArr,'itemIdTop',$itemVO->itemIdTop);
							if($index>-1) {
								if(isset($sortedItems[$itemVO->itemIdTop])) $sortedItems[$itemVO->itemIdTop]++; else $sortedItems[$itemVO->itemIdTop]=1;
								$newArr=array_insert($newArr,$index+$sortedItems[$itemVO->itemIdTop],$itemVO);
							}
							else $newArr[]=$itemVO;
						} else $newArr[]=$itemVO;
					}
					$fItems->data=$newArr;
					/**/
				}
								
				$timeBefore = time();
				$group = array();
				$output = '';
				
				while ($itemVO = array_shift($fItems->data)) {
				
					$itemIdTop = $itemVO->itemIdTop;
					$itemIdPrev = $itemPrev ? $itemPrev->itemId : 0;
					
					if($pageVO->pageId != $itemVO->pageId) {
						if(!$itemPrev || $pageIdPrev != $itemVO->pageId || $itemIdTopPrev != $itemIdTop) {
							if($itemIdTop > 0 && $itemIdPrev!=$itemIdTop) {
								//show top item
								$itemIdPrev = $itemIdTop;
								$itemTop = new ItemVO($itemIdTop,true);
								$itemTop->render($fItems->fItemsRenderer, false);
								$group[] = $fItems->show();
							} 
						}
					}
					
					if($itemIdTop > 0 && $itemIdPrev == $itemIdTop && $pageVO->pageId != $itemVO->pageId) {
						$group[count($group)-1] = str_replace('fitem','fitem opacity',$group[count($group)-1]);
					}
					
					//render new item
					if($itemVO->typeId=='top') {
					} else {
						$fItems->parse($itemVO);
					}
					
					if($itemIdTop > 0 && $pageVO->pageId != $itemVO->pageId) {
						$last = $fItems->fItemsRenderer->getLast();
						$last = str_replace('fitem','fitem reaction',$last);
						$fItems->fItemsRenderer->setLast($last);
					}
					
					
					$itemPrev = $itemVO;
					$itemIdTopPrev = $itemIdTop;
					$pageIdPrev = $itemVO->pageId;
					if($itemVO->isUnreaded) {
						$readed[] = $itemVO->itemId;
					}

					if($itemVO->typeId=='event') if(!in_array('fcalendar',$touchedBlocks)) $touchedBlocks[]='fcalendar';
					
					$group[] = $fItems->show();
					
					$nextPageId = false;
					$nextTypeId = false;
					$nextItemIdTop = false;
					$nextVO = $fItems->data ? $fItems->data[0] : null;
					if($nextVO) {
						$nextPageId = $nextVO->pageId;
						$nextTypeId = $nextVO->typeId;
						$nextItemIdTop = $nextVO->itemIdTop;
					}
					if($nextPageId != $itemVO->pageId || ($nextTypeId == 'blog' || $nextTypeId != $itemVO->typeId && $nextItemIdTop != $itemVO->itemId)) {
						if(!empty($group)) {
							if($pageVO->pageId != $itemVO->pageId) {
								$output .= $fItems->fItemsRenderer->addPageName( implode("\n",$group), $itemVO);
							} else {
								if($typeId=='galery') {
									$output .= ''.implode("\n",$group).''."\n";
								} else {
									$output .= '<div class="panel panel-default"><div class="panel-body">'.implode("\n",$group).'</div></div>'."\n";
								}
							}
							$group = array();
						}
					}
				}
				
				$vars['ITEMS'] = $output;
			} else {
				$touchedBlocks[]='feedempty';
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