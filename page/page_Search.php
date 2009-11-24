<?php
include_once('iPage.php');
class page_Search implements iPage {

	static function process($data) {
		$invalidate = false;
		$user = FUser::getInstance();

		$cache = FCache::getInstance('s');
		$pageSearchCache = $cache->getPointer('search');
		if(!isset($pageSearchCache['filtrStr'])) $pageSearchCache['filtrStr']='';

		if(isset($_REQUEST['f'])) $data['filtr'] = $_REQUEST['f'];
		if(isset($data['filtr'])) {
			if($data['filtr'] !== $pageSearchCache['filtrStr']) {
				$pageSearchCache['filtrStr'] = FSystem::textins($data['filtr'],array('plainText'=>1));
				$invalidate = true;
			}
		}

		if($invalidate === true) {
			$mainCache = FCache::getInstance('f',0);
			$mainCache->invalidate($pageSearchCache['filtrStr'],'search-'.$user->userVO->userId);
		}
	}

	static function invalidate() {
		$user = FUser::getInstance();
		$mainCache = FCache::getInstance('f',0);
		$mainCache->invalidateGroup('search');
	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;

		$cache = FCache::getInstance('s');
		$pageSearchCache = $cache->getPointer('search');

		$p = 1;
		$urlVar = FConf::get('pager','urlVar');
		if(isset($_GET[$urlVar])) $p = (int) $_GET[$urlVar];
		$mainCache = FCache::getInstance('f',0);
		$cacheKey = $pageSearchCache['filtrStr'].(($p>1)?('-p-'.$p):(''));
		$cacheGrp = 'search-'.$user->userVO->userId;
		$ret = $mainCache->getData($cacheKey,$cacheGrp);

		if(false === $ret) {

			//---QUERY RESULTS
			$fPages = new FPages(array('galery','forum','blog'), $userId);
			$fPages->fetchmode=1;

			if(!empty($pageSearchCache['filtrStr'])){
				$fPages->addWhereSearch(array('p.name','p.description','p.authorContent','p.dateContent'),$pageSearchCache['filtrStr'],'OR');
			}

			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')).',p.typeId');
			if($user->idkontrol!==true) {
				$fPages->addWhere('p.locked < 2');
			} else {
				$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			}
			$fPages->setOrder("p.name");

			$perPage = $user->pageVO->perPage();
			$pager = new FPager(0,$perPage ,array('noAutoparse'=>1));
			$pager->extraVars['k'] = $user->pageVO->pageId;
			$from = ($pager->getCurrentPageID()-1) * $perPage;
			$fPages->setLimit( $from, $perPage+1 );

			$arr = $fPages->getContent();

			$totalItems = count($arr);

			$maybeMore = false;
			if($totalItems > $perPage) {
				$maybeMore = true;
				unset($arr[(count($arr)-1)]);
			}
			if($from > 0) $totalItems += $from;

			//---BUILD PAGE
			$tpl = FSystem::tpl('pages.search.tpl.html');

			//---show results if any
			if($totalItems > 0) {
				//--pagination
				$pager->totalItems = $totalItems;
				$pager->maybeMore = $maybeMore;
				$pager->getPager();

				//---results
					
				$tpl->setVariable('PAGES',FPages::printPagelinkList($arr));
					
				//---pager
				if($totalItems > $perPage) {
					
					$tpl->setVariable('PAGESPAGER',$pager->links);
				}

			} else {
				$tpl->touchBlock('noresults');
			}
			$ret = $tpl->get();
			$mainCache->setData($ret,$cacheKey,$cacheGrp);
		}

		FBuildPage::addTab(array( "MAINDATA"=>$ret ));

	}
}
