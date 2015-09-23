<?php
include_once('iPage.php');
class page_PagesList implements iPage {

	static function process($data) {

	}

	static function build($data=array(),$override=array()) {

		$user = FUser::getInstance();
		$category = 0;
		if($user->categoryVO) $category = $user->categoryVO->categoryId;

		$userId = $user->userVO->userId;
		$typeId = $user->pageParam;
		if(!empty($user->pageVO->typeIdChild)) $typeId=$user->pageVO->typeIdChild;
		
		if(!empty($override['typeId'])) $typeId = $override['typeId'];
		if(!empty($override['categoryId'])) $category = $override['categoryId'];
		
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
		}
		
		//$user->pageVO->showHeading = false;

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
		$inDate = $user->inDate();
		if(!empty($override['inDate'])) $inDate = $override['inDate'];
		$sort = false;
		if(!empty($override['sort'])) {
			$sort = $override['sort'];
		}
		
		if($typeId == 'galery') {
			if(!empty($user->pageVO->pageIdTop)) {
				$fPages->addWhere("sys_pages.pageIdTop = '".$user->pageVO->pageIdTop."'");
			}
			if($inDate) $fPages->addWhere("sys_pages.dateContent like '".$inDate."%'");
			if($sort) {
				$fPages->setOrder($sort);
			} else {
				$fPages->setOrder("dateContent desc");
			}
			if(!$user->idkontrol) $fPages->addWhere('sys_pages.cnt>0');
		} else {
			$fPages->joinOnPropertie('itemIdLast');
			if($inDate) $fPages->addWhere("sys_pages.dateUpdated like '".$inDate."%'");
			if($sort) {
				$fPages->setOrder($sort);
			} else {
				$fPages->setOrder("dateUpdated desc");
			}
		}

		$perPage = $user->pageVO->perPage();
		$pager = 0;
		$from = 0;
		if(empty($override['nopager'])) {
			$pager = new FPager(0,$perPage ,array('noAutoparse'=>1));
			$from = ($pager->getCurrentPageID()-1) * $perPage;
		}
		if(empty($override['limit']) && $override['limit']>0) {
			$fPages->setLimit( $from, $perPage+1 );
		}
		
		//$uid = $fPages->getUID($from, $perPage+1);
		//if(!empty($override['nopager'])) $uid.='nopager';
		//if(is_array($typeId)) $cachetype= count($typeId)>1 ? 'all' : $typeId[0]; else $cachetype = $typeId;
		//$grpid = 'pages/'.($cachetype?$cachetype:'all');
		//$cache = FCache::getInstance('f');
		//$data = $cache->getData($uid,$grpid);
		$data = false;
		if($data===false) {
			$arr = $fPages->getContent();
			$totalItems = count($arr);
			$maybeMore = false;
			if($totalItems > $perPage) {
				$maybeMore = true;
				array_pop($arr);
			}
			if($from > 0) $totalItems += $from;
			//---BUILD PAGE
			$tpl = FSystem::tpl('pages.list.tpl.html');
			//---show results if any
			if(!empty($arr)) {
				//--pagination
				if($pager) {
					$pager->totalItems = $totalItems;
					$pager->maybeMore = $maybeMore;
					$pager->getPager();
				}

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
							$tplGal->setVariable("IMGURL",FSystem::getUri('i='.$fotoItemVO->itemId,$gal->pageId,''));
							$tplGal->setVariable("IMGURLTHUMB",$fotoItemVO->thumbUrl);
						}
						$tplGal->setVariable("PAGEID",$gal->pageId);

						$tplGal->setVariable("CATEGORY",$gal->categoryId);
						$tplGal->setVariable("YEAR",substr($gal->date($gal->dateContent,'iso'), 0, 4));
						$tplGal->setVariable("MONTH",substr($gal->date($gal->dateContent,'iso'), 5, 2)*1);
						$tplGal->setVariable("DATE",substr($gal->date($gal->dateContent,'iso'), 8, 2)*1);

						$tplGal->setVariable("PAGELINK",FSystem::getUri('',$gal->pageId,''));
						$tplGal->setVariable("PAGENAME",$gal->name);
						$tplGal->setVariable("DATELOCAL",$gal->date($gal->dateContent,'date'));
						$tplGal->setVariable("DATEISO",$gal->date($gal->dateContent,'iso'));
						$tplGal->setVariable("GALERYTEXT",$gal->description);
						if($gal->unreaded>0) $tplGal->setVariable("FOTONEW",$gal->unreaded);
						$tplGal->setVariable("FOTONUM",$gal->cnt);
						$tplGal->parse();
					}
					$tpl->setVariable('PAGELINKS',$tplGal->get());

				} else {
					$tpl->setVariable('PAGELINKS',FPages::printPagelinkList($arr));
				}
				//---pager
				if($pager) {
					if($totalItems > $perPage) {
						$tpl->setVariable('BOTTOMPAGER',$pager->links);
					}
				}

			} else {
				$tpl->touchBlock('noresults');
			}
			$data = $tpl->get();
		
			//$cache->setData($data,$uid,$grpid);
		}
		
		if(!empty($override['return'])) return $data;
		FBuildPage::addTab(array( "MAINDATA"=>$data ));
	}
}
