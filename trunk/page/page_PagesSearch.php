<?php
include_once('iPage.php');
class page_PagesSearch implements iPage {

	static function process() {

		$cache = FCache::getInstance('s');
		$pageSearchCache = $cache->getData($user->pageVO->pageId,'search');

		if(isset($_POST['kat'])) {
			$catId = (int) $_POST["kat"];
			if($catId != $pageSearchCache['categoryId']) {
				$pageSearchCache['categoryId'] = $catId;
				$pageSearchCache = $cache->setData($pageSearchCache,$user->pageVO->pageId,'search');
			}
		}

		if(isset($_POST['filtr'])) {
			if($_POST['filtr'] !== $pageSearchCache['filtrStr']) {
				$pageSearchCache['filtrStr'] = FSystem::textins($_POST['filtr'],array('plainText'=>1));
				$pageSearchCache = $cache->setData($pageSearchCache,$user->pageVO->pageId,'search');
			}
		}
		
	}

	static function build() {


		$user = FUser::getInstance();
		$cache = FCache::getInstance('s');
		$pageSearchCache = $cache->getData($user->pageVO->pageId,'search');

		if(empty($pageSearchCache)) {
			$pageSearchCache = array('filtrStr'=>'','categoryId'=>0);
		}

		$fPages = new FPages($user->currentPage['typeIdChild'],$user->userVO->userId);
		$fItems->cacheResults = 's';
		if(!empty($pageSearchCache['categoryId'])) $fPages->addWhere("p.categoryId=".$pageSearchCache['categoryId']);

		if(!empty($pageSearchCache['filtrStr'])){
			$fPages->addWhereSearch(array('p.name','p.description','p.authorContent','p.dateContent'),$pageSearchCache['filtrStr'],'OR');
		}
		$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0');
		$fPages->setOrder('p.dateContent',true);

		$pager = FSystem::initPager(0,SEARCH_PERPAGE,array('noAutoparse'=>1));
		$from = ($pager->getCurrentPageID()-1) * SEARCH_PERPAGE;
		$fPages->setLimit($from,SEARCH_PERPAGE+1);

		$arr = $fPages->getContent();
		$totalItems = count($arr);

		$maybeMore = false;
		if($totalItems > SEARCH_PERPAGE) {
			$maybeMore = true;
			unset($arr[(count($arr)-1)]);
		}
		if($from > 0) $totalItems += $from;


		//--input form for search
		$typeId = $user->pageVO->typeIdChild;
		$q = "SELECT categoryId,name FROM sys_pages_category where typeId='".$typeId."'".(($user->idkontrol)?(''):(' and public=1 '))." ORDER BY name";
		$arrkat = FDBTool::getAll($q,$user->pageVO->pageId.'-'.$typeId,'categ');

		$tpl = new FTemplateIT('pages.search.tpl.html');

		$tpl->setVariable('FORMACTION',FUser::getUri());
		$categoryOptions='';
		foreach ($arrkat as $kateg)
		$categoryOptions.='<option value="'.$kateg[0].'"'.(($pageSearchCache['categoryId']==$kateg[0])?(' selected="selected"'):('')).'>'.$kateg[1].'</option>';
		$tpl->setVariable('CATEGORYOPTIONS',$categoryOptions);
		$tpl->setVariable('FILTRTEXT',$pageSearchCache['filtrStr']);

		if($totalItems > 0) {
			//--pagination
			$pager->totalItems = $totalItems;
			$pager->maybeMore = $maybeMore;
			$pager->getPager();

			//---results
			$tpl->setVariable('PAGELINKS',FPages::printPagelinkList($arr));
			//---pager
			if($totalItems > SEARCH_PERPAGE) {
				$tpl->setVariable('TOPPAGER',$pager->links);
				$tpl->setVariable('BOTTOMPAGER',$pager->links);
			}

		} else {
			$tpl->touchBlock('noresults');
		}
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}