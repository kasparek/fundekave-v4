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
		'events.edit.php'=>'page_EventsEdit',
		
     'forum.view.php'=>'page_ForumView',
     'bloged.main.php'=>'page_Main',
	   
		'pages.booked.php'=>'page_PagesBooked',
     'pages.list.php'=>'page_PagesList',
		'pages.search.php'=>'page_PagesSearch',
		'pages.stat.php'=>'page_PagesStat',
		'pages.poll.php'=>'page_PagePoll',
		'page.new.simple.php'=>'page_PageNewSimple',
		'page.edit.php'=>'page_PageEdit',

		'galery.list.php'=>'page_GaleryList',
	   'galery.detail.php'=>'page_GaleryDetail',
	   
	   'items.live.php'=>'page_ItemsLive',
		'items.search.php'=>'page_ItemsSearch',
		'items.tags.php'=>'page_ItemsTags',
		'items.tagging.randoms.php'=>'page_ItemsTaggingRandom',
	   
	   'user.post.php'=>'page_UserPost',
		'user.friends.php'=>'page_UserFriends',
		'user.friends.all.php'=>'page_FriendsAll',
		'user.surf.php'=>'page_UserSurf',
		'user.settings.php'=>'page_UserSettings',
		'user.info.php'=>'page_UserInfo',
		'user.diary.php'=>'page_UserDiary',
		
		'registration.php'=>'page_Registration'
		
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
		FSystem::profile('FBuildPage::baseContent--START');
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
			FSystem::profile('FBuildPage::baseContent--TPL READY');
			if($template != '') {
				if ($staticTemplate === false) {
					//DYNAMIC TEMPLATE
					$template = FBuildPage::getTemplate($template);
					FSystem::profile('FBuildPage::baseContent--TPL LOADED');
					if( class_exists($template) ) {
						$c = new $template;
						$c->build();
					}
					FSystem::profile('FBuildPage::baseContent--TPL PROCESSED');
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
			FSystem::profile('FBuildPage::baseContent--CONTENT DONE');
	
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
						FSystem::secondaryMenuAddItem(FUser::getUri('m=user-book&d=page:'.$pageId), ((0 == $user->pageVO->favorite)?(FLang::$LABEL_BOOK):(FLang::$LABEL_UNBOOK)), 0, 'bookButt','fajaxa');
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
			FSystem::profile('FBuildPage::baseContent--BUTTONS ADDED');
			/**/
		}
	}

	static function show() {
		
		FBuildPage::baseContent();
		FSystem::profile('FBuildPage--FBuildPage::baseContent');

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
		//---MAIN MENU
		$arrMenuItems = FSystem::topMenu();
			while($arrMenuItems) {
			 $menuItem = array_shift($arrMenuItems);
				$tpl->setCurrentBlock("topmenuitem");
				$tpl->setVariable('LINK',$menuItem['LINK']);
				$tpl->setVariable('TEXT',$menuItem['TEXT']);
				if($menuItem['pageId']==$user->pageVO->pageId) {  $tpl->touchBlock('topmenuactivelink'); }
				$tpl->parseCurrentBlock();
			}
		FSystem::profile('FBuildPage--FSystem::topMenu');

		//---BANNER
		if(!isset($_GET['nobanner'])) {
			$banner = FSystem::grndbanner();
			if(!empty($banner)) {
				$tpl->setVariable("BANNER",$banner);
				$tpl->touchBlock('hasMainBanner');
			}
		}
		FSystem::profile('FBuildPage--FSystem::grndbanner');

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
					if(isset($menuItem['OPPOSITE']))  $tpl->touchBlock('secondary-menu-oppositebutton');
					$tpl->parseCurrentBlock();
				}
			}
		}
		FSystem::profile('FBuildPage--FSystem::secondaryMenu');
		
		//---LEFT PANEL POPULATING
		$fLeftpanel = new FLeftPanel($user->pageVO->pageId, $user->userVO->userId, $user->pageVO->typeId);
		$fLeftpanel->load();
		$fLeftpanel->show();
		FSystem::profile('FBuildPage--FLeftPanel');

		//---FOOTER INFO
		$cache = FCache::getInstance('l');
		$cachedArr = $cache->getData('profile','FSystem');
		$start = $cachedArr[0]['time'];

		$pagesSum = FDBTool::getOne("select sum(hit) from sys_users", 'tCounter', 'default', 's', 0);
		$tpl->setVariable("COUNTER", $pagesSum.'::'.round((FSystem::getmicrotime()-$start),3));
		FSystem::profile('FBuildPage--footer');
		//---user tooltips - one per user avatar displayed
		$ttips = '';
		$cache = FCache::getInstance('l');
		if(($arrUserAvatarTips = $cache->getGroup('UavatarTip')) !==false ) $ttips .= implode("\n", $arrUserAvatarTips);
		$tpl->setVariable('USERTOOLTIPS',$ttips);
		FSystem::profile('FBuildPage--user tooltips');
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
		FSystem::profile('FBuildPage--custom js sections');
		//---PRINT PAGE
		header("Content-Type: text/html; charset=".CHARSET);
		$tpl->show();
	}
}