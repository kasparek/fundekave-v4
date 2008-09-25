<?php
$sessGallSearchCache = & $user->gallSearchCache;
if(empty($sessGallSearchCache)) $sessGallSearchCache = array('perpage'=>SEARCH_PERPAGE,'filtr'=>'','kategorie'=>0,'action'=>1,'data'=>array());

$redir = false;
if(isset($_POST["perpage"])) {
	$perpage = $_POST["perpage"] * 1;
	if ($perpage>2 && $perpage!=$sessGallSearchCache['perpage']) {
		$sessGallSearchCache['perpage']=$perpage;
		$redir=true;
	}	
}

//--search section
if(isset($_POST['kat']))
if($_POST['kat']!=$sessGallSearchCache['kategorie']) {
	$sessGallSearchCache['action']=1;
	$sessGallSearchCache['kategorie'] = $_POST['kat'] * 1;
	$redir = true;
}

if(isset($_POST['filtr'])) 
	if($_POST['filtr'] !== $sessGallSearchCache['filtr']) {
		$sessGallSearchCache['filtr'] = fSystem::textins($_POST['filtr'],0,0);
		$sessGallSearchCache['action']=1;
		$redir = true;
	}
	
	//echo $sessGallSearchCache['action'];
if($sessGallSearchCache['action']==1) {
	$fPages = new fPages('galery',$user->gid,$db);
    if(!empty($sessGallSearchCache['kategorie'])) $fPages->addWhere("p.categoryId=".$sessGallSearchCache['kategorie']);
    
	if(!empty($sessGallSearchCache['filtr'])){
	    $fPages->addWhereSearch(array('p.name','p.description','p.authorContent','p.dateContent'),$sessGallSearchCache['filtr'],'OR');
	}
	$fPages->setSelect('p.pageId,p.name,p.userIdOwner,p.authorContent,date_format(p.dateContent,"{#date_local#}")');
	$fPages->setOrder('p.dateContent',true);
	$arr=$fPages->getContent();
	$sessGallSearchCache['data'] = serialize($arr);
	$sessGallSearchCache['action'] = 0;
}

if($redir) fHTTP::redirect($user->getUri());


//--input form for search
$arrkat=$db->getAll("SELECT categoryId,name FROM sys_pages_category where typeId='galery'".(($user->idkontrol)?(''):(' and public=1 '))." ORDER BY name");

$tpl = new fTemplateIT('galery.search.tpl.html');

$tpl->setVariable('FORMACTION',$user->getUri());
$categoryOptions='';
foreach ($arrkat as $kateg)
	$categoryOptions.='<option value="'.$kateg[0].'"'.(($sessGallSearchCache['kategorie']==$kateg[0])?(' selected="selected"'):('')).'>'.$kateg[1].'</option>';
$tpl->setVariable('CATEGORYOPTIONS',$categoryOptions);
$tpl->setVariable('FILTRTEXT',$sessGallSearchCache['filtr']);
$tpl->setVariable('PERPAGE',$sessGallSearchCache['perpage']);


$galGood = unserialize($sessGallSearchCache['data']);

if(!empty($galGood)) {
	//--pagination
	$celkem = count($galGood);
	$pager = fSystem::initPager($celkem,$sessGallSearchCache['perpage'],array('itemData'=>$galGood));

	$arrgal = $pager->getPageData();
	//--sql query
	$od=($pager->getCurrentPageID()-1) * $sessGallSearchCache['perpage'];
	
	//--printing
//	$tpl->setVariable('FROM',$od+1);
//	$tpl->setVariable('TO',$od+$sessGallSearchCache['perpage']);
	$tpl->setVariable('TOTAL',$celkem);
	if($celkem > $sessGallSearchCache['perpage']) {
	    $tpl->setVariable('PAGER',$pager->links);
	}
	$bck=true;
	foreach ($arrgal as $row){
	    $tpl->setCurrentBlock('result');
	    if($bck) { $tpl->setVariable('DUMMYODDCLASS',' '); $bck=false;} else {$bck=true;}
	    $tpl->setVariable('RNAME',$row[1]);
	    $tpl->setVariable('RLINK','?k='.$row[0]);
	    $tpl->setVariable('RDATE',$row[4]);
	    $tpl->setVariable('OWNERLINK','?k=33&who='.$row[2]);
	    $tpl->setVariable('OWNERNAME',$row[3]);
	    $tpl->parseCurrentBlock();
	}
} else {
	$tpl->setVariable('DUMMYEMPTY',' ');
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>