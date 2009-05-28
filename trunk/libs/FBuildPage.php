<?php
class FBuildPage {
	private static $instance;
	
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = &new fTemplateIT('main.tpl.html');
		}
		return self::$instance;
	}

	static function addTab($arrVars) {
		$tpl = FBuildPage::getInstance();
		$tpl->addTab($arrVars);
	}
	
	static function printErrorMsg() {
		$tpl = FBuildPage::getInstance();
		$tpl->printErrorMsg();
	}
	
	static function process() {
		$tpl = FBuildPage::getInstance();
		$user = FUser::getInstance();
		if($user->pageAccess == true) {
		
		    if($user->pageParam=='sa') $template = 'page_pageEdit';
		    else $template = $user->pageVO->template;
		    if($template != '') {
		    	$staticTemplate = false;
		    	if (preg_match("/(.html)$/",$template)) {
		    		$staticTemplate = true;
		    		if(FRules::get($user->userVO->userId,$user->pageVO->pageId,2)) {
		    			if($user->pageParam == 'e') {
		    				$staticTemplate = false;
		    				$template = 'page_pageEdit';
		    			}
		    		}
		    	}
		    	
		    	if ($staticTemplate == false) {
		    		if( class_exists($template) ) {
		    			$c = new $template;
		    			$c->process();
		    		}
		    		
		    	}
		    
		    }
		}
	}
	
	static function baseContent() {
		$tpl = FBuildPage::getInstance();
		$user = FUser::getInstance();
		if($user->pageAccess == true) {
		
		    if($user->pageParam=='sa') $template = 'page_pageEdit';
		    else $template = $user->pageVO->template;
		    if($template != '') {
		    	$staticTemplate = false;
		    	if (preg_match("/(.html)$/",$template)) {
		    		$staticTemplate = true;
		    		if(FRules::get($user->userVO->userId,$user->pageVO->pageId,2)) {
		    			if($user->pageParam == 'e') {
		    				fSystem::secondaryMenuAddItem($user->getUri('',$user->pageVO->pageId,''),FLang::$BUTTON_PAGE_BACK);
		    				$staticTemplate = false;
		    				$template = 'page_pageEdit';
		    			}
		        		else fSystem::secondaryMenuAddItem($user->getUri('',$user->pageVO->pageId.'e'),FLang::$BUTTON_EDIT);
		    		}
		    	}
		
		    	if ($staticTemplate == false) {
		    		
		    		if( class_exists($template) ) {
		    			$c = new $template;
		    			$c->build();
		    		}
		    		
		    	} else {
		    		//STATIC TEMPLATE
		    		$tpl = new fTemplateIT($template);
		    		$tpl->vars = array_merge($user->pageVO, $_GET);
		    		$tpl->edParseBlock();
		    		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		        	unset($tpl);
		    	}
		    	
		    } else {
		    	//NOT TEMPLATE AT ALL
		    	$contentData = array("MAINDATA"=>$user->pageVO->content);
		    }
			//SUPERADMIN access - tlacitka na nastaveni stranek
		    if(FRules::get($user->userVO->userId,'sadmi',1)) {
		        if($user->pageParam=='sa') fSystem::secondaryMenuAddItem($user->getUri('',$user->pageVO->pageId,''),FLang::$BUTTON_PAGE_BACK);
		        else fSystem::secondaryMenuAddItem(FUser::getUri('',$user->pageVO->pageId,'sa'),FLang::$BUTTON_PAGE_SETTINGS,'',1);
		    }
		    
		    /**/
		}
	}
	
	static function show() {
		//----DEBUG
		if(isset($_GET['d'])) { fSystem::profile('BEFORE CONTENT'); }
		
		FBuildPage::baseContent();
		
		//----DEBUG
		if(isset($_GET['d'])) { fSystem::profile('AFTER CONTENT'); }
		
		
		$tpl = FBuildPage::getInstance();
		$user = FUser::getInstance();
		//---ERROR MESSAGES
		FBuildPage::printErrorMsg();
		//---HEADER
		$cssPath = $user->getSkinCSSFilename();
		$tpl->setVariable("CSSSKIN", $cssPath);
		$tpl->setVariable("CHARSET", CHARSET);
		//if(is_object($xajax)) $arrXajax = explode("\n",$xajax->getJavascript());

		$JSWrapper = new FJSWrapper(ROOT.ROOT_WEB.'data/cache/js/','/data/cache/js/',$user->pageVO->typeId.'.'.(($user->idkontrol===true)?('1'):('0')).'.js');
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
		    $tpl->setVariable("WRAPPEDJS", $wrap);
		}

		if($user->pageAccess) {
		  $pageTitle = $user->pageVO->name;
		  $pageHeading = $user->pageVO->name;
		}
		
		$tpl->setVariable("TITLE", (!empty($pageTitle)?($pageTitle.' - '):('')).BASEPAGETITLE);
		if(!empty($user->pageVO->description)) $tpl->setVariable("DESCRIPTION", str_replace('"','',$user->pageVO->description));
		if(!empty($pageHeading)) $tpl->setVariable('PAGEHEAD',$pageHeading);

		//---BODY PARAMETERS
		$bodyAction = '';
		$bodyAction .= (!empty($onload))?(' onload="'.$onload.'" '):('');
		$bodyAction .= (!empty($onunload))?(" onbeforeunload='".$onunload."' "):('');
		$tpl->setVariable("BODYACTION", $bodyAction);
		//---MAIN MENU
		$arrMenuItems = fSystem::topMenu();
		if(!empty($arrMenuItems)) {
		  foreach($arrMenuItems as $menuItem) {
		    $tpl->setCurrentBlock("topmenuitem");
		    $tpl->setVariable('LINK',$menuItem['LINK']);
		    $tpl->setVariable('TEXT',$menuItem['TEXT']);
		    if($menuItem['ACTIVE']==1) {
		      $tpl->touchBlock('topmenuactivelink');
		    }
		    $tpl->parseCurrentBlock();
		  }
		}

		//---BANNER
		if(!isset($_GET['nobanner'])) {
		  $banner = fSystem::grndbanner();
		  if(!empty($banner)) {
		    $tpl->setVariable("BANNER",$banner);
		    $tpl->touchBlock('hasMainBanner');
		  }
		}

		if($user->pageAccess === true) {
			//---SECONDARY MENU
			$lomenuItems = FSystem::secondaryMenu($user->pageVO->pageId);
			if(!empty($lomenuItems)) {
			  foreach($lomenuItems as $menuItem) {
			    $tpl->setCurrentBlock("secondary-menu-item");
			    $tpl->setVariable('LOLINK',$menuItem['LINK']);
			    $tpl->setVariable('LOTEXT',$menuItem['TEXT']);
			    if(isset($menuItem['CLICK'])) $tpl->setVariable('LOCLICK',$menuItem['CLICK']);
			    if(isset($menuItem['ID'])) $tpl->setVariable('LOID',$menuItem['ID']);
			    if($menuItem['ACTIVE']==1) {
			      $tpl->touchBlock('secondary-menu-activelink');
			    }
			    if($menuItem['OPPOSITE']==1) {
			      $tpl->touchBlock('secondary-menu-oppositebutton');
			    }
			    $tpl->parseCurrentBlock();
			  }
			}
	
			//---LEFT PANEL POPULATING
			$fLeftpanel = new fLeftPanel($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->typeId);
			$fLeftpanel->load();
			$fLeftpanel->show();
		}
		
		//---FOOTER INFO
		$cache = FCache::getInstance('l');
		$start = $cache->getData('start','debug');
		
		$pagesSum = FDBTool::getOne("select sum(hit) from sys_users", 'tCounter', 'default', 's', 0);
		$tpl->setVariable("COUNTER", $pagesSum.'::'.((isset($debugTime))?('<strong>'.$debugTime.'</strong>::'):('')).round((fSystem::getmicrotime()-$start),3));
		
		//---user tooltips - one per user avatar displayed
		$ttips = '';
		$cache = FCache::getInstance('l');
		if(($arrUserAvatarTips = $cache->getGroup('UavatarTip')) !==false ) $ttips .= implode("\n", $arrUserAvatarTips);
		$tpl->setVariable('USERTOOLTIPS',$ttips);

		//--- last check
		//if calendar js and css is needed
		$useCalendar = false;
		$useDomTabs = false;
		foreach ($tpl->blockdata as $item) {
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
		    $tpl->setVariable("CSSSKINCALENDAR", $cssPath);
		    $tpl->touchBlock("calendar2"); //---javascript on the end of the page
		}
		if($useDomTabs === true) {
		    $tpl->touchBlock("domtabs2"); //---javascript on the end of the page
		}


		//----DEBUG
		if(isset($_GET['d'])) { fSystem::profile('DONE:');die(); }
		
		//---PRINT PAGE
		header("Content-Type: text/html; charset=".CHARSET);
		$tpl->show();
	}
}