<?php
include_once('iPage.php');
class page_Search implements iPage {

	static function process($data) {
		$invalidate = false;
		$user = FUser::getInstance();

		$cache = FCache::getInstance('s');
		$pageSearchCache = &$cache->getPointer('search');
		if(!isset($pageSearchCache['filtrStr'])) $pageSearchCache['filtrStr']='';


		if(isset($_REQUEST['f'])) $data['filtr'] = $_REQUEST['f'];
		if(!empty($data['filtr'])) {
				
			$str = FText::preProcess($data['filtr'],array('plainText'=>1));
			$setPages=true;$setItems=true;$setUsers=true;
				
			if(isset($data['t'])) {
				if($data['t']=='pages') { $setItems=false; $setUsers=false; }
				if($data['t']=='items') { $setPages=false; $setUsers=false; }
				if($data['t']=='users') { $setItems=false; $setPages=false; }
			}
			
			if($setPages===true) {
				if(!isset($pageSearchCache['filtrPages'])) $pageSearchCache['filtrPages'] = '';
				if($str !== $pageSearchCache['filtrPages']) {
					$pageSearchCache['filtrPages'] = $str;
					$invalidate = true;
				}
			}
				
			if($setItems===true) {
				if(!isset($pageSearchCache['filtrItems'])) $pageSearchCache['filtrItems'] = '';
				if($str !== $pageSearchCache['filtrItems']) {
					$pageSearchCache['filtrItems'] = $str;
					$invalidate = true;
				}
			}
				
			if($setUsers===true) {
				if(!isset($pageSearchCache['filtrUsers'])) $pageSearchCache['filtrUsers'] = '';
				if($str !== $pageSearchCache['filtrUsers']) {
					$pageSearchCache['filtrUsers'] = $str;
					$invalidate = true;
				}
			}
				
		}

		if($invalidate === true) {
			page_Search::invalidate();
		}
	}

	static function invalidate() {
		$user = FUser::getInstance();
		$mainCache = FCache::getInstance('f',0);
		$mainCache->invalidateGroup('search-'.$user->userVO->userId);
	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;

		$VARS = array();
		$TOUCHEDBLOCKS = array();

		$cache = FCache::getInstance('s');
		$pageSearchCache = &$cache->getPointer('search');

		$perPage = $user->pageVO->perPage();

		/**
		 * SEARCH PAGES
		 */
		if(!empty($pageSearchCache['filtrPages'])) {
			$p = 1;
			if(isset($_GET['pp'])) $p = (int) $_GET['pp'];
			$mainCache = FCache::getInstance('f',0);
			$cacheKey = $pageSearchCache['filtrPages'].'-p-'.(($p>1)?('-p-'.$p):(''));
			$cacheGrp = 'search-'.$user->userVO->userId;
			$PAGES = $mainCache->getData($cacheKey,$cacheGrp);

			if(false === $PAGES) {
				$vars = array();
				$touchedBlocks = array();
				//---QUERY RESULTS
				$fPages = new FPages(array('galery','forum','blog'), $userId);
				$fPages->addWhere('sys_pages.locked < 2');
				if(SITE_STRICT) $fPages->addWhere("sys_pages.pageIdTop = '".SITE_STRICT."'");
				if(!empty($pageSearchCache['filtrPages'])){
					$fPages->addWhereSearch(array('sys_pages.name','sys_pages.description','sys_pages.dateContent'),$pageSearchCache['filtrPages'],'OR');
				}
				$fPages->setOrder("sys_pages.name");
				$pager = new FPager(0,$perPage ,array('noAutoparse'=>1,'urlVar'=>'pp','hash'=>'pages'));
				$pager->extraVars['k'] = $user->pageVO->pageId;
				$from = ($pager->getCurrentPageID()-1) * $perPage;
				$fPages->setLimit( $from, $perPage+1 );
				$arr = $fPages->getContent();
				//$totalItems = count($arr);
				$totalItems = $fPages->getCount();
				$maybeMore = false;
				if($totalItems > $perPage) {
					$maybeMore = true;
					unset($arr[(count($arr)-1)]);
				}
				if($from > 0) $totalItems += $from;
				//---show results if any
				if($totalItems > 0) {
					//--pagination
					$pager->totalItems = $totalItems;
					$pager->maybeMore = $maybeMore;
					$pager->getPager();
					//---results
					$vars['PAGES'] = FPages::printPagelinkList($arr);
					
					//---pager
					if($totalItems > $perPage) {
						$vars['PAGESPAGER'] = $pager->links;
					}
				} else {
					$touchedBlocks['nopages'] = true;
				}
				$vars['FILTRPAGES'] = $pageSearchCache['filtrPages'];
				$vars['TOTALPAGES'] = $totalItems;
					
				$PAGES['vars'] = $vars;
				$PAGES['touchedblocks'] = $touchedBlocks;
				$mainCache->setData($PAGES,$cacheKey,$cacheGrp);
			}

			if(!empty($PAGES['vars'])) $VARS = array_merge($VARS, $PAGES['vars']);
			if(!empty($PAGES['touchedblocks'])) $TOUCHEDBLOCKS = array_merge($TOUCHEDBLOCKS, $PAGES['touchedblocks']);
		} else {
			$TOUCHEDBLOCKS['nopages'] = true;
		}

			/**
			 * SEARCH ITEMS
			 *
			 */
		if(!empty($pageSearchCache['filtrItems'])) {
			$p = 1;
			if(isset($_GET['pi'])) $p = (int) $_GET['pi'];
			$mainCache = FCache::getInstance('f',0);
			$cacheKey = $pageSearchCache['filtrItems'].'-i-'.(($p>1)?('-p-'.$p):(''));
			$cacheGrp = 'search-'.$user->userVO->userId;
			$ITEMS = $mainCache->getData($cacheKey,$cacheGrp);
			if(false === $ITEMS) {
				$fItems = new FItems('',$user->userVO->userId);
				if(SITE_STRICT) $fItems->addWhere("sys_pages_items.pageIdTop = '".SITE_STRICT."'");
				$fItems->addFulltextSearch('text,enclosure,addon,textLong',$pageSearchCache['filtrItems']);
				$listArr = page_ItemsList::buildList($fItems,$user->pageVO,array('hash'=>'items','urlVar'=>'pi'));
				$ITEMS['vars'] = $listArr['vars'];
				$ITEMS['vars']['FILTRITEMS'] = $pageSearchCache['filtrItems'];
				if(!empty($listArr['blocks'])) $ITEMS['touchedblocks'] = $listArr['blocks'];
				$mainCache->setData($ITEMS,$cacheKey,$cacheGrp);
			}


			if(!empty($ITEMS['vars'])) $VARS = array_merge($VARS, $ITEMS['vars']);
			if(!empty($ITEMS['touchedblocks'])) $TOUCHEDBLOCKS = array_merge($TOUCHEDBLOCKS, $ITEMS['touchedblocks']);
		} else {
			$TOUCHEDBLOCKS['noitems'] = true;
		}
		
		/**
		 * SEARCH USERS
		 *
		 */
		if(!empty($pageSearchCache['filtrUsers'])) {
			$p = 1;
			if(isset($_GET['pu'])) $p = (int) $_GET['pu'];
			$mainCache = FCache::getInstance('f',0);
			$cacheKey = $pageSearchCache['filtrUsers'].'-u-'.(($p>1)?('-p-'.$p):(''));
			$cacheGrp = 'search-'.$user->userVO->userId;
			$USERS = $mainCache->getData($cacheKey,$cacheGrp);

			if(false === $USERS) {
				$vars = array();
				$touchedBlocks = array();

				$userVO = new UserVO();
				$vo = new FDBvo( $userVO );
				$vo->VO = 'UserVO';
				$vo->addWhereSearch("sys_users.name",$pageSearchCache['filtrUsers']);
				$vo->addWhere("sys_users.deleted=0");
				$vo->setOrder('sys_users.name');
				$vo->setLimit(0,2);
				$arr = $vo->getContent();
				
				$pager = new FPager(0,$perPage,array('noAutoparse'=>1,'hash'=>'users','urlVar'=>'pu'));
				$from = ($pager->getCurrentPageID()-1) * $perPage;
				$totalItems = 0;
				if(!empty($arr)) {
					$totalItems = count($arr);
				}
				$maybeMore = false;
				if($totalItems > $perPage) {
					$maybeMore = true;
					array_pop($arr);
				}

				if($from > 0) $totalItems += $from;
				$ret = '';
				if($totalItems > 0) {
					$pager->totalItems = $totalItems;
					$pager->maybeMore = $maybeMore;
					$pager->getPager();
					$vars['USERS'] = FUser::usersList( $arr );
					if ($totalItems > $perPage) $vars['USERSPAGER'] = $pager->links;
				} else {
					$touchedBlocks['nousers'] = true;
				}
				$vars['TOTALUSERS'] = ($maybeMore===true)?($perPage.'+'):($totalItems);
				$vars['FILTRUSERS'] = $pageSearchCache['filtrUsers'];
				
				$USERS['vars'] = $vars;
				$USERS['touchedblocks'] = $touchedBlocks;
				$mainCache->setData($USERS,$cacheKey,$cacheGrp);
			}


			if(!empty($USERS['vars'])) $VARS = array_merge($VARS, $USERS['vars']);
			if(!empty($USERS['touchedblocks'])) $TOUCHEDBLOCKS = array_merge($TOUCHEDBLOCKS, $USERS['touchedblocks']);
		} else {
			$TOUCHEDBLOCKS['nousers'] = true;
		}
		

		$VARS['SEARCHACTION'] = FSystem::getUri();

		$tpl = FSystem::tpl('pages.search.tpl.html');
		$tpl->setVariable($VARS);
		if(!empty($TOUCHEDBLOCKS)) $tpl->touchedBlocks = $TOUCHEDBLOCKS;

		$ret = $tpl->get();
		FBuildPage::addTab(array( "MAINDATA"=>$ret ));

	}
}
