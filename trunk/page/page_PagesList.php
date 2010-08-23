<?php
include_once('iPage.php');
class page_PagesList implements iPage {

	static function process($data) {

	}

	static function invalidate() {
		$user = FUser::getInstance();
		$cacheGrp = 'pagelist';
		$mainCache = FCache::getInstance('f',0);
		$mainCache->invalidateGroup($cacheGrp);
	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;
		$category = 0;
		if(isset($_REQUEST['c'])) $category = (int) $_REQUEST['c'];
		$p = 1;
		$urlVar = FConf::get('pager','urlVar');
		if(isset($_GET[$urlVar])) $p = (int) $_GET[$urlVar];
		$mainCache = FCache::getInstance('f',0);
		$cacheKey = (($user->pageVO->pageIdTop)?($user->pageVO->pageIdTop):('')).'p-'.$user->pageId.'-c-'.$category.'-u-'.$user->userVO->userId.(($p>1)?('-p-'.$p):(''));
		$cacheGrp = 'pagelist';
		$ret = $mainCache->getData($cacheKey,$cacheGrp);

		if(false === $ret) {

			$userId = $user->userVO->userId;
			$typeId = $user->pageVO->typeIdChild;

			if ( $userId > 0 ) {
				FForum::clearUnreadedMess();
				FItems::afavAll( $userId );
			}

			//---QUERY RESULTS
			$fPages = new FPages($typeId, $userId);
			
			$fItems->cacheResults = 'f';
			$fItems->cacheGroup = 'fdb-pagelist';
			$fItems->lifeTime = '86400';
					
			if($category > 0) {
				$categoryArr = FCategory::getCategory($category);
				$user->pageVO->htmlName =  $categoryArr[2] . ' - ' . $user->pageVO->name;
				$fPages->addWhere("p.categoryId=".$category);
			}
			if(SITE_STRICT == 1) {
				$fPages->addWhere("p.pageIdTop = '".HOME_PAGE."'");
			}
			if($typeId == 'galery') {
				$fPages->setSelect("p.pageId,p.name,p.userIdOwner,date_format(dateContent,'{#date_local#}') as datumcz,description,date_format(dateContent,'{#date_iso#}') as diso,p.cnt as total".(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')));
				$fPages->setOrder("dateContent desc");
				if($user->idkontrol!==true) {
					$fPages->addWhere('p.locked < 2');
				} else {
					$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
				}
			} else {
				$fPages->fetchmode = 1;
				$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')).',pplastitem.value as itemId,p.typeId');
				$fPages->addJoin('left join sys_pages_properties as pplastitem on pplastitem.pageId=p.pageId and pplastitem.name = "itemIdLast"');
				if($user->idkontrol!==true) {
					$fPages->addWhere('p.locked < 2');
				} else {
					$fPages->addJoin('left join sys_pages_favorites as f on p.pageId=f.pageId and f.userId= "'.$userId.'"');
				}
				$fPages->setOrder("p.dateUpdated desc");
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
					$fItems->cacheResults = 'f';
					$fItems->cacheGroup = 'fdb-galery';
					$fItems->lifeTime = '86400'; 
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
						$tplGal->setVariable("FOTOCSSDIR",FSystem::getSkinCSSFilename());
						if($gal[7]>0)$tplGal->setVariable("FOTONEW",$gal[7]);
						$tplGal->setVariable("FOTONUM",$gal[6]);
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
