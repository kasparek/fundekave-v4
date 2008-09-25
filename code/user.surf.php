<?php
if(isset($_REQUEST["sc"])) $kat = $_REQUEST["sc"]*1; else $kat=0;
if(isset($_REQUEST["sm"])) $showAll = $_REQUEST["sm"]*1; else $showAll=0;

//write new link
if(isset($_POST["insert"]) && $user->idkontrol) {
    $url = trim($_POST["surflink"]);
	if($url=='') fError::addError(ERROR_SURF_URL);
	else {
		$sLinx = new fSqlSaveTool('sys_surfinie','surfId');
		$sLinx->addCol('userId',$user->gid);
		$sLinx->addCol('url',fSystem::textins($url,0,0));
		$sLinx->addCol('name',fSystem::textins($_POST["surfdesc"],0,0));
		$sLinx->addCol('public',($_POST["surfpublic"]*1));
		$sLinx->addCol('categoryId',($_POST['selcat']*1));
		$sLinx->addCol('dateCreated','NOW()',false);
		$dot = $sLinx->buildInsert();
		$db->query($dot);
		fHTTP::redirect($user->getUri('sc='.$kat));
	}
	
}
if(isset($_GET['d'])) {
    $deleteId = $_GET['d']*1;
    $doDelete = false;
    if($deleteId>0) {
        if(fRules::get($user->gid,$user->currentPageId,2)) $doDelete = true;
        elseif($db->getOne("select userId from sys_surfinie where surfId='".$deleteId."'")==$user->gid) $doDelete = true;
    }
    if($doDelete===true) {
	   $db->query('delete from sys_surfinie where surfId= "'.$deleteId.'"');
	   fHTTP::redirect($user->getUri());
    }
}
$tpl = new fTemplateIT("user.surf.tpl.html");
$tpl->setVariable('FORMACTION',$user->getUri());
$tpl->setVariable('SELECTEDCATEGORY',$kat);

$options = '';
$arr = $db->getAll("select categoryId,name from sys_pages_category where typeId='surf' order by name");
foreach ($arr as $row) {
    $options .= '<option value="'.$row[0].'"'.(($row[0]==$kat)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
    if($row[0]==$kat) $tpl->setVariable('CATEGORYNAME',$row[1]);
}
$tpl->setVariable('CATOPTIONS',$options);
if($showAll==1) $tpl->touchBlock('showall');

$qLinx = new fQueryTool('sys_surfinie','surfId');
$qLinx->setSelect('surfId,userId,url,name');
$qLinx->setWhere("categoryId='".$kat."' and (userId='".$user->gid."'".(($showAll==1)?(' or public=1'):('')).")");
$qLinx->setOrder('dateCreated desc');

$total = $qLinx->getCount();

if($total>0) {
    
    $od = 1;
    if($total>DEFAULT_PERPAGE) {
        $pager = fSystem::initPager($total,DEFAULT_PERPAGE,array('extraVars('array('sc'=>$kat,'sm'=>$showAll)));
        $od = ($pager->getCurrentPageID()-1) * DEFAULT_PERPAGE;
        $tpl->setVariable('TOPPAGER',$pager->links);
        $tpl->setVariable('BOTTOMPAGER',$pager->links);
        
        $tpl->setVariable('TOTAL',$total);
        $tpl->setVariable('FROM',$od);
        $tpl->setVariable('TO',$od+DEFAULT_PERPAGE);
    }
    
    
	$qLinx->setLimit($od,DEFAULT_PERPAGE);
	$arr = $qLinx->getContent();
	
	foreach ($arr as $row){
	    $tpl->setCurrentBlock('result');
	    if($user->gid==$row[1] || fRules::get($user->gid,$user->currentPageId,2)) {
	       $tpl->setVariable('DELETELINK',$user->getUri('d='.$row[0]));   
	    }
	    if($user->gid!=$row[1]) {
	        $tpl->setVariable('AUTHORLINK','?k=finfo&who='.$row[1]);
	        $tpl->setVariable('AUTHORNAME',$user->getgidname($row[1]));
	    }
	    $tpl->setVariable('DESCRIPTION',$row[3]);
	    
	    $tpl->setVariable('LINKPRINT',((strlen($row[2]>30)?(substr($row[2],0,30).'...'):($row[2]))));
	    $tpl->setVariable('LINKTITLE',$row[2]);
	    $tpl->setVariable('LINKURL',$row[2]);
	    $tpl->parseCurrentBlock();
	    
	}
}

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));

?>