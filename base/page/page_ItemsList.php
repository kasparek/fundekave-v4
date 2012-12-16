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
		if($user->pageVO->typeId=='galery' && empty($user->itemVO) && !$data['__ajaxResponse']) {
			$grpid = 'page/'.$user->pageVO->pageId.'/list';
			$cache = FCache::getInstance('f');
			$data = $cache->getData('complete',$grpid);
			if($data) {
				FBuildPage::addTab(array("MAINDATA"=>$data));
				return;
			}
		}
		
		$tpl = page_ItemsList::buildPrep($data);
		
		if($data['__ajaxResponse']) {
			FAjax::addResponse('commentForm','action',FSystem::getUri('','',false,array('short'=>1)));
			$tpl->parse('itemlist');
			FAjax::addResponse('itemFeed','$html',$tpl->get('itemlist'));
			FAjax::addResponse('pageHead','$html',FBuildPage::getHeading());
			FAjax::addResponse('document','title',FBuildPage::getTitle());
			FAjax::addResponse('call','fajaxInit');
			FAjax::addResponse('call','GooMapi.init');
		} else {
			$data = $tpl->get();
			FBuildPage::addTab(array("MAINDATA"=>$data));
			
			if(isset($grpid) && $user->pageVO->typeId=='galery' && empty($user->itemVO)) {
				$cache->setData($data,'complete',$grpid);
			}
		}
	}	
	 
	static function buildPrep($data=array()) {
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
				if(empty($user->pageParam) && empty($user->itemVO) && $pageVO->typeId=='blog') {
					FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:blog',$user->pageVO->pageId), FLang::$LABEL_ADD,array('class'=>'fajaxa'));
					if(FRules::getCurrent(FConf::get('settings','perm_add_shortblog')))FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:forum',$user->pageVO->pageId), FLang::$LABEL_FORUM_NEW,array('class'=>'fajaxa'));
					if(FRules::getCurrent(FConf::get('settings','perm_add_event')))FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:event',$user->pageVO->pageId), FLang::$LABEL_EVENT_NEW,array('class'=>'fajaxa'));
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
    if($pageVO->typeId=='galery' && !$isDetail) {
      $touchedBlocks[]='galleryfeed';
      $user->pageVO->showSidebar = false;
    }
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

		if($user->idkontrol && !$isDetail && $pageVO->typeId!='galery') {
			//TAG FILTERING
			$cache = FCache::getInstance('f');
			$tagGroups = $cache->getData('tagGrouped'.(!empty($typeRequest)?'-'.$typeRequest:''),'page/'.$user->pageVO->pageId.'/tag');
			if($tagGroups===false) {
				$tagGroups = FDBTool::getCol("SELECT tag_weight FROM `sys_pages_items` where tag_weight>0 ".(!empty($typeRequest)?" and typeId='".$typeRequest."' ":'').($user->pageVO->typeId!='top'?" and pageId='".$user->pageVO->pageId."' ":'')." group by tag_weight");
				$cache->setData($tagGroups);
			}
			$len = count($tagGroups);
			if($len>0) {
				$steps = ceil($len/5);
				if($steps<1) $steps = 1;
				for($i=0;$i<($len/$steps);$i++) if($i*$steps<$len) $tagGroupsFiltered[] = $tagGroups[$i*$steps];	
				$currentTag = 0;
				if(!empty($data['__get']['tag'])) $currentTag = (int) $data['__get']['tag'];
				if($currentTag>0) $tagsHtmlList[] = '<a href="'.FSystem::getUri("").'" title="'.FLang::$LABEL_TAG_ALL.'">0</a> ';
				else $tagsHtmlList[] = '<span class="current" title="'.FLang::$LABEL_TAG_ALL.'">0</span> ';
				foreach($tagGroupsFiltered as $tag) {
					if($tag==$currentTag) $tagsHtmlList[] = '<span class="current">'.$tag.'</span>';
					else $tagsHtmlList[] = '<a href="'.FSystem::getUri((!empty($typeRequest)?'type='.$typeRequest.'&':'')."tag=".$tag).'" title="'.FLang::$LABEL_TAG_FILTER.$tag.'">'.$tag.'</a> ';
				}
				if($user->pageVO->typeId=='top') {
					if($typeRequest=='galery') $tagsHtmlList[] = '<span class="current">Jen foto</span>';
					else $tagsHtmlList[] = '<a href="'.FSystem::getUri("type=galery&tag=".$currentTag).'">Jen foto</a>';
				}
				$tagsHtml = implode("\n",$tagsHtmlList);
				$vars['TAGFILTERLINKS'] = $tagsHtml;
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
			if(FItemsForm::canComment()) {
				if(!$data['__ajaxResponse']) {
					if($isDetail) $data['simple'] = true;
					$formItemVO = new ItemVO();
					$formItemVO->typeId = 'forum';
					$formItemVO->pageId = $pageVO->pageId;
					$data['perpage'] = $pageVO->perPage();
					if($searchStr!==false) $data['text'] = $searchStr;
					$vars['MESSAGEFORM'] = FItemsForm::show($formItemVO,$data);
				} else {
					//TODO: set Lang
					$vars['MESSAGEFORM'] = '<a href="'.FSystem::getUri('m=item-commentsForm&d=ti:'.$itemVO->itemId).'" class="fajaxa">Vloz komentar</a>';
				}
			} else if($isDetail && $pageVO->typeId=='forum') {
				$vars['MESSAGE'] = FLang::$MESSAGE_FORUM_REGISTEREDONLY;
			}
		}

		//HEADER
		if(!$isDetail && !empty($pageVO->content)) {
			$vars['CONTENT'] = FSystem::postText($pageVO->content);
		}

		//LIST ITEMS
		$fItems = new FItems('',$user->userVO->userId);
		if(!empty($data['__get']['tag'])) {
			$tag = (int) $data['__get']['tag'];
			$fItems->addWhere("tag_weight >= '". $tag ."'");
		}
		
		$type = 'item';
		if(!empty($data['__get']['type'])) {
			$type = $data['__get']['type'];
			$fItems->addWhere("typeId = '". $type ."'");
		} else {
			$type = $pageVO->typeId;
		}

		if($pageVO->typeId!='top') {
			if($pageVO->pageId!='event') $fItems->setPage($pageVO->pageId);
			$fItems->hasReactions($pageVO->typeId!='forum' && !$isDetail ? false : true);
		}
		if($categoryId > 0) {
			$fItems->addWhere("categoryId='". $categoryId ."'");
		}
		if(!empty($searchStr)) {
			$fItems->addWhereSearch(array('name','text','enclosure','dateCreated','location','addon'),$searchStr,'or');
		}
		if($isDetail) {
			$itemId = $itemVO->itemId;
			$fItems->addWhere("itemIdTop='".$itemVO->itemId."'"); //displaying reactions
			$type = 'forum';
		}
		if(SITE_STRICT && $pageVO->typeId=='top') {
			$fItems->addWhere("pageIdTop = '".SITE_STRICT."'");
		}
		

		if(!empty($date)) {
			//used for sorting
			$fItems->addWhere("(sys_pages_items.typeId='forum' and '".$date."'=date_format(sys_pages_items.dateCreated,'%Y-%m-%d')) "
			."or (sys_pages_items.typeId in ('blog','galery') and '".$date."'=date_format(sys_pages_items.dateStart,'%Y-%m-%d')) "
			."or (sys_pages_items.typeId='event' and '".$date."'>=date_format(sys_pages_items.dateStart,'%Y-%m-%d') and '".$date."'<=date_format(sys_pages_items.dateEnd,'%Y-%m-%d'))"
			);
			$fItems->setOrder('dateStart desc');
		} else {
			//ORDER
			if($pageVO->pageId=='event' && !$isDetail) {
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

		if($pageVO->typeId=='top') {
			$fItems->userIdForPageAccess=true;
			$fItems->setTypeLimit('galery',3);
			$fItems->cacheResults = 'f';
		}
		
		$listArr = page_ItemsList::buildList($fItems,$pageVO,$pagerOptions);
		
		$vars = array_merge($vars,$listArr['vars']);
		if(!empty($listArr['blocks'])) $touchedBlocks = array_merge($touchedBlocks,$listArr['blocks']);
		
		//based of type of items in feed
		$vars['FEEDID'] = $type.'Feed';

		
		}
		if($isDetail) {
			$touchedBlocks[]='comm';
		}
		
		$tpl = FSystem::tpl($template);
		if(!empty($touchedBlocks)) $tpl->touchBlock( $touchedBlocks );
		$tpl->setVariable($vars);
		
		return $tpl;
	}

	static function buildList($fItems,$pageVO,$pagerOptions=array()) {
		$touchedBlocks = array();
		$vars = array();
		$pagerOptions['noAutoparse']=1;
		$perPage = $pageVO->perPage();
		$pager = new FPager(0,$perPage,$pagerOptions);
		
		$from = ($pager->getCurrentPageID()-1) * $perPage;

		$uid = $fItems->getUID($from, $perPage+1);
		$grpid = 'page/'.($pageVO->typeId!='top'?$pageVO->pageId:'top').'/list';
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
					$vars['BOTTOMPAGER'] = $pager->links;
				}
				if(!$fItems->fItemsRenderer) $fItems->fItemsRenderer = new FItemsRenderer();
				$itemPrev=null;
				$typeIdPrev=null;
				$itemIdTopPrev=null;
				$pageIdPrev=null;
				
				if($pageVO->typeId=='top') {
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
				
				while ($itemVO = array_shift($fItems->data)) {
					if($pageVO->pageId != $itemVO->pageId) {
						$fItems->fItemsRenderer->showPage=false;
						if(!$itemPrev || $pageIdPrev != $itemVO->pageId || $itemIdTopPrev!=$itemVO->itemIdTop) {
							//setup renderer if needed
							$itemIdTop=0;
							$itemIdPrev=0;
							if($itemVO)$itemIdTop=$itemVO->itemIdTop;
							if($itemPrev)$itemIdPrev=$itemPrev->itemId;
							if($itemIdTop > 0 && $itemIdPrev!=$itemIdTop) {
								//show top item
								$itemTop = new ItemVO($itemVO->itemIdTop);
								//figure out how to display showPage for galery items
								if($itemTop->get('typeId')=='galery') $fItems->fItemsRenderer->showPage=true;
								$itemTop->render($fItems->fItemsRenderer);
								$last = $fItems->fItemsRenderer->getLast();
								$last = str_replace($itemVO->itemIdTop.'" class="hentry',$itemVO->itemIdTop.'" class="hentry opacity',$last);
								$last = str_replace($itemVO->itemIdTop.'" class="vevent',$itemVO->itemIdTop.'" class="vevent opacity',$last);
								$fItems->fItemsRenderer->setLast($last);
								$fItems->fItemsRenderer->showPage=false;
							} elseif(($itemVO->typeId=='forum' || $itemVO->typeId=='galery') && empty($itemVO->itemIdTop)) {
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