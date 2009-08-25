<?php
include_once('iPage.php');
class page_PagesList implements iPage {

	static function process($data) {
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
	}

	static function build() {
		$user = FUser::getInstance();
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
		$fPages->cacheResults = 's';
		if(!empty($pageSearchCache['categoryId'])) $fPages->addWhere("p.categoryId=".$pageSearchCache['categoryId']);
		if(!empty($pageSearchCache['filtrStr'])){
			$fPages->addWhereSearch(array('p.name','p.description','p.authorContent','p.dateContent'),$pageSearchCache['filtrStr'],'OR');
		}
		if($typeId == 'galery') {
			$fPages->setSelect("p.pageId,p.name,p.userIdOwner,date_format(dateContent,'{#date_local#}') as datumcz,description,date_format(dateContent,'{#date_iso#}') as diso");
			$fPages->setOrder("dateContent desc,pageId desc");
			$fPages->addWhere('p.locked < 2');
		} else {
			$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco'.(($userId > 0)?(',(p.cnt-f.cnt) as newMess'):(',0')));
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
		$fPages->setLimit($from,$perPage+1);

		$arr = $fPages->getContent();
		$totalItems = count($arr);

		$maybeMore = false;
		if($totalItems > $perPage) {
			$maybeMore = true;
			unset($arr[(count($arr)-1)]);
		}
		if($from > 0) $totalItems += $from;

		//---BUILD PAGE
		$tpl = new FTemplateIT('pages.list.tpl.html');

		$tpl->setVariable('FORMACTION',FUser::getUri());

		//---populate categories
		$category = new FCategory('sys_pages_category','categoryId');
		$arrCats = $category->getCats($typeId);

		if(!empty($arrCats)) {
			foreach ($arrCats as $row) {
				//---selected cat
				if($row[0] == $pageSearchCache['categoryId']) {
					$tpl->setVariable('CATEGORYSELECTED',$row[1]);
					$tpl->setVariable('CATEGORYSELECTEDCANCEL',$user->getUri('c=0'));
					$user->pageVO->name =  $row[1] . ' - ' . $user->pageVO->name;
				}
				$tpl->setCurrentBlock('category');
				$tpl->setVariable('CATLINK',$user->getUri('c='.$row[0]));
				$tpl->setVariable('CATNAME',$row[1]);

				$tpl->parseCurrentBlock();
			}
		}

		//---populate filte input
		$tpl->setVariable('FILTRTEXT',$pageSearchCache['filtrStr']);
		if(!empty($pageSearchCache['filtrStr'])) {
			$tpl->setVariable('FILTRSELECTEDCANCEL',$user->getUri('f='));
		}

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
				$fItems = new FItems('galery',$user->userVO->userId,$itemRenderer);
      			$fItems->setOrder('hit desc');
      			
      			$tplGal = new FTemplateIT('item.galerylink.tpl.html');
				foreach ($arr as $gal) {
					$fItems->setWhere('pageId="'.$gal[0].'"');
					$fotoThumb = $fItems->render(0,1);
					$tplGal->setCurrentBlock('item');
					$tplGal->setVariable("THUMB",$fotoThumb);
					$tplGal->setVariable("PAGEID",$gal[0]);
					$tplGal->setVariable("PAGELINK",FUser::getUri('',$gal[0]));
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

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));

	}
}
