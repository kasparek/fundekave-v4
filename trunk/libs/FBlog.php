<?php
class FBlog extends FDBTool  {
    var $typeId = 'blog';
	var $perPage;
	
	function __construct() {
        parent::__construct('sys_pages_items as i','i.itemId');
		$this->perPage = BLOG_PERPAGE;
	}
	
  function process($aFormValues) {
	    $user = FUser::getInstance();
	    $returnItemId = 0;
	    $pageId = $aFormValues['pageid'];
    	if(FRules::get($user->userVO->userId,$pageId,2) === true) {
          	$fsave = new FDBTool('sys_pages_items','itemId');
          	if(!isset($aFormValues['del'])) $aFormValues['del'] = 0; 
          	if($aFormValues['del'] == 0) {
          		$arrSave = array('addon'=>FSystem::textins($aFormValues['nadpis'],array('plainText'=>1)),'text'=>FSystem::textins($aFormValues['textclanku'])); 
          		$arrSave['name'] = FSystem::textins($aFormValues['autor'],array('plainText'=>1));
          		if($arrSave['name']=='') $arrSave['name'] = $user->userVO->name;
          		$aFormValues['datum'] = FSystem::switchDate($aFormValues['datum']);
          		if(FSystem::isDate($aFormValues['datum'])) $arrSave['dateCreated'] = FSystem::textins($aFormValues['datum'],array('plainText'=>1));
          		if(isset($aFormValues['nid'])) $itemId = (int) $aFormValues['nid'];
          		else $itemId = 0;
          		
          		if(isset($aFormValues['category'])) $arrSave['categoryId'] = (int) $aFormValues['category'];
          		
          		$arrSave['public'] = 0;
          		if($aFormValues['public'] == 1) $arrSave['public'] = 1;
          		
          		if($itemId>0) {
          		    $arrSave['itemId'] = $aFormValues['nid']*1;
          		} else {
          		    $arrSave['userId'] = $user->userVO->userId;
          		    $arrSave['pageId'] = $pageId;
          		    $arrSave['typeId'] = $this->typeId;
          		    FPages::cntSet($pageId);
          		}
          		//$fsave->debug=1;
          		$returnItemId = $fsave->save($arrSave);
          		
          		///properties
          		FItems::setProperty($returnItemId,'forumSet',(int) $aFormValues['forumset']);
          		
          		fUserDraft::clear(fBlog::textAreaId());
          	} else {
          	  $fItems = new FItems();
          	  $fItems->deleteItem($aFormValues['nid']*1);
          	  FPages::cntSet($pageId,false);
          	  $returnItemId = 0;
          	}
          	$cache = FCache::getInstance('f');
          	$cache->invalideteGroup('lastBlogPost');
          	
    	} else {
        //---DO AJAX ERROR - cant save data - no rules
        //echo 'error::rules';
      }
      return $returnItemId;
	}
	static function textAreaId() {
	    $user = FUser::getInstance();
	    return 'Blog'.$user->pageVO->pageId;
	}
	function getEditForm($itemId) {
	    $user = FUser::getInstance();
	    
	    $textAreaId = fBlog::textAreaId();
	    
	    $tpl = new fTemplateIT('blog.editform.tpl.html');
        $tpl->setVariable('PAGEID',$user->pageVO->pageId);
        if($itemId>0) {
            $this->setSelect("text, date_format(dateCreated,'%d.%m.%Y'), name, addon, public, categoryId");
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
        		///properties
        		$tpl->touchBlock('fforum'.FItems::getProperty($itemId,'forumSet',FPages::getProperty($user->pageVO->pageId,'forumSet',2)));
        		///categories
        		if($opt = FSystem::getOptions($user->pageVO->pageId,$arr[5],true,''))
        		  $tpl->setVariable('CATEGORYOPTIONS',$opt);
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
	    $user = FUser::getInstance();
	    $itemId = (int) $itemId;
		$tpl = new fTemplateIT('blog.list.tpl.html');
		if($user->idkontrol) $tpl->touchBlock('logged');
		
		//--edit mode
		if($editMode === true) {
		    if(FRules::get($user->userVO->userId,$user->pageVO->pageId,2)) {
		        $tpl->setVariable('EDITFORM',$this->getEditForm($itemId));
		    }
		}
		
		$currentPage = 0;
		if(empty($itemId)) {
    		if($user->pageVO->cnt > $this->perPage) {
    			$pager = FSystem::initPager($user->pageVO->cnt,$this->perPage);
    			$tpl->setVariable('BOTTOMPAGER',$pager->links);
    			$currentPage = $pager->getCurrentPageID()-1;
    		}
		}
		
		if(!empty($user->pageVO->content)) $tpl->setVariable('CONTENT',$user->pageVO->content);
		
		if($itemId > 0) {
		
			$itemVO = new ItemVO($itemId,true,array('type'=>'blog','showComments'=>true));
		    return $itemVO->render();
			    
		} else {
		
			$itemRenderer = new FItemsRenderer();
				
			$fItems = new FItems('blog',false,$itemRenderer);
			$fItems->initData('blog');
			$fItems->addWhere("i.pageId='".$user->pageVO->pageId."'");
			$fItems->addWhere('i.itemIdTop is null');
			
		    $fItems->setLimit($currentPage * $this->perPage, $this->perPage);
		    $fItems->setOrder("i.dateCreated desc");
			$fItems->getData();
			if(!empty($fItems->data)){
				while($fItems->arrdata) $fItems->parse();
				FForum::aFav($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->cnt);
	            $tpl->setVariable('ITEMS',$fItems->show());
	            if($itemId>0) $user->pageVO->name = $fItems->currentHeader;
	        }
	        return $tpl->get();    
		}
			
	}
}