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
				if(FRules::getCurrent(FConf::get('settings','perm_add_shortblog')))FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:forum',$user->pageVO->pageId), FLang::$LABEL_FORUM_NEW,array('class'=>'fajaxa'));
				if(FRules::getCurrent(FConf::get('settings','perm_add_event')))FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:event',$user->pageVO->pageId), FLang::$LABEL_EVENT_NEW,array('class'=>'fajaxa'));
			}
		}
		if($user->pageVO->pageId=='event' && $user->userVO->userId>0){
			FMenu::secondaryMenuAddItem(FSystem::getUri('m=item-edit&d=item:0;t:event',$user->pageVO->pageId), FLang::$LABEL_EVENT_NEW,array('class'=>'fajaxa'));
		}

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
		if(!empty($itemVO) && $pageVO->typeId!='forum') {
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
			if(FRules::getCurrent(2) || $user->userVO->userId==$itemVO->userId) {
				$vars['EDITFORM'] = FItemsForm::show($itemVO);
			}
		}

		/**
		 *ITEM DETAIL
		 **/
		$detail = false;
		if(!empty($itemVO)) {
			if($itemVO->pageVO->get('typeId')!='forum') {
				//show item detail
				$vars['DETAIL'] = page_ItemDetail::build($data);
				$detail = true;
			} else {
				$itemVO = null;
			}
		}
		
		if($detail==false) {
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
			$fItems = new FItems('',$user->userVO->userId);
			if(!empty($data['__get']['tag'])) {
				$tag = (int) $data['__get']['tag'];
				$fItems->addWhere("tag_weight >= '". $tag ."'");
			}
			if(!empty($data['__get']['type'])) {
				$type = $data['__get']['type'];
				$fItems->addWhere("typeId = '". $type ."'");
			}
			//$fItems->debug=1;
			if($pageVO->typeId!='top') {
				if($pageVO->pageId!='event') $fItems->setPage($pageVO->pageId);
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
				if($pageVO->pageId=='event' && empty($itemVO)) {
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
					if(!empty($itemVO) || $pageVO->typeId=='top') {
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

			$listArr = page_ItemsList::buildList($fItems,$pageVO,$pagerOptions);
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