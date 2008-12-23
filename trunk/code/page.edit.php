<?php
if($user->currentPageId=='galed' || $user->currentPageId=='paged') {
   $user->currentPageParam = 'a' ;
}

$rules = new fRules((($user->currentPageParam != 'a')?($user->currentPageId):('')),$user->currentPage['userIdOwner']);
$fRelations = new fPagesRelations($user->currentPageId);

$textareaIdDescription = 'desc'.$user->currentPageId;
$textareaIdContent =  'cont'.$user->currentPageId;
$textareaIdForumHome = 'home'.$user->currentPageId;

/**
 * $user->currentPageParam == a - add from defaults, e - edit from user->currentPage
 */

$typeForSaveTool = $user->currentPage['typeId'];
if($user->currentPageParam=='a') $typeForSaveTool = $user->currentPage['typeIdChild'];  

$deleteThumbs = false;
if($typeForSaveTool == 'galery') {
  $galery = new fGalery();
}

$sPage = new fPagesSaveTool($typeForSaveTool);  
if($user->currentPageParam!='a')$sPage->xmlProperties = $user->currentPage['pageParams'];

if(empty($sPage->xmlProperties)) {
    $sPage->setXmlPropertiesDefaults();
}

if($typeForSaveTool=='blog' && $user->currentPageParam!='a') {
    $category = new fCategory('sys_pages_category','categoryId');
    $category->addWhere("typeId='".$user->currentPageId."'");
    $category->arrSaveAddon = array('typeId'=>$user->currentPageId);
    $category->process();
}

$fLeft = new fLeftPanel($user->currentPageId,0,$user->currentPage['typeId']);

if(isset($_POST["save"])) {
    
    $fLeft->process($_POST['leftpanel']);
  
  $notQuoted = array();
	$arr['name'] = fSystem::textins($_POST['name'],array('plainText'=>1));
	if(empty($arr['name'])) fError::addError(ERROR_PAGE_ADD_NONAME);
	$arr['description']=fSystem::textins($_POST['description'],array('plainText'=>1));
	$arr['content']=fSystem::textins($_POST['content']);
   
	if($typeForSaveTool == 'galery') {
	
  	$arr['galeryDir'] = Trim($_POST['galeryDir']);
  	if($arr['galeryDir']=='') fError::addError(ERROR_GALERY_DIREMPTY);
  	elseif (!fSystem::checkDirname($arr['galeryDir'])) fError::addError(ERROR_GALERY_DIRWRONG);
  	elseif($user->currentPageParam=='e' && $user->currentPage['galeryDir'] != $arr['galeryDir']) {
  	    $deleteThumbs = true;
  	}
	
  	if(($xperpage = $_POST['xperpage']*1) < 1) $xperpage = $galery->get('thumbNumWidth');
	 if(($xwidthpx = $_POST['xwidthpx']*1) < 10) $xwidthpx = $galery->get('widthThumb');
	 if(($xheightpx = $_POST['xheightpx']*1) < 10) $xheightpx = $galery->get('heightThumb');
	
    $sPage->setXMLVal('enhancedsettings','perpage',$xperpage);
    $sPage->setXMLVal('enhancedsettings','widthpx',$xwidthpx);
    $sPage->setXMLVal('enhancedsettings','heightpx',$xheightpx);
    $sPage->setXMLVal('enhancedsettings','thumbnailstyle',(int) $_POST['xthumbstyle']);
    if(isset($_POST['galeryorder'])) $sPage->setXMLVal('enhancedsettings','orderitems',(int) $_POST['galeryorder']);
    if(isset($_POST['forumReact'])) $sPage->setXMLVal('enhancedsettings','fotoforum',(int) $_POST['forumReact']);
    
    if($user->currentPage['pageParams'] != $sPage->xmlProperties && $user->currentPageParam=='e') {
	    $deleteThumbs = true;
	 }
  } 
  
  if(isset($_POST['datecontent'])) {
      $dateContent = fSystem::switchDate($_POST['datecontent']);
	    if(!empty($dateContent)) if(fSystem::isDate($dateContent)) $arr['dateContent'] = $dateContent;
  }
  
	if($user->currentPageParam=='sa') {
	    $arr['nameShort'] = fSystem::textins($_POST['nameshort'],array('plainText'=>1));
	    $arr['authorContent'] = fSystem::textins($_POST['authorcontent'],array('plainText'=>1));
	    $arr['template'] = fSystem::textins($_POST['template'],array('plainText'=>1));
	        
	    
	    if($user->currentPageParam=='a') $arr['locked'] = 'null';
	    if(isset($_POST['locked'])) {
	     $locked = $_POST['locked'];
	     if($locked>0) {
	       $notQuoted[] = 'locked';
	       $arr['locked'] = $locked * 1;
	     }
	    }
	    
	    if($user->currentPageParam=='a') $arr['categoryId'] = 'null';
	    if(isset($_POST['category'])) {
  	    $cat = $_POST['category'];
  	    $notQuoted[] = 'categoryId';
  	    if($cat>0) {
  	      $arr['categoryId'] = $cat * 1;
  	    } else {
  	      $arr['categoryId'] = 'null';
  	    }
	    }
	    
	    if($user->currentPageParam=='a') $arr['menuSecondaryGroup'] = 'null';
	    if(isset($_POST['menusec'])) {
  	    $menusec = $_POST['menusec'];
  	    $notQuoted[] = 'menuSecondaryGroup';
  	    if($menusec>0) {
  	      $arr['menuSecondaryGroup'] = $menusec * 1;
  	    } else {
  	      $arr['menuSecondaryGroup'] = 'null';
  	    }
	    }
	    	    
	}
	
	if(isset($_POST['forumhome'])) {
	 $sPage->setXMLVal('home',fSystem::textins($_POST['forumhome']));
	}

	if(!fError::isError()) {
		
    if($typeForSaveTool=='galery') {
    	    $adr = $galery->get('rootImg').$arr['galeryDir'];
    		if(!file_exists($adr)) {
    			if(mkdir ($adr, 0777)) {
    				mkdir ($adr."/nahled", 0777);
    				chmod ( $adr, 0777 );
    				chmod ( $adr.'/nahled', 0777 );
    			}
    		}
	    }
    		
		if($user->currentPageParam == 'a') {
			$arr['userIdOwner'] = $user->gid;
			$user->cacheRemove('calendarlefthand');
		} else {
		  $arr['pageId'] = $user->currentPageId;
		}
	    if(!empty($_POST['audicourl'])) {
	        $filename = 'pageAvatar-'.$user->currentPageId.'.jpg';
	        if($file = @file_get_contents($_POST['audicourl'])) {
	            file_put_contents(WEB_REL_PAGE_AVATAR.$filename,$file);
	            $resizeParams = array('quality'=>80,'crop'=>1,'width'=>PAGE_AVATAR_WIDTH_PX,'height'=>PAGE_AVATAR_HEIGHT_PX);
                $iProc = new fImgProcess(WEB_REL_PAGE_AVATAR.$filename,WEB_REL_PAGE_AVATAR.$filename,$resizeParams);
	        }
	        $arr["pageIco"] = $filename;
	        
	    }
	    
	    if($deleteThumbs===true && $typeForSaveTool=='galery') {
    	  $galery->getGaleryData($user->currentPageId);
    	  $cachePath = ROOT.ROOT_WEB.$galery->getThumbCachePath();
    		fSystem::rm_recursive($cachePath);
    		$systemCachePath = ROOT.ROOT_WEB.$galery->getThumbCachePath($galery->_cacheDirSystemResolution);
    		fSystem::rm_recursive($systemCachePath);
    		
    		
	    }
	    
	    
        if ($_FILES["audico"]['error']==0) {
            $konc = Explode(".",$_FILES["audico"]["name"]);
			$_FILES["audico"]['name'] = "icoaudit".$user->currentPageId.'.'.$konc[(count($konc)-1)];
			if($up = fSystem::upload($_FILES["audico"],WEB_REL_PAGE_AVATAR,200000)) {
                //---resize and crop if needed
                list($width,$height,$type) = getimagesize(WEB_REL_PAGE_AVATAR.$up['name']);
                if($width!=PAGE_AVATAR_WIDTH_PX || $height!=PAGE_AVATAR_HEIGHT_PX) {
                    if($type!=2) $up['name'] = str_replace($konc[(count($konc)-1)],'jpg',$up['name']);
                    //---RESIZE
                    $resizeParams = array('quality'=>80,'crop'=>1,'width'=>PAGE_AVATAR_WIDTH_PX,'height'=>PAGE_AVATAR_HEIGHT_PX);
                    $iProc = new fImgProcess(WEB_REL_PAGE_AVATAR.$_FILES["audico"]['name'],WEB_REL_PAGE_AVATAR.$up['name'],$resizeParams);
                }
                $arr["pageIco"] = $up['name'];
			}
		}
		if(isset($_POST['delpic'])) $arr['pageIco'] = '';
  
  //$sPage->debug = 1;
    $arr['pageParams'] = $sPage->xmlProperties;

		$nid = $sPage->savePage($arr,$notQuoted);

		$user->cacheRemove('forumdesc');
		
		if($user->currentPageParam == 'a') {
		  $rules->setPageId($nid);
		  $fRelations->setPageId($nid);
		}
		//---rules,relations update	
		$rules->public = $_POST['public'];
		$rules->ruleText = $_POST['rule'];
		$rules->update();
		$fRelations->update();
		
		//---set properties
		if ($typeForSaveTool=='blog') {
            if(isset($_POST['forumReact'])) fPages::setProperty($nid,'forumSet',(int) $_POST['forumReact']);
        }
		
		//CLEAR DRAFT
		fUserDraft::clear($textareaIdDescription);
		fUserDraft::clear($textareaIdContent);
		if($user->currentPage['typeId']=='forum' || $user->currentPage['typeId']=='blog') fUserDraft::clear($textareaIdForumHome);
		if(isset($nid)) $user->currentPageId = $nid;		
		if($user->currentPageParam=='a') $user->currentPageParam = '';
		/**/
		
		/*galery foto upload*/
		
		if($user->currentPageParam!='a' && $typeForSaveTool=='galery') {
    
      if(!empty($_FILES)) {
            if(!empty($user->currentPage['galeryDir'])) {
            	$adr = $galery->get('rootImg').$user->currentPage['galeryDir'];
            	
            	foreach ($_FILES as $foto) {
            		if ($foto["error"]==0) $up=fSystem::upload($foto,$adr,500000);
            	}
            } else fError::addError(ERROR_GALERY_DIREMPTY);
        }
        
        //---foto description, foto deleteing
        	if(isset($_POST['delfoto'])) foreach ($_POST['delfoto'] as $dfoto) $galery->removeFoto($dfoto);
        	if(isset($_POST['fot'])) {
        	    foreach ($_POST['fot'] as $k=>$v) {
        	        $changed = false;
        	        $newDesc = fSystem::textins($v['comm'],array('plainText'=>1));
        	        $galery->getFoto($k);
        	        $oldDesc = $galery->get('fComment');
        	        $oldDate = $galery->get('fDate');
        	        if($newDesc!=$oldDesc) {
        	            $galery->set('fComment',$newDesc);
        	            $changed = true;
        	        }
        	        $newDate = $v['date'];
        	        if(!empty($newDate)) {
        	           if(strpos($newDate,'.')===true) $newDate = fSystem::den($newDate);
        	           elseif(!fSystem::isDate($newDate)) $newDate = '';
        	           if(empty($newDate)) fError::addError(ERROR_DATE_FORMAT);
        	           else {
        	               $galery->set('fDate',$newDate);
        	               $changed=true;
        	           }
        	        }
        	        if($changed) $galery->updateFoto();
        	    }
        	}
    
    }
        
    /**/
		
		
		fHTTP::redirect($user->getUri());
	} else {
	   //---error during value check .. let the values stay in form - data remain in _POST
		fUserDraft::save($textareaIdDescription);
		fUserDraft::save($textareaIdContent);
		if($user->currentPage['typeId']=='forum' || $user->currentPage['typeId']=='blog') fUserDraft::save($textareaIdForumHome,$_POST['forumhome']);
	}
}

if (isset($_POST['del']) && $user->currentPageParam=='e') {
  if($typeForSaveTool=='galery') {
    //---delete photo
  	$dir = $user->currentPage['galeryDir'];
  	$arrd = $db->getCol("SELECT itemId FROM sys_pages_items WHERE pageId='".$user->currentPageId."'");
  	foreach ($arrd as $df) $galery->removeFoto($df);
  	if(!empty($dir)) {
  	  fSystem::rm_recursive($galery->_rootImg.$dir);
  	  $galery->getGaleryData($user->currentPageId);
  		$cachePath = $galery->getThumbCachePath();
  		fSystem::rm_recursive($cachePath);
  		$systemCachePath = $galery->getThumbCachePath($galery->_cacheDirSystemResolution);
  		fSystem::rm_recursive($systemCachePath);
  	}
	}
	fPages::deletePage($user->currentPageId);
	fHTTP::redirect($user->getUri('','galer'));
}

//---SHOW TIME
/***
 *TODO:
 *-kdyz je admin - tlacitko smazat
 *- kdyz se maze top stranka tak se jen skryje
 *-   
 *
 *
 *
 *
 **/
if(isset($_POST['save'])) {
  //---load from save
  $pageData = $arr;
  unset($arr);
} else if($user->currentPageParam=='a') {
	//new page
	$pageData = $sPage->defaults[$typeForSaveTool];
} else {
	//edit page
	$pageData = $user->currentPage;
}

$tpl=new fTemplateIT('page.edit.tpl.html');
$tpl->setVariable('FORMACTION',$user->getUri());
if(!empty($pageData['userIdOwner'])) {
	$tpl->setVariable('OWNERLINK','?k=finfo&who='.$pageData['userIdOwner']);
	$tpl->setVariable('OWNERNAME',$user->getgidname($pageData['userIdOwner']));
}

$pageDesc = '';
$pageCont = '';

if(isset($pageData['name'])) $tpl->setVariable('PAGENAME',$pageData['name']);
if(isset($pageData['description'])) if(!$pageDesc = fUserDraft::get($textareaIdDescription)) $pageDesc = $pageData['description'];
if(isset($pageData['content'])) if(!$pageCont = fUserDraft::get($textareaIdContent)) $pageCont = $pageData['content'];

$tpl->setVariable('PAGEDESCRIPTIONID',$textareaIdDescription);
$tpl->setVariable('PAGEDESCRIPTION',fSystem::textToTextarea($pageDesc));

$tpl->setVariable('PAGECONTENTID',$textareaIdContent);
$tpl->setVariable('PAGECONTENT',fSystem::textToTextarea($pageCont));
$tpl->addTextareaToolbox('PAGECONTENTTOOLBOX',$textareaIdContent);

if(!empty($pageData['pageIco'])) $tpl->setVariable('PAGEICOLINK',WEB_REL_PAGE_AVATAR.$pageData['pageIco']);
$tpl->setVariable('PAGEPERMISIONSFORM',$rules->printEditForm($user->currentPageId));

if($user->currentPageParam != 'a') $tpl->setVariable('RELATIONSFORM',$fRelations->getForm($user->currentPageId));

$tpl->touchBlock('pageavatarupload');

if($typeForSaveTool == 'forum') {
	//enable avatar
	$tpl->touchBlock('forumspecifictab');
	//FORUM HOME
	if(!$home = fUserDraft::get($textareaIdForumHome)) {
		  $home = fSystem::textToTextarea($sPage->getXMLVal('home'));
	}
	$tpl->setVariable('CONTENT',$home);
	$tpl->setVariable('HOMEID',$textareaIdForumHome);
	$tpl->addTextareaToolbox('CONTENTTOOLBOX',$textareaIdForumHome);
}

if($typeForSaveTool == 'galery' && $user->currentPageParam != 'a') {
  $galery->getGaleryData($user->currentPageId);
  $galery->getFoto($user->currentPageId,true,(($galery->gOrderItems==1)?('i.dateCreated desc'):('i.enclosure')));
   
  $pageData['galeryDir'] = $galery->gDir;
}

if($typeForSaveTool == 'galery') {
    if(isset($pageData['galeryDir'])) $tpl->setVariable('GDIR',$pageData['galeryDir']);
    $tpl->setVariable('PERPAGE',$sPage->getXMLVal('enhancedsettings','perpage'));
    $tpl->setVariable('GTHUMBWIDTH',$sPage->getXMLVal('enhancedsettings','widthpx'));
    $tpl->setVariable('GTHUMBHEIGHT',$sPage->getXMLVal('enhancedsettings','heightpx'));
    if($sPage->getXMLVal('enhancedsettings','thumbnailstyle') == 2) $tpl->touchBlock('galerythumbstyle2');
    //$tpl->touchBlock('fforum'.($sPage->getXMLVal('enhancedsettings','fotoforum')*1));
} elseif ($typeForSaveTool=='blog') {
    $tpl->touchBlock('fforum'.(fPages::getProperty($user->currentPageId,'forumSet',1)*1));
}

if($typeForSaveTool == 'galery' && $user->currentPageParam != 'a') {
  $tpl->touchBlock('galeryspecifictabs');
    
	$tpl->setVariable('FOTOTOTAL',count($galery->arrData));
	
	if($sPage->getXMLVal('enhancedsettings','orderitems') == 1) $tpl->touchBlock('gorddate');
	
	if(!empty($galery->arrData)) {
    	foreach ($galery->arrData as $foto){
    	    list($date,$time) = explode('T',$foto['dateIso']);
    	    if($date=='0000-00-00') $date='';
    	    $exif = exif_read_data(ROOT.ROOT_WEB.$foto['detailUrl']);
    	    if($exif!==false) {
        	    if(empty($date)) {
                    $date = date("Y-m-d",$exif['FileDateTime']);
                    if(isset($exif['DateTimeOriginal'])) {
                        $da = new DateTime($exif['DateTimeOriginal']);
                        $date = $da->format("Y-m-d");
                    }
                }
    	    }
            
            $tpl->setCurrentBlock('gfoto');
    	    $tpl->setVariable('FID',$foto['itemId']);
    	    $tpl->setVariable('FNAME',$foto['enclosure']);
    	    $tpl->setVariable('FTHUMBURL',$foto['thumbUrl']);
    	    $tpl->setVariable('FCOMMENT',$foto['text']);
    	    
    	    if($date!='0000-00-00') {
    	       $tpl->setVariable('FDATE',$date);
    	    }
    	    
    	    $tpl->parseCurrentBlock();
    	}
    	
	}
  
  
  $numInputs=7;
  for ($x=1;$x<$numInputs;$x++) {
  	$tpl->setCurrentBlock('uploadinput');
  	$tpl->setVariable('UPLOADINPUTLABEL','Foto '.$x.'.');
  	$tpl->setVariable('UPLOADINPUTID',$x);
  	$tpl->parseCurrentBlock();
  }

}

$categoryId = (isset($pageData['categoryId']))?($pageData['categoryId']):(0);
$arrTmp = $db->getAll('select categoryId,name from sys_pages_category where typeId="'.$typeForSaveTool.'"');
if(!empty($arrTmp)) $tpl->setVariable('CATEGORYOPTIONS',fSystem::getOptions($arrTmp,$categoryId));



//---if pageParam = sa - more options to edit on page
//--- nameShort,template,menuSecondaryGroup,categoryId,dateContent,locked,authorContent
if($user->currentPageParam=='sa') {
    $arrTmp = $db->getAll('select menuSecondaryGroup,menuSecondaryGroup from sys_menu_secondary group by menuSecondaryGroup order by menuSecondaryGroup');
    $tpl->setVariable('MENUSECOPTIONS',fSystem::getOptions($arrTmp,$pageData['menuSecondaryGroup']));
    
    $tpl->setVariable('LOCKEDOPTIONS',fSystem::getOptions($ARRLOCKED,$pageData['locked']));
    $tpl->setVariable('PAGEAUTHOR',$pageData['authorContent']);
    $date = new DateTime($pageData['dateContent']);
    $tpl->setVariable('DATECONTENT',$date->format("d.m.Y"));
    $tpl->setVariable('PAGENAMESHORT',$pageData['nameshort']);
    $tpl->setVariable('PAGETEMPLATE',$pageData['template']);
}

if($typeForSaveTool=='blog' && $user->currentPageParam!='a') {
    $tpl->touchBlock('categorytab');
    $tpl->setVariable('PAGECATEGORYEDIT',$category->getEdit());
}

//---left panels configure
    $tpl->touchBlock('leftpaneltab');
    
    $tpl->setVariable('LEFTPANELEDIT',$fLeft->showEdit());


$TOPTPL->addTab(array("MAINHEAD"=>($user->currentPageParam == 'a')?(LABEL_PAGE_NEW):(''),"MAINDATA"=>$tpl->get()));