<?php
$pageSearchCache = & $user->pagesSearch;
if(empty($pageSearchCache)) {
    $pageSearchCache = array('perpage'=>SEARCH_PERPAGE,'filtrStr'=>'','categoryId'=>0,'action'=>1,'data'=>array(),'maybemore'=>false);
}

//--search section
if(isset($_POST['kat'])) {
    $catId = (int) $_POST["kat"];
    if($catId != $pageSearchCache['categoryId']) {
    	$pageSearchCache['action'] = 1;
    	$pageSearchCache['categoryId'] = $catId;
    }
}
if(isset($_POST['filtr'])) {
	if($_POST['filtr'] !== $pageSearchCache['filtrStr']) {
		$pageSearchCache['filtrStr'] = fSystem::textins($_POST['filtr'],array('plainText'=>1));
		$pageSearchCache['action']=1;
	}
}

	

	$fPages = new fPages($user->currentPage['typeIdChild'],$user->userVO->userId);
	$fItems->cacheResults = true;
    if(!empty($pageSearchCache['categoryId'])) $fPages->addWhere("p.categoryId=".$pageSearchCache['categoryId']);
    
	if(!empty($pageSearchCache['filtrStr'])){
	    $fPages->addWhereSearch(array('p.name','p.description','p.authorContent','p.dateContent'),$pageSearchCache['filtrStr'],'OR');
	}
	$fPages->setSelect('p.pageId,p.categoryId,p.name,p.pageIco,0');
	$fPages->setOrder('p.dateContent',true);
	
	$pager = fSystem::initPager(0,SEARCH_PERPAGE,array('noAutoparse'=>1));
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
$arrkat = $db->getAll("SELECT categoryId,name FROM sys_pages_category where typeId='".$user->currentPage['typeIdChild']."'".(($user->idkontrol)?(''):(' and public=1 '))." ORDER BY name");

$tpl = new fTemplateIT('pages.search.tpl.html');

$tpl->setVariable('FORMACTION',$user->getUri());
$categoryOptions='';
foreach ($arrkat as $kateg)
	$categoryOptions.='<option value="'.$kateg[0].'"'.(($pageSearchCache['categoryId']==$kateg[0])?(' selected="selected"'):('')).'>'.$kateg[1].'</option>';
$tpl->setVariable('CATEGORYOPTIONS',$categoryOptions);
$tpl->setVariable('FILTRTEXT',$pageSearchCache['filtrStr']);
$tpl->setVariable('PERPAGE',$pageSearchCache['perpage']);


if($totalItems > 0) {
	//--pagination
	$pager->totalItems = $totalItems;
	$pager->maybeMore = $maybeMore;
	$pager->getPager();
		
	//---results
	$tpl->setVariable('PAGELINKS',fPages::printPagelinkList($arr));
	//---pager
	if($totalItems > $pageSearchCache['perpage']) {
	    $tpl->setVariable('TOPPAGER',$pager->links);
	    $tpl->setVariable('BOTTOMPAGER',$pager->links);
	}
	
} else {
	$tpl->touchBlock('noresults');
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));