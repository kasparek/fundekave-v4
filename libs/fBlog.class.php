<?php
class fBlog extends fQueryTool  {
    var $typeId = 'blog';
	var $perPage;
	
	function __construct($db) {
        parent::__construct('sys_pages_items as i','i.itemId',$db);
		$this->perPage = BLOG_PERPAGE;
	}
	function process($aFormValues) {
	    global $user;
	    $pageId = $aFormValues['pageid'];
    	if(fRules::get($user->gid,$pageId,2)==true) {
          	$fsave = new fSqlSaveTool('sys_pages_items','itemId');
          	if(!isset($aFormValues['del'])) $aFormValues['del'] = 0; 
          	if($aFormValues['del'] == 0) {
          		$arrSave = array('addon'=>fSystem::textins($aFormValues['nadpis'],0,0),'text'=>fSystem::textins($aFormValues['textclanku'])); 
          		$arrSave['name'] = fSystem::textins($aFormValues['autor'],0,0);
          		if($arrSave['name']=='') $arrSave['name'] = $user->gidname;
          		if(fSystem::isDate($aFormValues['datum'])) $arrSave['dateCreated'] = fSystem::textins($aFormValues['datum'],0,0);
          		if(isset($aFormValues['nid'])) $itemId = (int) $aFormValues['nid'];
          		else $itemId = 0;
          		if($itemId>0) {
          		    $arrSave['itemId'] = $aFormValues['nid']*1;
          		} else {
          		    $arrSave['userId'] = $user->gid;
          		    $arrSave['pageId'] = $pageId;
          		    $arrSave['typeId'] = $this->typeId;
          		}
          		//$fsave->debug=1;
          		$fsave->save($arrSave);
          		
          		fPages::cntSet($pageId);
          		fUserDraft::clear('b'.$pageId);
          	} else {
          	  $fItems = new fItems();
          	  $fItems->deleteItem($itemId);
          	  fPages::cntSet($pageId,false);
          	}
          	$user->cacheRemove('lastBlogPost');
    	} else {
        //---DO AJAX ERROR - cant save data - no rules
        //echo 'error::rules';
      }
	}
	function getEditForm($itemId) {
	    global $user;
	    $tpl = new fTemplateIT('blog.editform.tpl.html');
        $tpl->setVariable('PAGEID',$user->currentPageId);
        if($itemId>0) {
            $this->setSelect("text,date_format(dateCreated,'%Y-%m-%d'),name,addon");
            $this->setWhere("itemId='".$itemId."'");
            $arrTmp = $this->getContent();
        	$arr = $arrTmp[0];
        	
        	if(!empty($arr)) {
        		$tpl->setVariable('EDITADDON',$arr[3]);
        		$tpl->setVariable('EDITDATE',$arr[1]);	
        		$tpl->setVariable('EDITTEXT',$arr[0]);	
        		$tpl->setVariable('EDITAUTOR',$arr[2]);	
        		$tpl->setVariable('DELETEFAKE',' ');
        		$tpl->setVariable('EDITID',$itemId);
        	}
        } else {
        	$tpl->setVariable('EDITDATE',Date("Y-m-d"));
        	if($draft = fUserDraft::get('b'.$user->currentPageId)) $tpl->setVariable('EDITTEXT',$draft);	
        }
        $tpl->setVariable('TACTION',fUserDraft::getAction());
        return $tpl->get();
	}
	function listAll($itemId=0) {
	    global $user;
	    $itemId = (int) $itemId;
		$tpl = new fTemplateIT('blog.list.tpl.html');
		if($user->idkontrol) $tpl->touchBlock('logged');
		$currentPage = 0;
		if(empty($itemId)) {
    		if($user->currentPage['cnt'] > $this->perPage) {
    			$pager = fSystem::initPager($user->currentPage['cnt'],$this->perPage);
    			$tpl->setVariable('BOTTOMPAGER',$pager->links);
    			$currentPage = $pager->getCurrentPageID()-1;
    		}
		}
		if(!empty($user->currentPage['content'])) $tpl->setVariable('CONTENT',$user->currentPage['content']);
		
		$fItems = new fItems();  
        $fItems->initData('blog');
		$fItems->setWhere("i.pageId='".$user->currentPageId."'");
		$fItems->addWhere('i.itemIdTop is null');
		if($itemId>0) {
		    $fItems->showComments = true;
		    $fItems->showHeading = false;
		    $fItems->initDetail($itemId);
		} else {
		    $fItems->setLimit($currentPage*$this->perPage,$this->perPage);
		    $fItems->setOrder("i.dateCreated desc");
		}
		$fItems->getData();
		if(!empty($fItems->arrData)){
		    if($user->idkontrol) fItems::initTagXajax();
			while($fItems->arrData) $fItems->parse();
			fForum::aFav($user->currentPageId,$user->gid,$user->currentPage['cnt']);
            $tpl->setVariable('ITEMS',$fItems->show());
            if($itemId>0) $user->currentPage['name'] = $fItems->currentHeader;
        }
		return $tpl->get();
	}
}