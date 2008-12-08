<?php
$rules = new fRules((($user->currentPageParam != 'a')?($user->currentPageId):('')));
if($user->currentPageParam != 'a') $fRelations = new fPagesRelations($user->currentPageId);
/**
 * $user->currentPageParam == a - add from defaults, e - edit from user->currentPage
 */

$typeForSaveTool = $user->currentPage['typeId'];
if($user->currentPageParam=='a') $typeForSaveTool = $user->currentPage['typeIdChild'];  

if(isset($_POST["save"])) {
  
$sPage = new fPagesSaveTool($typeForSaveTool);  

  $notQuoted = array();
	$arr['name'] = fSystem::textins($_POST['name'],array('plainText'=>1));
	if(empty($arr['name'])) fError::addError(ERROR_PAGE_ADD_NONAME);
	$arr['description']=fSystem::textins($_POST['description'],array('plainText'=>1));
	$arr['content']=fSystem::textins($_POST['content']);
	

	if($user->currentPageParam=='sa') {
	    $arr['nameShort'] = fSystem::textins($_POST['nameshort'],array('plainText'=>1));
	    $arr['authorContent'] = fSystem::textins($_POST['authorcontent'],array('plainText'=>1));
	    $arr['template'] = fSystem::textins($_POST['template'],array('plainText'=>1));
	    $dateContent = $_POST['datecontent'];
	    if(!empty($dateContent)) if(fSystem::isDate($dateContent)) $arr['dateContent'] = $dateContent;
	    
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
	
	if($user->currentPage['typeId']=='forum' || $user->currentPage['typeId']=='blog') {
    	$xml = new SimpleXMLElement($user->currentPage['pageParams']);
		$xml->home = fSystem::textins($_POST['forumhome']);
		$arr['pageParams'] = $xml->asXML();
	}

	if(!fError::isError()) {
		//---rules update	
		$rules->public=$_POST['public'];
		$rules->ruleText=$_POST['rule'];
		$rules->update();
		if($user->currentPageParam == 'a') {
			$arr['userIdOwner'] = $user->gid;
			$user->cacheRemove('calendarlefthand');
		} else {
		  $arr['pageId'] = $user->currentPageId;
			$fRelations->update();
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

		$nid = $sPage->savePage($arr,$notQuoted);

		$user->cacheRemove('forumdesc');
		
		//CLEAR DRAFT
		fUserDraft::clear($user->currentPageId.'desc');
		fUserDraft::clear($user->currentPageId.'cont');
		if($user->currentPage['typeId']=='forum' || $user->currentPage['typeId']=='blog') fUserDraft::clear($user->currentPageId.'home');
		if(isset($nid)) $user->currentPageId = $nid;		
		if($user->currentPageParam=='a') $user->currentPageParam = '';
		/**/
	} else {
		fUserDraft::save($user->currentPageId.'desc',$_POST['description']);
		fUserDraft::save($user->currentPageId.'cont',$_POST['content']);
		if($user->currentPage['typeId']=='forum' || $user->currentPage['typeId']=='blog') fUserDraft::save($user->currentPageId.'home',$_POST['forumhome']);
	}
		
	fHTTP::redirect($user->getUri());
}

if($user->currentPageParam=='a') {
	//new page
	$sPage = new fSqlSaveTool('sys_pages','pageId');
	$pageData = $sPage->defaults[$user->currentPage['typeIdChild']];
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
$tpl->setVariable('PAGENAME',$pageData['name']);

if(!$pageDesc = fUserDraft::get($user->currentPageId.'desc')) $pageDesc = $pageData['description'];
if(!$pageCont = fUserDraft::get($user->currentPageId.'cont')) $pageCont = $pageData['content'];

$tpl->setVariable('PAGEDESCRIPTIONID',$user->currentPageId.'desc');
$tpl->setVariable('PAGEDESCRIPTION',fSystem::textToTextarea($pageDesc));

$tpl->setVariable('PAGECONTENTID',$user->currentPageId.'cont');
$tpl->setVariable('PAGECONTENT',fSystem::textToTextarea($pageCont));
$tpl->addTextareaToolbox('PAGECONTENTTOOLBOX',$user->currentPageId.'cont');

if(!empty($pageData['pageIco'])) $tpl->setVariable('PAGEICOLINK',WEB_REL_PAGE_AVATAR.$pageData['pageIco']);
$tpl->setVariable('PAGEPERMISIONSFORM',$rules->printEditForm($user->currentPageId));

if($user->currentPageParam != 'a') $tpl->setVariable('RELATIONSFORM',$fRelations->getForm($user->currentPageId));

$tpl->touchBlock('pageavatarupload');

if($typeForSaveTool == 'forum' || $typeForSaveTool == 'blog') {
	//enable avatar
	$tpl->touchBlock('forumspecifictab');
	//FORUM HOME
	if(!$home = fUserDraft::get($user->currentPageId.'home')) {
		$xml = new SimpleXMLElement($pageData['pageParams']);
		$home = fSystem::textToTextarea($xml->home);
	}
	$tpl->setVariable('CONTENT',$home);
	$tpl->setVariable('HOMEID',$user->currentPageId.'home');
	$tpl->addTextareaToolbox('CONTENTTOOLBOX',$user->currentPageId.'home');
}
//---if pageParam = sa - more options to edit on page
//--- nameShort,template,menuSecondaryGroup,categoryId,dateContent,locked,authorContent
if($user->currentPageParam=='sa') {
    $arrTmp = $db->getAll('select menuSecondaryGroup,menuSecondaryGroup from sys_menu_secondary group by menuSecondaryGroup order by menuSecondaryGroup');
    $tpl->setVariable('MENUSECOPTIONS',fSystem::getOptions($arrTmp,$pageData['menuSecondaryGroup']));
    $arrTmp = $db->getAll('select categoryId,name from sys_pages_category where typeId="'.$pageData['typeId'].'"');
    if(!empty($arrTmp)) $tpl->setVariable('CATEGORYOPTIONS',fSystem::getOptions($arrTmp,$pageData['categoryId']));
    $tpl->setVariable('LOCKEDOPTIONS',fSystem::getOptions($ARRLOCKED,$pageData['locked']));
    $tpl->setVariable('PAGEAUTHOR',$pageData['authorContent']);
    $tpl->setVariable('DATECONTENT',$pageData['dateContent']);
    $tpl->setVariable('PAGENAMESHORT',$pageData['nameshort']);
    $tpl->setVariable('PAGETEMPLATE',$pageData['template']);
}
$TOPTPL->addTab(array("MAINHEAD"=>($user->currentPageParam == 'a')?(LABEL_PAGE_NEW):(''),"MAINDATA"=>$tpl->get()));