<?php
$typeIdChild = $user->currentPage['typeIdChild'];
if(fRules::get($user->gid,$user->currentPageId,2)) {
    fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId.'a'),LABEL_ADD);
}
if($user->currentPageParam == 'a') {
	require('page.edit.php');
} else {
    
    if(isset($_REQUEST["kat"])) {
        $katTmp = (int) $_REQUEST["kat"];
        if($katTmp>0) $kat = $katTmp;
    }
    
    $categoryName = '';
    
    $fPages = new fPages($typeIdChild,$user->gid,$db);
    if(!empty($kat)) {
        $fPages->addWhere('categoryId="'.$kat.'"');
        $categoryName = $db->getOne('select name from sys_pages_category where categoryId="'.$kat.'"');
    }
    $total = $fPages->getCount();
    $tpl = new fTemplateIT('page.list.tpl.html');
    
    if($total > 0) {
        
        if($total>DEFAULT_PERPAGE) {
            
            $pager = fSystem::initPager($total,DEFAULT_PERPAGE);
    			
    		$od=($pager->getCurrentPageID()-1) * DEFAULT_PERPAGE;
    		$do=$od+DEFAULT_PERPAGE;
    		
//    		$tpl->setVariable('FROM',$od);
//    		$tpl->setVariable('TO',$do);
//    		$tpl->setVariable('TOTAL',$total);
//    		$tpl->setVariable('TOPPAGER',$pager->links);
    		$tpl->setVariable('PAGER',$pager->links);
        }
    		
    		/* ..... list clanky ....*/
    		$fPages->setSelect("p.pageId,p.name,p.description,date_format(p.dateCreated,'%d.%m.%Y') as datumcz,p.userIdOwner,
    		p.authorContent");
    		$fPages->setOrder("dateCreated",true);
    		if($total > DEFAULT_PERPAGE) $fPages->setLimit($od,DEFAULT_PERPAGE);
    		$arr=$fPages->getContent();
    		
    		foreach ($arr as $r){
    		    
    		    $tpl->setCurrentBlock('result');
    		    $tpl->setVariable('PAGELINK','?k='.$r[0].((!empty($kat))?('&kat='.$kat):('')).(($pager)?('&'.$conf['pager']['urlVar'].'='.$pager->getCurrentPageID()):('')));
    		    $tpl->setVariable('PAGENAME',$r[1]);
    		    if(!empty($r[2])) $tpl->setVariable('DESCRIPTION',$r[1]);
    		    $tpl->setVariable('PAGEDATE',$r[3]);
    		    $tpl->setVariable('PAGEAUTOR',$r[5]);
    		    $tpl->setVariable("LINKAUTOR",$user->showAvatar($r[4]).'<br /><a href="?k=finfo&who='.$r[4].'">'.$user->getgidname($r[4]).'</a>');
    		    $tpl->parseCurrentBlock();
    		}
    		
    } else {
    	$tpl->setVariable('DUMMYNORESULTS',' ');
    }
    	
    $TOPTPL->addTab(array("MAINHEAD"=>$categoryName,"MAINDATA"=>$tpl->get()));
}
?>