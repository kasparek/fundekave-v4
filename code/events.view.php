<?php
if($user->currentPageParam == 'archiv') $archiv = 1;

if($user->currentPageParam=='archiv' || $user->currentPageParam=='a' || $user->currentPageParam=='e') {
  fSystem::secondaryMenuAddItem($user->getUri('','event',''),BUTTON_PAGE_BACK);
} else {
  fSystem::secondaryMenuAddItem($user->getUri('','eventarchiv'),LABEL_EVENTS_ARCHIV);
}

if($user->currentPageParam=='u') {
    require('events.edit.php');
} else {

    $fItems = new fItems();
    $fItems->initData('event',false,true);
    
    $adruh = 0;
    $filtr = '';

    if($user->currentItemId>0) {
        
        $fItems->showComments = true;
        $fItems->initDetail($user->currentItemId);
        
    } else {
        if(isset($_REQUEST['adruh'])) $adruh = (int) $_REQUEST['adruh'];
        if(isset($_REQUEST['filtr'])) $filtr = trim($_REQUEST['filtr']); 
        if($adruh>0) $fItems->addWhere('i.categoryId="'.$adruh.'"');
        if(!empty($filtr)) $fItems->addWhereSearch(array('i.location','i.addon','i.text'),$filtr,'or');
        
        if(!isset($archiv)) {
            $fItems->addWhere("i.dateStart >= date_format(NOW(),'%Y-%m-%d')");
            $fItems->setOrder('i.dateStart');
        } else {
            $fItems->addWhere("i.dateStart < date_format(NOW(),'%Y-%m-%d')");
            $fItems->setOrder('i.dateStart desc');
        }
    }
    
    //--listovani
    $celkem = $fItems->getCount();
    $perPage = $conf['events']['perpage'];
    $tpl = new fTemplateIT('events.tpl.html');
    if($celkem > 0) {
        if($celkem > $perPage) {
           $pager = fSystem::initPager($celkem,$perPage,array('extraVars'=>array('adruh'=>$adruh,'filtr'=>$filtr)));
    	   $od = ($pager->getCurrentPageID()-1) * $perPage;    
        } else $od=0;
           
    	$fItems->getData($od,$perPage);
    
    	if($user->currentItemId == 0) {
            $arrOpt = $db->getAll('select categoryId,name from sys_pages_category where typeId="event" order by ord,name');
            $options = '';
            if(!empty($arrOpt)) foreach ($arrOpt as $row) {
            	$options .= '<option value="'.$row[0].'"'.(($row[0]==$adruh)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
            }
            $tpl->setVariable('CATEGORYOPTIONS',$options);
            $tpl->setVariable('FILTRVALUE',$filtr);
    	
          	if($celkem > $perPage) {
          	   $tpl->setVariable('LISTTOTAL',$celkem);
          	   $tpl->setVariable('PAGER',$pager->links);
          	}
    	} else {
    	    $fItems->showHeading = false;
    	}
    	//---items parsing
    	while ($fItems->arrData) {
    		$fItems->parse();
    	}
    	
    	if($user->currentItemId > 0) $user->currentPage['name'] = $fItems->currentHeader;
    	
    	$tpl->setVariable('ITEMS',$fItems->show());
    } else {
    	$tpl->touchBlock('notanyevents');
    }
    $TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
}