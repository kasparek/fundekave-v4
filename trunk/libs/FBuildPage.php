<?php
class FBuildPage {
	private static $instance;

	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = &new FTemplateIT('main.tpl.html');
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

	static function getTemplate($template) {
		$old = array(
     'events.view.php'=>'page_EventsView',
     'forum.view.php'=>'page_ForumView',
     'bloged.main.php'=>'page_Main',
	   'pages.list.php'=>'page_PagesList',
	   'galery.list.php'=>'page_GaleryList',
	   'galery.detail.php'=>'page_GaleryDetail',
	   'pages.booked.php'=>'page_PagesBooked',
	   'items.live.php'=>'page_ItemsLive',
	   'user.post.php'=>'page_UserPost',
		'events.edit.php'=>'page_EventsEdit',
		'user.friends.php'=>'page_UserFriends',
		'user.friends.all.php'=>'page_FriendsAll',
		'items.search.php'=>'page_ItemsSearch'
		
		);
		//---temporary till database change
		if(isset($old[$template])) {
			$template = $old[$template];
		}
		return $template;
	}

	static function process( $data ) {
		$tpl = FBuildPage::getInstance();
		$user = FUser::getInstance();
		if($user->pageAccess == true) {
			if($user->pageParam=='sa' || $user->pageParam == 'e') $template = 'page_PageEdit';
			else $template = $user->pageVO->template;
			if($template != '') {
				if (!preg_match("/(.html)$/",$template)) {
					$template = FBuildPage::getTemplate($template);
					if( class_exists($template) ) {
						$c = new $template;
						$c->process( $data );
					}
				}
			}
		}
	}

	static function baseContent() {
		$tpl = FBuildPage::getInstance();
		$user = FUser::getInstance();
		if($user->pageAccess == true) {
			
			$staticTemplate = false;
			
			switch($user->pageParam) {
				case 'sa':
				case 'e':
					$template = 'page_PageEdit';
				break;
				
				/* poll */
				case 'p':
					$template = 'page_PagePoll';
				break;
				
				/* stats */
				case 's':
					$template = 'page_PageStat';
				break;
				
				/* home */
				case 'h':
					$homePage = $user->pageVO->getPageParam('home');
					if(empty($homePage)) $homePage = FLang::$MESSAGE_FORUM_HOME_EMPTY;
					$template='';
					$user->pageVO->content = $homePage;
				break;
				
				default:
					$template = $user->pageVO->template;
					if($template != '') {
						if (preg_match("/(.html)$/",$template)) {
							$staticTemplate = true;
						}
					}
				break;
			}
			
			if($template != '') {
				if ($staticTemplate === false) {
					//DYNAMIC TEMPLATE
					$template = FBuildPage::getTemplate($template);
					if( class_exists($template) ) {
						$c = new $template;
						$c->build();
					}
				} else {
					//STATIC TEMPLATE
					$tpl = new FTemplateIT($template);
					$tpl->vars = array_merge($user->pageVO, $_GET);
					$tpl->edParseBlock();
					FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
					unset($tpl);
				}
				 
			} else {
				//NOT TEMPLATE AT ALL
				$contentData = array("MAINDATA"=>$user->pageVO->content);
			}
	
			//DEFAULT TLACITKA - pro typy - galery, blog, forum
			$pageId = $user->pageVO->pageId;
			
			if(!empty($user->pageParam) || $user->itemVO->itemId > 0) {
				FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,''),FLang::$BUTTON_PAGE_BACK);
			}
			
			if($user->pageVO->typeId == 'forum') {
				FSystem::secondaryMenuAddItem($user->getUri('',$pageId,'h'), FLang::$LABEL_HOME);
			}
			
			if($user->idkontrol==true && ($staticTemplate==true || $user->pageVO->typeId == 'forum' || $user->pageVO->typeId == 'galery' || $user->pageVO->typeId == 'blog')) {
				if(empty($user->pageParam)) {
					if($user->pageVO->userIdOwner != $user->userVO->userId) {
						FSystem::secondaryMenuAddItem(FUser::getUri('m=user-book&d=page:'.$pageId), ((0 == $user->pageVO->favorite)?(FLang::$LABEL_BOOK):(FLang::$LABEL_UNBOOK)), 0, 'fajaxa');
					}
					FSystem::secondaryMenuAddItem(FUser::getUri('m=user-pocketIn&d=page:'.$pageId), FLang::$LABEL_POCKET_PUSH, 0, '', 'fajaxa');
					if(FRules::getCurrent(2)) {
						
						FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,'e'),FLang::$LABEL_SETTINGS);
						
					}
				}
				FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,'p'), FLang::$LABEL_POLL);
				FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,'s'), FLang::$LABEL_STATS);
			}
			//SUPERADMIN access - tlacitka na nastaveni stranek
			if(FRules::get($user->userVO->userId,'sadmi',1)) {
				FSystem::secondaryMenuAddItem(FUser::getUri('',$pageId,'sa'),FLang::$BUTTON_PAGE_SETTINGS,'',1);
			}

			/**/
		}
	}

	static function show() {
		
		FBuildPage::baseContent();

		$tpl = FBuildPage::getInstance();
		$user = FUser::getInstance();
		//---ERROR MESSAGES
		FBuildPage::printErrorMsg();
		//---HEADER
		$cssPath = $user->getSkinCSSFilename();
		$tpl->setVariable("CSSSKIN", $cssPath);
		$tpl->setVariable("CHARSET", CHARSET);
		//if(is_object($xajax)) $arrXajax = explode("\n",$xajax->getJavascript());

		//TODO: use wrapper when all js done
		/*
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
		 */
		if($user->pageAccess == true) {
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
		$arrMenuItems = FSystem::topMenu();
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
			$banner = FSystem::grndbanner();
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
					if(!empty($menuItem['ID'])) $tpl->setVariable('LOID',$menuItem['ID']);
					if(!empty($menuItem['CLASS'])) $tpl->setVariable('CLASS',$menuItem['CLASS']);
					if(isset($menuItem['ACTIVE'])) $tpl->touchBlock('secondary-menu-activelink');
					if(isset($menuItem['OPPOSITE']))  $tpl->touchBlock('secondary-menu-oppositebutton');
					$tpl->parseCurrentBlock();
				}
			}

		}
		
		//---LEFT PANEL POPULATING
		$fLeftpanel = new FLeftPanel($user->pageVO->pageId, $user->userVO->userId, $user->pageVO->typeId);
		$fLeftpanel->load();
		$fLeftpanel->show();

		//---FOOTER INFO
		$cache = FCache::getInstance('l');
		$start = $cache->getData('start','debug');

		$pagesSum = FDBTool::getOne("select sum(hit) from sys_users", 'tCounter', 'default', 's', 0);
		$tpl->setVariable("COUNTER", $pagesSum.'::'.((isset($debugTime))?('<strong>'.$debugTime.'</strong>::'):('')).round((FSystem::getmicrotime()-$start),3));

		//---user tooltips - one per user avatar displayed
		$ttips = '';
		$cache = FCache::getInstance('l');
		if(($arrUserAvatarTips = $cache->getGroup('UavatarTip')) !==false ) $ttips .= implode("\n", $arrUserAvatarTips);
		$tpl->setVariable('USERTOOLTIPS',$ttips);

		//--- last check
		//--- js and css included just when needed
		$useDatePicker = false;
		$useMarkItUp = false;
		$useDomTabs = false;
		$useSlimbox = false;
		$useUploadify = false;
		$useSupernote = false;
		$useFajaxform = false;
		foreach ($tpl->blockdata as $item) {
			if(strpos($item, 'datepicker') !== false) { $useDatePicker = true; }
			if(strpos($item, 'markItUp') !== false || strpos($item, 'toggleToolSize') !== false) { $useMarkItUp = true; }
			if(strpos($item, 'lightbox') !== false) { $useSlimbox = true; }
			if(strpos($item, 'uploadify') !== false) { $useUploadify = true; }
			if(strpos($item, 'domtabs') !== false) { $useDomTabs = true; }
			if(strpos($item, 'supernote-') !== false) { $useSupernote = true; }
			if(strpos($item, 'fajaxform') !== false) { $useFajaxform = true; }
		}
		
		if($useDatePicker === true) {
			$tpl->touchBlock("juiHEAD"); //---js in the header
			$tpl->touchBlock("juiEND"); //---javascript on the end of the page
			$tpl->touchBlock("datepickerEND"); //---javascript on the end of the page
		}
		if($useMarkItUp === true) {
			$tpl->touchBlock("markitupHEAD");
			$tpl->touchBlock("markitupEND");
		}
		if($useSlimbox === true) {
			$tpl->touchBlock("slimboxHEAD");
			$tpl->touchBlock("slimboxEND");
		}
		if($useUploadify === true) {
			$tpl->touchBlock("uploadifyHEAD");
			$tpl->touchBlock("uploadifyEND");
		}
		if($useDomTabs === true) {
			$tpl->touchBlock("domtabsEND");
		}
		if($useSupernote === true) {
			$tpl->touchBlock("supernoteEND");
		}
		if($useFajaxform === true) {
			$tpl->touchBlock("fajaxformEND");
		}

		//---PRINT PAGE
		header("Content-Type: text/html; charset=".CHARSET);
		$tpl->show();
	}
}