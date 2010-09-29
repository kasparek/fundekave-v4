<?php
class FBlog {
	
	static function textAreaId() {
		$user = FUser::getInstance();
		return 'Blog'.$user->pageVO->pageId;
	}
	
	static function getEditForm($itemId) {
		$user = FUser::getInstance();
			
		$textAreaIdShort = FBlog::textAreaId().'short';
		$textAreaIdLong = FBlog::textAreaId().'long';
			
		$tpl = FSystem::tpl('form.blog.tpl.html');
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=blog-submit'));

		$textShort = '';
		$textLong = '';
			
		$selectedCategory = 0;
		if($itemId > 0) {
			$itemVO = new ItemVO($itemId,false,array('type'=>'blog'));

			if($itemVO->load()) {
				$tpl->setVariable('TITLE',$itemVO->addon);
				$tpl->setVariable('DATESTART',$itemVO->dateStartLocal);

				$textShort = $itemVO->text;
				$textLong = $itemVO->textLong;

				$tpl->setVariable('USERNAME',$itemVO->name);
				$tpl->touchBlock('newdelete');
				$tpl->setVariable('ITEMID',$itemId);
				if($itemVO->public == 0) {
					$tpl->touchBlock('classnotpublic');
					$tpl->touchBlock('headernotpublic');
				} else {
					$tpl->touchBlock('statpublic');
				}
				///properties
				$tpl->touchBlock('comments'.$itemVO->getProperty('forumSet',$user->pageVO->prop('forumSet'),true));
				$selectedCategory = $itemVO->categoryId;
				
				$tpl->setVariable('POSITION',str_replace(';',"\n",$itemVO->prop('position')));
			}
		} else {

			$tpl->setVariable('DATESTART',Date("d.m.Y"));
			$tpl->setVariable('USERNAME',$user->userVO->name);

		}

		//categories
		if($opt = FCategory::getOptions($user->pageVO->pageId,$selectedCategory,true,'')) $tpl->setVariable('CATEGORYOPTIONS',$opt);

		$tpl->setVariable('CONTENT',$textShort);
		$tpl->setVariable('CONTENTLONG',$textLong);

		$tpl->setVariable('CONTENTID',$textAreaIdShort);
		$tpl->setVariable('CONTENTLONGID',$textAreaIdLong);

		return $tpl->get();
	}
	
	static function listAll($itemId = 0,$editMode = false) {
		$user = FUser::getInstance();
		$itemId = (int) $itemId;
		$perPage = BLOG_PERPAGE;
		$categoryId = 0;
		if(isset($_GET['c'])) $categoryId = (int) $_GET['c'];

		if(FRules::getCurrent(2)) {
			if(empty($user->pageParam) && !$itemId) {
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=blog-edit&d=item:0',$user->pageVO->pageId,'a'), FLang::$LABEL_ADD);
			}
			if($user->pageParam=='a') return;
		}
		
		$ret = false;
		
		if($editMode===false) {
			$ppUrlVar = FConf::get('pager','urlVar');
			$pageNum = 1;
			if(isset($_GET[$ppUrlVar])) $pageNum = (int) $_GET[$ppUrlVar];
			$cache = FCache::getInstance('f',0);
			$cacheKey = $user->pageVO->pageId.'-'.$pageNum.'-'.$itemId.'-'.(int) $user->userVO->userId.'-'.$categoryId;
			$cacheGrp = 'pagelist';
			$ret = $cache->getData($cacheKey,$cacheGrp);
		}
		
		if($ret===false) {	
			$tpl = FSystem::tpl('blog.list.tpl.html');
			if($user->idkontrol) $tpl->touchBlock('logged');
	
			//--edit mode
			if($editMode === true) {
				if(FRules::get($user->userVO->userId,$user->pageVO->pageId,2)) {
					$tpl->setVariable('EDITFORM',FBlog::getEditForm($itemId));
				}
			}
			
			if($itemId > 0) {
	
				$extraParams = array('type'=>'blog','showComments'=>true);
				if($user->pageParam=='u') {
					$extraParams['showComments'] = false;
				}
				$extraParams['showDetail'] = true;
				$itemVO = new ItemVO($itemId,true,$extraParams);
				if($editMode===false) {
					if($itemVO->userId != $user->userVO->userId) {
						$itemVO->hit();
					}
				}
	
				if(($itemNext = $itemVO->getNext(true,false))!==false) {
					FMenu::secondaryMenuAddItem(FSystem::getUri('i='.$itemNext),FLang::$BUTTON_PAGE_NEXT,0,'nextButt','','opposite');
				}
				if(($itemPrev = $itemVO->getPrev(true,false))!==false) {
					FMenu::secondaryMenuAddItem(FSystem::getUri('i='.$itemPrev),FLang::$BUTTON_PAGE_PREV,0,'prevButt','','opposite');
				}
	
				$user->pageVO->htmlTitle = $user->pageVO->name;
				$user->pageVO->htmlName = $itemVO->addon;
				$tpl->setVariable('ITEMS', $itemVO->render());
	
			} else {
	
				if(!empty($user->pageVO->content)) $tpl->setVariable('CONTENT',FSystem::postText($user->pageVO->content));
				
				$fItems = new FItems('blog',false);
				$fItems->addWhere("pageId='".$user->pageVO->pageId."'");
				$total = $user->pageVO->cnt;
				$fItems->addWhere('(itemIdTop is null or itemIdTop=0)');
				if($categoryId > 0) {
					$fItems->addWhere("categoryId='". $categoryId ."'");
					$total = $fItems->getCount();
				}
	
				$fItems->setOrder("dateStart desc, itemId desc");
	
				$currentPage = 0;
				if($total > $perPage) {
					$pager = new FPager($total,$perPage);
					$tpl->setVariable('BOTTOMPAGER',$pager->links);
					$currentPage = $pager->getCurrentPageID()-1;
				}
	
	
				$render = $fItems->render($currentPage * $perPage, $perPage);
	
				if(!empty($render)){
					FItems::aFav($user->pageVO->pageId,$user->userVO->userId);
					$tpl->setVariable('ITEMS', $render);
				}
	
			}
	
			$ret = $tpl->get();
			if(isset($cacheKey)) $cache->setData($ret,$cacheKey,$cacheGrp);
		}
		return $ret;	
	}
}