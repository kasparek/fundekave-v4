<?php
$pageSearchCache = & $user->pagesSearch;
if(empty($pageSearchCache)) $pageSearchCache = array('perpage'=>SEARCH_PERPAGE,'filtrStr'=>'','categoryId'=>0,'action'=>1,'data'=>array(),'maybemore'=>false);

$redir = false;
if(isset($_POST["perpage"])) {
	$perpage = (int) $_POST["perpage"];
	if ($perpage > 2 && $perpage != $pageSearchCache['perpage']) {
		$pageSearchCache['perpage'] = $perpage;
		$redir=true;
	}	
}
//--search section
if(isset($_POST['kat'])) {
    $catId = (int) $_POST["kat"];
    if($catId != $pageSearchCache['categoryId']) {
    	$pageSearchCache['action'] = 1;
    	$pageSearchCache['categoryId'] = $catId;
    	$redir = true;
    }
}
if(isset($_POST['filtr'])) {
	if($_POST['filtr'] !== $pageSearchCache['filtrStr']) {
		$pageSearchCache['filtrStr'] = fSystem::textins($_POST['filtr'],array('plainText'=>1));
		$pageSearchCache['action']=1;
		$redir = true;
	}
}
$searchForTotal = 100;
	
if($pageSearchCache['action']==1) {
	$fPages = new fPages($user->currentPage['typeIdChild'],$user->gid,$db);
    if(!empty($pageSearchCache['categoryId'])) $fPages->addWhere("p.categoryId=".$pageSearchCache['categoryId']);
    
	if(!empty($pageSearchCache['filtrStr'])){
	    $fPages->addWhereSearch(array('p.name','p.description','p.authorContent','p.dateContent'),$pageSearchCache['filtrStr'],'OR');
	}
	$fPages->setSelect('p.pageId,p.name,p.userIdOwner,p.authorContent,date_format(p.dateContent,"{#date_local#}")');
	$fPages->setOrder('p.dateContent',true);
	$fPages->setLimit(0,$searchForTotal+1);
	$arr = $fPages->getContent();
	if(count($arr)>$searchForTotal) $pageSearchCache['maybeMore'] = true; else $pageSearchCache['maybeMore'] = false;
	$pageSearchCache['data'] = serialize($arr);
	$pageSearchCache['action'] = 0;
}

if($redir) fHTTP::redirect($user->getUri());


//--input form for search
$arrkat = $db->getAll("SELECT categoryId,name FROM sys_pages_category where typeId='galery'".(($user->idkontrol)?(''):(' and public=1 '))." ORDER BY name");

$tpl = new fTemplateIT('pages.search.tpl.html');

$tpl->setVariable('FORMACTION',$user->getUri());
$categoryOptions='';
foreach ($arrkat as $kateg)
	$categoryOptions.='<option value="'.$kateg[0].'"'.(($pageSearchCache['categoryId']==$kateg[0])?(' selected="selected"'):('')).'>'.$kateg[1].'</option>';
$tpl->setVariable('CATEGORYOPTIONS',$categoryOptions);
$tpl->setVariable('FILTRTEXT',$pageSearchCache['filtrStr']);
$tpl->setVariable('PERPAGE',$pageSearchCache['perpage']);

$arrPagesFound = unserialize($pageSearchCache['data']);

if(!empty($arrPagesFound)) {
	//--pagination
	$totalItems = count($arrPagesFound);
	$pager = fSystem::initPager($totalItems,$pageSearchCache['perpage'],array('itemData'=>$arrPagesFound));
	$arrPagesPaginated = $pager->getPageData();
	
	$tpl->setVariable('TOTAL',$totalItems);
	if($totalItems > $pageSearchCache['perpage']) $tpl->setVariable('PAGER',$pager->links);
	if($pageSearchCache['maybemore']==true) $tpl->touchBlock('maybemore');
	
	foreach ($arrPagesPaginated as $row){
	    $tpl->setCurrentBlock('result');
	    $tpl->setVariable('RNAME',$row[1]);
	    $tpl->setVariable('RLINK','?k='.$row[0]);
	    $tpl->setVariable('RDATE',$row[4]);
	    $tpl->setVariable('OWNERLINK','?k=finfo&who='.$row[2]);
	    $tpl->setVariable('OWNERNAME',$row[3]);
	    $tpl->parseCurrentBlock();
	}
} else {
	$tpl->touchBlock('noresults');
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));