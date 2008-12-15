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
	    $returnItemId = 0;
	    $pageId = $aFormValues['pageid'];
    	if(fRules::get($user->gid,$pageId,2) === true) {
          	$fsave = new fSqlSaveTool('sys_pages_items','itemId');
          	if(!isset($aFormValues['del'])) $aFormValues['del'] = 0; 
          	if($aFormValues['del'] == 0) {
          		$arrSave = array('addon'=>fSystem::textins($aFormValues['nadpis'],array('plainText'=>1)),'text'=>fSystem::textins($aFormValues['textclanku'])); 
          		$arrSave['name'] = fSystem::textins($aFormValues['autor'],array('plainText'=>1));
          		if($arrSave['name']=='') $arrSave['name'] = $user->gidname;
          		$aFormValues['datum'] = fSystem::switchDate($aFormValues['datum']);
          		if(fSystem::isDate($aFormValues['datum'])) $arrSave['dateCreated'] = fSystem::textins($aFormValues['datum'],array('plainText'=>1));
          		if(isset($aFormValues['nid'])) $itemId = (int) $aFormValues['nid'];
          		else $itemId = 0;
          		
          		$arrSave['public'] = 0;
          		if($aFormValues['public'] == 1) $arrSave['public'] = 1;
          		
          		if($itemId>0) {
          		    $arrSave['itemId'] = $aFormValues['nid']*1;
          		} else {
          		    $arrSave['userId'] = $user->gid;
          		    $arrSave['pageId'] = $pageId;
          		    $arrSave['typeId'] = $this->typeId;
          		}
          		//$fsave->debug=1;
          		$returnItemId = $fsave->save($arrSave);
          		
          		fPages::cntSet($pageId);
          		fUserDraft::clear(fBlog::textAreaId());
          	} else {
          	  $fItems = new fItems();
          	  $fItems->deleteItem($aFormValues['nid']*1);
          	  fPages::cntSet($pageId,false);
          	  $returnItemId = 0;
          	}
          	$user->cacheRemove('lastBlogPost');
    	} else {
        //---DO AJAX ERROR - cant save data - no rules
        //echo 'error::rules';
      }
      return $returnItemId;
	}
	static function textAreaId() {
	    global $user;
	    return 'Blog'.$user->currentPageId;
	}
	function getEditForm($itemId) {
	    global $user;
	    
	    $textAreaId = fBlog::textAreaId();
	    
	    $tpl = new fTemplateIT('blog.editform.tpl.html');
        $tpl->setVariable('PAGEID',$user->currentPageId);
        if($itemId>0) {
            $this->setSelect("text, date_format(dateCreated,'%d.%m.%Y'), name, addon, public");
            $this->setWhere("itemId = '".$itemId."'");
            $arrTmp = $this->getContent();
        	$arr = $arrTmp[0];
        	
        	if(!empty($arr)) {
        		$tpl->setVariable('EDITADDON',$arr[3]);
        		$tpl->setVariable('EDITDATE',$arr[1]);	
        		$tpl->setVariable('EDITTEXT',$arr[0]);	
        		$tpl->setVariable('EDITAUTOR',$arr[2]);	
        		$tpl->touchBlock('newdelete');
        		$tpl->setVariable('EDITID',$itemId);
        		if($arr[4] == 0) {
        		    $tpl->touchBlock('classnotpublic');
        		    $tpl->touchBlock('headernotpublic');
        		} else {
        		    $tpl->touchBlock('statpublic');
        		}
        	}
        } else {
        	$tpl->setVariable('EDITDATE',Date("d.m.Y"));
        	if($draft = fUserDraft::get($textAreaId)) $tpl->setVariable('EDITTEXT',$draft);	
        }
        
        $tpl->setVariable('TEXTID',$textAreaId);
        $tpl->addTextareaToolbox('TEXTTOOLBOX',$textAreaId);
        //---have to be called js functions: draftSetEventListeners, initInsertToTextarea
        
        return $tpl->get();
	}
	function listAll($itemId = 0,$editMode = false) {
	    global $user;
	    $itemId = (int) $itemId;
		$tpl = new fTemplateIT('blog.list.tpl.html');
		if($user->idkontrol) $tpl->touchBlock('logged');
		
		//--edit mode
		if($editMode === true) {
		    if(fRules::get($user->gid,$user->currentPageId,2)) {
		        $tpl->setVariable('EDITFORM',$this->getEditForm($itemId));
		    }
		}
		
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
		$fItems->addWhere("i.pageId='".$user->currentPageId."'");
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