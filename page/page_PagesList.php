<?php
include_once('iPage.php');
class page_PagesList implements iPage {

	static function process($data) {

	}

	static function build($data=array()) {

		$user = FUser::getInstance();
		$category = 0;
		if($user->categoryVO) $category = $user->categoryVO->categoryId;

		$userId = $user->userVO->userId;
		$typeId = $user->pageParam;
		if(!empty($user->pageVO->typeIdChild)) $typeId=$user->pageVO->typeIdChild;
		if(!isset(FLang::$TYPEID[$typeId])) $typeId=array_keys(FLang::$TYPEID);

		if($user->idkontrol) {
			if($user->pageParam=='a') {
				switch($data['__get']['t']){
				case 'forum':
				$heading=FLang::$LABEL_PAGE_FORUM_NEW;
				break;
				case 'blog':
				$heading=FLang::$LABEL_PAGE_BLOG_NEW;
				break;
				case 'galery':
				$heading=FLang::$LABEL_PAGE_GALERY_NEW;
				break;
				}
				$user->pageVO->htmlName=$heading;
				page_PageEdit::build($data);
				return;
			}
			if($typeId!='galery') {
				if(FRules::getCurrent(FConf::get('settings','perm_add_forum')))FMenu::secondaryMenuAddItem(FSystem::getUri('t=forum',$user->pageVO->pageId,'a'), FLang::$LABEL_PAGE_FORUM_NEW);
				if(FRules::getCurrent(FConf::get('settings','perm_add_blog')))FMenu::secondaryMenuAddItem(FSystem::getUri('t=blog',$user->pageVO->pageId,'a'), FLang::$LABEL_PAGE_BLOG_NEW);
			}
			if(FRules::getCurrent(FConf::get('settings','perm_add_galery')))FMenu::secondaryMenuAddItem(FSystem::getUri('t=galery',$user->pageVO->pageId,'a'), FLang::$LABEL_PAGE_GALERY_NEW);
		}
		
		$user->pageVO->showHeading = false;

		//---QUERY RESULTS
		$fPages = new FPages($typeId, $userId);

		if($category > 0) {
			$categoryArr = FCategory::getCategory($category);
			if(!empty($categoryArr)) {
				if($categoryArr[1]=='galery') $typeId='galery';
				$user->pageVO->htmlName =  $categoryArr[2] . ' - ' . $user->pageVO->name;
				$fPages->addWhere("sys_pages.categoryId=".$category);
			}
		}
		if(SITE_STRICT) {
			$fPages->addWhere("sys_pages.pageIdTop = '".SITE_STRICT."'");
		}
		if($typeId == 'galery') {
			$fPages->setOrder("dateContent desc");
			if(!$user->idkontrol) $fPages->addWhere('sys_pages.cnt>0');
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

				$fItems = new FItems('galery',$user->userVO->userId);
				$fItems->thumbInSysRes = true;
				$fItems->setOrder('hit desc');

				$tplGal = FSystem::tpl('item.galerylink.tpl.html');
				foreach ($arr as $gal) {
					$fItems->setWhere('sys_pages_items.pageId="'.$gal->pageId.'" and (itemIdTop is null or itemIdTop=0)');
					$fItems->setOrder('sys_pages_items.hit desc');
					$itemList = $fItems->getList(0,1);
					if(!empty($itemList)) {
						$fotoItemVO = $itemList[0];
						$tplGal->setVariable("IMGURL",$fotoItemVO->detailUrl);
						$tplGal->setVariable("IMGURLTHUMB",$fotoItemVO->thumbUrl);
					}
					$tplGal->setVariable("PAGEID",$gal->pageId);
					$tplGal->setVariable("PAGELINK",FSystem::getUri('',$gal->pageId,''));
					$tplGal->setVariable("PAGENAME",$gal->name);
					$tplGal->setVariable("DATELOCAL",$gal->date($gal->dateContent,'date'));
					$tplGal->setVariable("DATEISO",$gal->date($gal->dateContent,'iso'));
					$tplGal->setVariable("GALERYTEXT",$gal->description);
					if($gal->unreaded>0)$tplGal->setVariable("FOTONEW",$gal->unreaded);
					$tplGal->setVariable("FOTONUM",$gal->cnt);
					$tplGal->parse('item');
				}
				$tpl->setVariable('PAGELINKS',$tplGal->get());

			} else {
				$tpl->setVariable('PAGELINKS',FPages::printPagelinkList($arr));
			}
			//---pager
			if($totalItems > $perPage) {
				$tpl->setVariable('BOTTOMPAGER',$pager->links);
			}

		} else {
			$tpl->touchBlock('noresults');
		}
		$ret = $tpl->get();
		//$mainCache->setData($ret,$cacheKey,$cacheGrp);


		FBuildPage::addTab(array( "MAINDATA"=>$ret ));

	}
}
