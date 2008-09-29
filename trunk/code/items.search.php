<?php
$typeId = $user->currentPage['typeIdChild'];

fItems::setTagToolbarDefaults(array('enabled'=>1,'search'=>1,'perpage'=>SEARCH_PERPAGE));

$itemsSearchCache = & $user->itemsSearch;
if(empty($itemsSearchCache)) $itemsSearchCache = array('perpage'=>SEARCH_PERPAGE,'filtrStr'=>'','action'=>1,'data'=>array(),'maybemore'=>false);

if(!empty($_REQUEST["perpage"])) $perpage = (int) $_REQUEST["perpage"];
elseif(empty($perpage)) $perpage = SEARCH_PERPAGE;
if($perpage<3 || $perpage>100) $perpage = SEARCH_PERPAGE;

if(isset($_POST['subsearch']) && !empty($_REQUEST["filtr"])) {
	
	if(strlen($_POST["filtr"])<2) {
		fError::addError(ERROR_SEARCH_TOSHORT);
		fHTTP::redirect($user->getUri());
	} else {
		$search_text = trim($_POST["filtr"]);
		$search_in = $_POST['svcem'];
	}
	
	
	$fItems = new fItems();
	$fItems->showPageLabel = true;
	$fItems->initData($typeId,$user->gid,true);
	
	if($search_in==0 || $search_in==2) {
	    $arrWhereSearch[]='i.text';
	    $arrWhereSearch[]='i.enclosure';
	}
	if($search_in==0 || $search_in==1) {
	    $arrWhereSearch[]='i.name';
	}
	if($search_in==0 || $search_in==3) {
	    $arrWhereSearch[]='i.dateCreated';
	}
	$fItems->addWhereSearch($arrWhereSearch,$search_text,'OR','AND');
	
	$limit = 100;
	
	$fItems->setOrder("i.dateCreated",true);
	$fItems->setLimit(0,$limit);
		
	$total = $fItems->getCount();
	
	$fItems->getData();
	
	if($total > $limit) fError::addError(LABEL_FOUND.' '.$total.'. '.LABEL_SEARCH_RESULTS_JUSTLISTED.' '.$limit.'.');
	
	$_SESSION['search_audit_data'] = array('priz'=>$fItems->arrData,'count'=>count($fItems->arrData));
	
	fHTTP::redirect($user->getUri());
}
//---show part
$tpl = new fTemplateIT('items.search.tpl.html');
$tpl->setVariable('FORMACTION',$user->getUri());
$options='';
foreach ($ARRWHERESEARCHLABELS as $k=>$v)
	$options.='<option value="'.$k.'"'.(($search_in==$k)?(' selected="selected"'):('')).'>'.$v.'</option>';
$tpl->setVariable('WHEREOPTIONS',$options);
$tpl->setVariable('SEARCHTEXT',$search_text);
$tpl->setVariable('PERPAGE',$perpage);

if($search_text != "") {
	$newdata = $_SESSION['search_audit_data'];
	$celkem = $newdata['count'];
	if($celkem > 0) {
		//--listovani
		$pager = fSystem::initPager(0,$perpage,array('itemData'=>$newdata['priz']));
		$od = ($pager->getCurrentPageID()-1) * $perpage;
		$arr = $pager->getPageData();
		if($celkem > $perpage) {
			$tpl->setVariable('TOPPAGER',$pager->links);
			$tpl->setVariable('BOTTOMPAGER',$pager->links);
		}
		$tpl->setVariable('FROM',$od);
				
		$fItems = new fItems();
		$fItems->showPageLabel = true;
		$fItems->arrData = $arr;
		
		while($fItems->arrData) $fItems->parse();
		$tpl->setVariable('RESULTS',$fItems->show());
	} else $tpl->touchBlock('noresults');
} else $tpl->touchBlock('nosearchconditions');

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));