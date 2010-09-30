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
		
		/*
		if(isset($_GET[$urlVar])) $p = (int) $_GET[$urlVar];
		$mainCache = FCache::getInstance('f',0);
		$cacheKey = (($user->pageVO->pageIdTop)?($user->pageVO->pageIdTop):('')).'p-'.$user->pageId.'-c-'.$category.'-u-'.$user->userVO->userId.(($p>1)?('-p-'.$p):(''));
		$cacheGrp = 'pagelist';
		$ret = $mainCache->getData($cacheKey,$cacheGrp);
*/
		$ret = false;
		
		if(false === $ret) {

			$userId = $user->userVO->userId;
			$typeId = $user->pageVO->typeIdChild;

			//---QUERY RESULTS
			$fPages = new FPages($typeId, $userId);
								
			if($category > 0) {
				$categoryArr = FCategory::getCategory($category);
				$user->pageVO->htmlName =  $categoryArr[2] . ' - ' . $user->pageVO->name;
				$fPages->addWhere("sys_pages.categoryId=".$category);
			}
			if(SITE_STRICT == 1) {
				$fPages->addWhere("sys_pages.pageIdTop = '".HOME_PAGE."'");
			}
			if($typeId == 'galery') {
				$fPages->setOrder("dateContent desc");
			} else {
				$fPages->joinOnPropertie('itemIdLast');
				$fPages->setOrder("dateUpdated desc");
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
					//TODO: galery listing - maybe get galery top foto different way, do not render whole item, just thumb, faster?				
					$fItems = new FItems('galery',$user->userVO->userId);
					$fItems->thumbInSysRes = true;
					$fItems->setOrder('hit desc');

					$tplGal = FSystem::tpl('item.galerylink.tpl.html');
					foreach ($arr as $gal) {
						
						$fItems->setWhere('pageId="'.$gal->pageId.'" and (itemIdTop is null or itemIdTop=0)');
						$fotoThumb = $fItems->render(0,1);

						$tplGal->setCurrentBlock('item');
						$tplGal->setVariable("THUMB",$fotoThumb);
						$tplGal->setVariable("PAGEID",$gal->pageId);
						$tplGal->setVariable("PAGELINK",FSystem::getUri('',$gal->pageId));
						$tplGal->setVariable("PAGENAME",$gal->name);
						$tplGal->setVariable("DATELOCAL",$gal->date('dateContent','date'));
						$tplGal->setVariable("DATEISO",$gal->date('dateContent','iso'));
						$tplGal->setVariable("GALERYTEXT",$gal->description);
						if($gal->unreaded>0)$tplGal->setVariable("FOTONEW",$gal->unreaded);
						$tplGal->setVariable("FOTONUM",$gal->cnt);
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
			//$mainCache->setData($ret,$cacheKey,$cacheGrp);
		}

		FBuildPage::addTab(array( "MAINDATA"=>$ret ));

	}
}
