<?php
require(INIT_FILENAME);
$USERDRAFT = false;

//----DEBUG
if(isset($_GET['d'])) {
    print_r($user->currentPage);
    //print_r($_SESSION);
    die(); 
    fSystem::profile('START:'); 
}

if(isset($_GET['t'])) {
  //tag item
  $tagItem = $_GET['t'] * 1;
  if($tagItem > 0) fItems::tag($tagItem,$user->gid);
}
if(isset($_GET['rt'])) {
  //remove tag item
  $tagItem = $_GET['rt'] * 1;
  if($tagItem > 0) fItems::removeTag($tagItem,$user->gid);
  fHTTP::redirect($user->getUri());
}
if(isset($_REQUEST['book'])) fForum::setBooked($user->currentPageId,$user->gid,1);
if(isset($_REQUEST['unbook'])) fForum::setBooked($user->currentPageId,$user->gid,0);

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

if(($user->currentPage['locked']==2 && $user->gid != $user->currentPage['userIdOwner']) || $user->currentPage['locked']==3)  {
	fError::addError(MESSAGE_PAGE_LOCKED);
	if(!fRules::get($user->gid,'sadmi',1)) $user->currentPageAccess = false;
}
if($user->currentPageAccess) {
  $template = $user->currentPage["template"];
}
//---DATA of page
$TOPTPL = new fTemplateIT('main.tpl.html');

//----DEBUG
if(isset($_GET['d'])) { fSystem::profile('BEFORE CONTENT'); }

if($user->currentPageAccess == true) {
    if($user->currentPageParam=='sa') $template = 'page.edit.php';
    if($template != '') {
    	$staticTemplate = false;
    	if (preg_match("/(.html)$/",$template)) {
    		$staticTemplate = true;
    		if(fRules::get($user->gid,$user->currentPageId,2)) {
    			if($user->currentPageParam == 'e') {
    				fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId,''),BUTTON_PAGE_BACK);
    				$staticTemplate = false;
    				$template = 'page.edit.php';
    			}
        		else fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId.'e'),BUTTON_EDIT);
    		}
    	}

    	if ($staticTemplate == false) {

    		include(ROOT.ROOT_CODE.$template);

    	} else {
    		//STATIC TEMPLATE
    		$tpl = new fTemplateIT($template);
    		$tpl->vars = array_merge($user->currentPage,$_GET);
    		$tpl->edParseBlock();
    		$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
        unset($tpl);	
    	}
    	
    } else {
    	//NOT TEMPLATE AT ALL
    	$contentData = array("MAINDATA"=>$user->currentPage["content"]);
    }
	//SUPERADMIN access - tlacitka na nastaveni stranek
    if(fRules::get($user->gid,'sadmi',1)) {
        if($user->currentPageParam=='sa') fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId,''),BUTTON_PAGE_BACK);
        else fSystem::secondaryMenuAddItem($user->getUri('',$user->currentPageId,'sa'),BUTTON_PAGE_SETTINGS,'',1);
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

$JSWrapper = new fJSWrapper(ROOT.ROOT_WEB.'data/cache/js/','/data/cache/js/',$user->currentPage['typeId'].'.'.(($user->idkontrol===true)?('1'):('0')).'.js');
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

if($user->currentPageAccess) {
  $pageTitle = $user->currentPage["name"];
  $pageHeading = $user->currentPage["name"];
}
$TOPTPL->setVariable("TITLE", (!empty($pageTitle)?($pageTitle.' - '):('')).BASEPAGETITLE);
if(!empty($user->currentPage["description"])) $TOPTPL->setVariable("DESCRIPTION", str_replace('"','',$user->currentPage["description"]));
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
$lomenuItems = fSystem::secondaryMenu($user->currentPageId);
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
$fLeftpanel = new fLeftPanel($user->currentPageId,$user->gid,$user->currentPage['typeId']);
$fLeftpanel->load();
$fLeftpanel->show();

 
//---FOOTER INFO
$TOPTPL->setVariable("COUNTER", $user->pocitadlo().'::'.((isset($debugTime))?('<strong>'.$debugTime.'</strong>::'):('')).round((fSystem::getmicrotime()-$start),3));

$ttips = '';
if(!empty($user->arrUsers['tooltips'])) $ttips .= implode("\n",$user->arrUsers['tooltips']);
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
if($user->currentPage['typeId']=='blog') {
    if(fRules::get($user->gid,$user->currentPageId,2)) {
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

$user->myDestructor();
//SESSIONS HAVE TO BE CLOSED BEFORE DB DISCONNECT WHEN SAVED IN DB
session_write_close();
//FIXME: when enabled it end up into connection interupted in edit of galery?
$db->disconnect();