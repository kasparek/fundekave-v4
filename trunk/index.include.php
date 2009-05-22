<?php
require(INIT_FILENAME);
$USERDRAFT = false;

//----DEBUG
if(isset($_GET['d'])) {
    print_r($user->pageVO);
    //print_r($_SESSION);
    die(); 
    fSystem::profile('START:'); 
}

if(isset($_GET['t'])) {
  //tag item
  $tagItem = $_GET['t'] * 1;
  if($tagItem > 0) fItems::tag($tagItem,$user->userVO->userId);
}
if(isset($_GET['rt'])) {
  //remove tag item
  $tagItem = $_GET['rt'] * 1;
  if($tagItem > 0) fItems::removeTag($tagItem,$user->userVO->userId);
  fHTTP::redirect($user->getUri());
}
if(isset($_REQUEST['book'])) fForum::setBooked($user->pageVO->pageID,$user->userVO->userId,1);
if(isset($_REQUEST['unbook'])) fForum::setBooked($user->pageVO->pageID,$user->userVO->userId,0);

if($user->idkontrol) {
  fXajax::register('user_switchFriend');
  fXajax::register('user_tag');
  fXajax::register('fcalendar_monthSwitch');
  fXajax::register('draft_save');
  fXajax::register('poll_pollVote');
  fXajax::register('forum_fotoDetail');
  fXajax::register('pocket_add');
  fXajax::register('pocket_action');
  fXajax::register('forum_booked');
  //post page
  $reqSetRecipient = fXajax::register('post_setRecipientAvatarFromBooked');
  $reqSetRecipient->setParameter(0, XAJAX_INPUT_VALUE, 'prokoho_book');
  $reqSetRecipientFromInput = fXajax::register('post_setRecipientAvatarFromInput');
  $reqSetRecipientFromInput->setParameter(0, XAJAX_INPUT_VALUE, 'prokoho');
  //items
  fXajax::register('user_tag');
  
  fXajax::register('forum_auditBook');
  //forum
  fXajax::register('forum_toolbar');
  //blog
  fXajax::register('blog_blogEdit');
  fXajax::register('blog_processFormBloged');
  
  
  fItems::setTagToolbar();
}

if(($user->pageVO->locked==2 && $user->userVO->userId != $user->pageVO->userIdOwner) || $user->pageVO->locked==3)  {
	fError::addError(MESSAGE_PAGE_LOCKED);
	if(!fRules::get($user->userVO->userId,'sadmi',1)) $user->pageAccess = false;
}

//---DATA of page
$TOPTPL = new fTemplateIT('main.tpl.html');

//----DEBUG
if(isset($_GET['d'])) { fSystem::profile('BEFORE CONTENT'); }

if($user->pageAccess == true) {
    if($user->pageParam=='sa') $template = 'page.edit.php';
    else $template = $user->pageVO->template;
    if($template != '') {
    	$staticTemplate = false;
    	if (preg_match("/(.html)$/",$template)) {
    		$staticTemplate = true;
    		if(FRules::get($user->userVO->userId,$user->pageVO->pageId,2)) {
    			if($user->pageParam == 'e') {
    				fSystem::secondaryMenuAddItem($user->getUri('',$user->pageVO->pageId,''),BUTTON_PAGE_BACK);
    				$staticTemplate = false;
    				$template = 'page.edit.php';
    			}
        		else fSystem::secondaryMenuAddItem($user->getUri('',$user->pageVO->pageId.'e'),BUTTON_EDIT);
    		}
    	}

    	if ($staticTemplate == false) {

    		include(ROOT.ROOT_CODE.$template);

    	} else {
    		//STATIC TEMPLATE
    		$tpl = new fTemplateIT($template);
    		$tpl->vars = array_merge($user->pageVO, $_GET);
    		$tpl->edParseBlock();
    		$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
        	unset($tpl);
    	}
    	
    } else {
    	//NOT TEMPLATE AT ALL
    	$contentData = array("MAINDATA"=>$user->pageVO->content);
    }
	//SUPERADMIN access - tlacitka na nastaveni stranek
    if(FRules::get($user->userVO->userId,'sadmi',1)) {
        if($user->pageParam=='sa') fSystem::secondaryMenuAddItem($user->getUri('',$user->pageVO->pageId,''),BUTTON_PAGE_BACK);
        else fSystem::secondaryMenuAddItem($user->getUri('',$user->pageVO->pageId,'sa'),BUTTON_PAGE_SETTINGS,'',1);
    }
    
    /**/
}

//----DEBUG
if(isset($_GET['d'])) { fSystem::profile('AFTER CONTENT'); }
 
//----------------	generate page	----------------------------------------

//---ERROR MESSAGES
$TOPTPL->printErrorMsg();
//---HEADER
$cssPath = $user->getSkinCSSFilename();
$TOPTPL->setVariable("CSSSKIN", $cssPath);

$TOPTPL->setVariable("CHARSET", CHARSET);

if(is_object($xajax)) $arrXajax = explode("\n",$xajax->getJavascript());

$JSWrapper = new fJSWrapper(ROOT.ROOT_WEB.'data/cache/js/','/data/cache/js/',$user->pageVO->typeId.'.'.(($user->idkontrol===true)?('1'):('0')).'.js');
if(!$JSWrapper->isCached()) {
  if(!empty($arrXajax)) {
      foreach ($arrXajax as $row) {
          $row = trim($row);
      	if(!empty($row)) {
      	    if(preg_match("/(.js)$/",$row)) $JSWrapper->addFile($row);
      	    else $JSWrapper->addCode($row);
      	}
      }
  }
  $JSWrapper->addFile(ROOT.ROOT_WEB.'js/dLiteCompressed-1.0.js');
  $JSWrapper->addFile(ROOT.ROOT_WEB.'js/supernote.js');
  $JSWrapper->addFile(ROOT.ROOT_WEB.'js/fdk-ondom.js');
}
if($wrap = $JSWrapper->get()) {
    $TOPTPL->setVariable("WRAPPEDJS", $wrap);
}

if($user->pageAccess) {
  $pageTitle = $user->pageVO->name;
  $pageHeading = $user->pageVO->name;
}
$TOPTPL->setVariable("TITLE", (!empty($pageTitle)?($pageTitle.' - '):('')).BASEPAGETITLE);
if(!empty($user->pageVO->description)) $TOPTPL->setVariable("DESCRIPTION", str_replace('"','',$user->pageVO->description));
if(!empty($pageHeading)) $TOPTPL->setVariable('PAGEHEAD',$pageHeading);
//---BODY PARAMETERS
$bodyAction = '';
$bodyAction .= (!empty($onload))?(' onload="'.$onload.'" '):('');
$bodyAction .= (!empty($onunload))?(" onbeforeunload='".$onunload."' "):('');
$TOPTPL->setVariable("BODYACTION", $bodyAction);
//---MAIN MENU
$arrMenuItems = fSystem::topMenu();
if(!empty($arrMenuItems)) {
  foreach($arrMenuItems as $menuItem) {
    $TOPTPL->setCurrentBlock("topmenuitem");
    $TOPTPL->setVariable('LINK',$menuItem['LINK']);
    $TOPTPL->setVariable('TEXT',$menuItem['TEXT']);
    if($menuItem['ACTIVE']==1) {
      $TOPTPL->touchBlock('topmenuactivelink');
    }
    $TOPTPL->parseCurrentBlock();
  }
}
//---BANNER
if(!isset($_GET['nobanner'])) {
  $banner = fSystem::grndbanner();
  if(!empty($banner)) {
    $TOPTPL->setVariable("BANNER",$banner);
    $TOPTPL->touchBlock('hasMainBanner');
  }
}
//---SECONDARY MENU
$lomenuItems = fSystem::secondaryMenu($user->pageVO->pageId);
if(!empty($lomenuItems)) {
  foreach($lomenuItems as $menuItem) {
    $TOPTPL->setCurrentBlock("secondary-menu-item");
    $TOPTPL->setVariable('LOLINK',$menuItem['LINK']);
    $TOPTPL->setVariable('LOTEXT',$menuItem['TEXT']);
    if(isset($menuItem['CLICK'])) $TOPTPL->setVariable('LOCLICK',$menuItem['CLICK']);
    if(isset($menuItem['ID'])) $TOPTPL->setVariable('LOID',$menuItem['ID']);
    if($menuItem['ACTIVE']==1) {
      $TOPTPL->touchBlock('secondary-menu-activelink');
    }
    if($menuItem['OPPOSITE']==1) {
      $TOPTPL->touchBlock('secondary-menu-oppositebutton');
    }
    $TOPTPL->parseCurrentBlock();
  }
}
//---LEFT PANEL POPULATING
$fLeftpanel = new fLeftPanel($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->typeId);
$fLeftpanel->load();
$fLeftpanel->show();

 
//---FOOTER INFO
$pagesSum = FDBTool::getOne("select sum(hit) from sys_users", 'tCounter', 'default', 's', 0);
$TOPTPL->setVariable("COUNTER", $pagesSum.'::'.((isset($debugTime))?('<strong>'.$debugTime.'</strong>::'):('')).round((fSystem::getmicrotime()-$start),3));

//---user tooltips - one per user avatar displayed
$ttips = '';
$cache = FCache::getInstance('l');
if($arrUserAvatarTips = $cache->getGroup('UavatarTip') !==false ) $ttips .= implode("\n", $arrUserAvatarTips);
$TOPTPL->setVariable('USERTOOLTIPS',$ttips);

//--- last check
//if calendar js and css is needed
$useCalendar = false;
$useDomTabs = false;
foreach ($TOPTPL->blockdata as $item) {
    if(strpos($item, 'format-') !== false) {
        $useCalendar = true;
    }
    if(strpos($item, 'domtabs') !== false) {
        $useDomTabs = true;
    }
}
if($user->pageVO->typeId=='blog') {
    if(fRules::get($user->userVO->userId,$user->pageVO->pageId,2)) {
        $useCalendar = true;
    }
}
if($useCalendar === true) {
    $TOPTPL->setVariable("CSSSKINCALENDAR", $cssPath);
    $TOPTPL->touchBlock("calendar2"); //---javascript on the end of the page
}
if($useDomTabs === true) {
    $TOPTPL->touchBlock("domtabs2"); //---javascript on the end of the page
}


//----DEBUG
if(isset($_GET['d'])) { fSystem::profile('DONE:');die(); }

//---PRINT PAGE
header("Content-Type: text/html; charset=".CHARSET);
$TOPTPL->show();

session_write_close();
$db = FDBConn::getInstance();
$db->disconnect();