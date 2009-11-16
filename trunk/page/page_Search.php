<?php
include_once('iPage.php');
class page_Search implements iPage {

	static function process($data) {
		$invalidate = false;
		$user = FUser::getInstance();

		$cache = FCache::getInstance('s');
		$pageSearchCache = $cache->getData($user->pageVO->pageId,'search');
		if(!isset($pageSearchCache['filtrStr'])) $pageSearchCache['filtrStr']='';
		if(!isset($pageSearchCache['categoryId'])) $pageSearchCache['categoryId']='0';

		if(isset($_REQUEST['c'])) $data['c'] = $_REQUEST['c'];
		if(isset($data['c'])) {
			$catId = (int) $data["c"];
			if($catId != $pageSearchCache['categoryId']) {
				$pageSearchCache['categoryId'] = $catId;
			}
		}

		if(isset($_REQUEST['f'])) $data['filtr'] = $_REQUEST['f'];
		if(isset($data['filtr'])) {
			if($data['filtr'] !== $pageSearchCache['filtrStr']) {
				$pageSearchCache['filtrStr'] = FSystem::textins($data['filtr'],array('plainText'=>1));
			}
		}
		
		$pageSearchCache = $cache->setData($pageSearchCache,$user->pageVO->pageId,'search');
		
		if($invalidate === true) {
			$mainCache = FCache::getInstance('f',0);
			$mainCache->invalidateCache('user-'.$user->userVO->userId,'page-'.$user->pageId);
		}
	}

	static function invalidate() {
		$user = FUser::getInstance();
		$cacheGrp = 'page-'.$user->pageId;
		$mainCache = FCache::getInstance('f',0);
		$mainCache->invalidateGroup($cacheGrp);
	}
	
	static function build($data=array()) {
		$user = FUser::getInstance();
		
		$p = 1;
		$urlVar = FConf::get('pager','urlVar');
		if(isset($_GET[$urlVar])) $p = (int) $_GET[$urlVar];
		$mainCache = FCache::getInstance('f',0);
		$cacheKey = 'user-'.$user->userVO->userId.(($p>1)?('-p-'.$p):(''));
		$cacheGrp = 'page-'.$user->pageId;
		$ret = $mainCache->getData($cacheKey,$cacheGrp);
		
		if(false === $ret) { 
		
		$cache = FCache::getInstance('s');
		$pageSearchCache = $cache->getData($user->pageVO->pageId,'search');

		$userId = $user->userVO->userId;
		$typeId = $user->pageVO->typeIdChild;

		if ( $userId > 0 ) {
			FForum::clearUnreadedMess();
			FItems::afavAll( $userId );
		}

		//---QUERY RESULTS
		$fPages = new FPages($typeId, $userId);
		//$fPages->cacheResults = 's';
		if(!empty($pageSearchCache['categoryId'])) $fPages->addWhere("p.categoryId=".$pageSearchCache['categoryId']);
		if(!empty($pageSearchCache['filtrStr'])){
			$fPages->addWhereSearch(array('p.name','p.description','p.authorContent','p.dateContent'),$pageSearchCache['filtrStr'],'OR');
		}
		if($typeId == 'galery') {
			$fPages->setSelect("p.pageId,p.name,p.userIdOwner,date_format(dateContent,'{#date_local#}') as datumcz,description,date_format(dateContent,'{#date_iso#}') as diso");
			$fPages->setOrder("dateContent desc,pageId desc");
			$fPages->addWhere('p.locked < 2');
		} else {
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')).',pplastitem.value,p.typeId');
			$fPages->addJoin('left join sys_pages_properties as pplastitem on pplastitem.pageId=p.pageId and pplastitem.name = "itemIdLast"');
			if($user->idkontrol!==true) {
				$fPages->addWhere('p.locked < 2');
			} else {
				$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
			}
			$fPages->setOrder("p.dateUpdated desc,p.name");
		}
		$perPage = $user->pageVO->perPage();
		$pager = new FPager(0,$perPage ,array('noAutoparse'=>1));
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
		$tpl = FSystem::tpl('pages.list.tpl.html');

		//---show results if any
		if($totalItems > 0) {
			//--pagination
			$pager->totalItems = $totalItems;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();

			//---results
			if($typeId == 'galery') {
				$itemRenderer = new FItemsRenderer();
				$itemRenderer->showTooltip = false;
				$itemRenderer->showText = false;
				$itemRenderer->showTag = false;
				$itemRenderer->showPageLabel = false;
				$itemRenderer->showRating = false;
				$itemRenderer->showHentryClass = false;
				$itemRenderer->openPopup = false;
				$itemRenderer->showPocketAdd = false;
				$itemRenderer->showComments = false;
				$itemRenderer->showCommentsNum = false;
				$fItems = new FItems('galery',$user->userVO->userId,$itemRenderer);
				$fItems->thumbInSysRes = true;
      			$fItems->setOrder('hit desc');
      			
      			$tplGal = FSystem::tpl('item.galerylink.tpl.html');
				foreach ($arr as $gal) {
					$fItems->setWhere('pageId="'.$gal[0].'"');
					$fotoThumb = $fItems->render(0,1);
					$tplGal->setCurrentBlock('item');
					$tplGal->setVariable("THUMB",$fotoThumb);
					$tplGal->setVariable("PAGEID",$gal[0]);
					$tplGal->setVariable("PAGELINK",FSystem::getUri('',$gal[0]));
					$tplGal->setVariable("PAGENAME",$gal[1]);
					$tplGal->setVariable("DATELOCAL",$gal[3]);
					$tplGal->setVariable("DATEISO",$gal[5]);
					$tplGal->setVariable("GALERYTEXT",$gal[4]);
					$tplGal->parseCurrentBlock();
				}
				$tpl->setVariable('PAGELINKS',$tplGal->get());
				
			} else {
				$tpl->setVariable('PAGELINKS',FPages::printPagelinkList($arr));
			}
			//---pager
			if($totalItems > $perPage) {
				$tpl->setVariable('TOPPAGER',$pager->links);
				$tpl->setVariable('BOTTOMPAGER',$pager->links);
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
