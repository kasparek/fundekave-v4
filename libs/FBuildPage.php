<?php
class FBuildPage {
	private static $instance;
	private static $tabsArr;

	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = FSystem::tpl( TPL_MAIN );
		}
		return self::$instance;
	}

	static function getTitle() {
		$user = FUser::getInstance();
		if($user->pageVO) {
			$pageTitle = $user->pageVO->htmlName ? $user->pageVO->htmlName : $user->pageVO->name;
			return (!empty($pageTitle)?($pageTitle.' - '):('')).(!empty($user->pageVO->htmlTitle)?($user->pageVO->htmlTitle.' - '):('')).BASEPAGETITLE;
		}
	}
	static function getHeading() {
		$user = FUser::getInstance();
		if($user->pageVO) {
			if($user->pageVO->showHeading===false) return '';
			if(!empty($user->pageVO->htmlName)) {
				return $user->pageVO->htmlName;
			} else if(empty($user->pageVO->name)) {
				return false;
			} else {
				return $user->pageVO->name;
			}
		}
	}

	static function getBreadcrumbs() {
		$user = FUser::getInstance();
		
		$breadcrumbs = array();
		//breadcrumbs
		$pageIdTop = $user->pageVO->pageIdTop ? $user->pageVO->pageIdTop : HOME_PAGE;
		$pageTop = new PageVO($pageIdTop,true);
		if($pageTop->pageId) {
			$homesite = $pageTop->prop('homesite');
			if(strpos($pageTop->prop('homesite'),'http:')===false) $homesite = 'http://'.$homesite;
			$breadcrumbs[] = array('name'=>$pageTop->name,'url'=>$homesite);
		}

		if($pageTop->pageId!=$user->pageVO->pageId) {
			//typ
			if(isset(FLang::$TYPEID[$user->pageVO->typeId])) {
				$pages = new FPages('top',$user->userVO->userId,1);
				$pages->setSelect('p.pageId');
				$pages->addWhere("p.typeIdChild='".$user->pageVO->typeId."' and public=1");
				$arr = $pages->getContent();
				if(!empty($arr)) {
					$breadcrumbs[] = array('name'=>FLang::$TYPEID[$user->pageVO->typeId],'url'=>FSystem::getUri('',$arr[0]->pageId,''));
				}
					
				if($user->pageVO->categoryId > 0) {
					$categoryArr = FCategory::getCategory($user->pageVO->categoryId);
					$breadcrumbs[] = array('name'=>$categoryArr[2],'url'=>FSystem::getUri('c='.$user->pageVO->categoryId,$arr[0]->pageId,''));
				}

			}

			//stranka
			$breadcrumbs[] = array('name'=>$user->pageVO->name,'url'=>FSystem::getUri('',$user->pageVO->pageId,''));
		}

		if($user->itemVO) {
			$categoryId = $user->itemVO->categoryId;
		}
		if(!empty($_REQUEST['c'])) {
			$categoryId = (int) $_REQUEST['c'];
		}

		if(!empty($categoryId)) {
			$categoryArr = FCategory::getCategory($categoryId);
			$breadcrumbs[] = array('name'=>$categoryArr[2],'url'=>FSystem::getUri('c='.$categoryId,$user->pageVO->pageId,''));
		}

		if($user->itemVO) {
			$itemName = $user->itemVO->addon;
			if(!empty($user->itemVO->htmlName)) $itemName = $user->itemVO->htmlName;
			if(!empty($itemName)) {
				$breadcrumbs[] = array('name'=>$itemName,'url'=>FSystem::getUri('i='.$user->itemVO->itemId));
			}
		}

		if($user->whoIs>0) {
			$breadcrumbs[] = array('name'=>FUser::getgidname($user->whoIs),'url'=>FSystem::getUri('who='.$user->whoIs));
		}

		unset($breadcrumbs[count($breadcrumbs)-1]['url']);
		return $breadcrumbs;
	}

	static function addTab($arrVars) {
		self::$tabsArr[] = $arrVars;
		//$tpl = FBuildPage::getInstance();
		//$tpl->addTab($arrVars);
	}
	static function getTabs() {
	 return self::$tabsArr;
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

		'galery.list.php'=>'page_PagesList',
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
		$user = FUser::getInstance();
		if($user->pageAccess == true) {
			if($user->pageParam=='sa' || $user->pageParam == 'e') $template = 'page_PageEdit';
			else $template = $user->pageVO->template;
			if($template != '') {
				if (!preg_match("/(.html)$/",$template)) {
					$template = FBuildPage::getTemplate($template);
					if( class_exists($template) ) {
						call_user_func(array($template, 'process'),$data);
					}
				}
			}
		}
	}

	static function baseContent() {
		FProfiler::profile('FBuildPage::baseContent--START');
		$tpl = FBuildPage::getInstance();
		$user = FUser::getInstance();

		if($user->pageAccess == true) {

			$staticTemplate = false;

			switch($user->pageParam) {
				case 'sa':
				case 'e':
					$template = 'page_PageEdit';
					break;
					
					/* calendar for events linked to page - forum/blog */
				case 'k':
					$template = 'page_ForumView';
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
					else $homePage = FSystem::postText($homePage);
					$template='';
					$user->pageVO->content = FSystem::postText($homePage);
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
			FProfiler::profile('FBuildPage::baseContent--TPL READY');
			if($template != '') {
				if ($staticTemplate === false) {
					//DYNAMIC TEMPLATE
					$template = FBuildPage::getTemplate($template);
					FProfiler::profile('FBuildPage::baseContent--TPL LOADED');
					if( class_exists($template) ) {
						call_user_func(array($template, 'build'));
					}
					FProfiler::profile('FBuildPage::baseContent--TPL PROCESSED');
				} else {
					//STATIC TEMPLATE
					$tpl = FSystem::tpl($template);
					FSystem::tplParseBlockFromVars( $tpl, $user->pageVO );
					FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
					unset($tpl);
				}
			} else {
				//NOT TEMPLATE AT ALL
				FBuildPage::addTab(array("MAINDATA"=>$user->pageVO->content));
			}

			FProfiler::profile('FBuildPage::baseContent--CONTENT DONE');

			//DEFAULT TLACITKA - pro typy - galery, blog, forum
			$pageId = $user->pageVO->pageId;

			if(!empty($user->pageParam) || $user->itemVO) {
				if($user->itemVO) $typeId = $user->itemVO->typeId; else $typeId = '';
				if($typeId!='galery' && $typeId!='forum' && $user->pageParam!='a') FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,''),FLang::$BUTTON_PAGE_BACK);
			}

			if($user->pageVO->typeId == 'forum' && $user->pageParam!='h') {
				$homePage = $user->pageVO->getPageParam('home');
				if(!empty($homePage)) {
					FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'h'), FLang::$LABEL_HOME);
				}
			}

			if($user->idkontrol==true && ($staticTemplate==true || $user->pageVO->typeId == 'forum' || $user->pageVO->typeId == 'galery' || $user->pageVO->typeId == 'blog')) {
				if(empty($user->pageParam)) {
					if($user->pageVO->userIdOwner != $user->userVO->userId) {
						FMenu::secondaryMenuAddItem(FSystem::getUri('m=user-book&d=page:'.$pageId), ((0 == $user->pageVO->favorite)?(FLang::$LABEL_BOOK):(FLang::$LABEL_UNBOOK)), 0, 'bookButt','fajaxa');
					}
					//if(FCong::get('pocket','enabled')==1) FMenu::secondaryMenuAddItem(FSystem::getUri('m=user-pocketIn&d=page:'.$pageId), FLang::$LABEL_POCKET_PUSH, 0, '', 'fajaxa');
					if(FRules::getCurrent(2)) {

						FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'e'),FLang::$LABEL_SETTINGS);

					}
				}
				//FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'p'), FLang::$LABEL_POLL);
				//FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'s'), FLang::$LABEL_STATS);
			}
			//SUPERADMIN access - tlacitka na nastaveni stranek
			if(FRules::get($user->userVO->userId,'sadmi',2)) {
				FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'sa'),FLang::$BUTTON_PAGE_SETTINGS,1);
			}
						
			FProfiler::profile('FBuildPage::baseContent--BUTTONS ADDED');
			/**/
		}
	}

	static function show() {

		FBuildPage::baseContent();
		FProfiler::profile('FBuildPage--FBuildPage::baseContent');

		$tpl = FBuildPage::getInstance();

		$tabsArr = FBuildPage::getTabs();
		if($tabsArr) {
			foreach($tabsArr as $tab) {
				$tpl->setCurrentBlock('maincontent-recurrent');
				foreach ($tab as $k=>$v)  {
					if($v!='') $tpl->setVariable($k, $v);
				}
				if(!empty($tab['TABID']) && !empty($tab['TABNAME'])) {
					$tpl->touchBlock('tabidclose');
				}
				$tpl->parseCurrentBlock();
			}
		}

		$user = FUser::getInstance();
		//---ERROR MESSAGES
		$arrMsg = FError::getError();
		if(!empty($arrMsg)){
			foreach ($arrMsg as $k=>$v) {
				$tpl->setVariable("ERRORMSG", $k . (($v>1)?(' ['.$v.']'):('')) );
				$tpl->parse("errormsg");
			}
			FError::resetError();
		}
		$arrMsg = FError::getError(1);
		if(!empty($arrMsg)){
			foreach ($arrMsg as $k=>$v) {
				$tpl->setVariable("OKMSG", $k . (($v>1)?(' ['.$v.']'):('')) );
				$tpl->parse("okmsg");
			}
			FError::resetError(1);
		}
		//---HEADER
		$cssPath = FSystem::getSkinCSSFilename();
		$tpl->setVariable("CSSSKIN", $cssPath);
		$tpl->setVariable("CHARSET", CHARSET);
		$tpl->setVariable("URL_JS", URL_JS);
		$tpl->setVariable("ASSETS_URL", ASSETS_URL);
		$tpl->setVariable("GOOGLEID", GOOGLE_ANAL_ID);
		
		$tpl->setVariable("CLIENT_WIDTH", $user->userVO->clientWidth);
		$tpl->setVariable("CLIENT_HEIGHT", $user->userVO->clientHeight);

		//searchform
		$tpl->setVariable("SEARCHACTION", FSystem::getUri('','searc',''));
		$tpl->setVariable("SEARCHCSSDIR",$cssPath);

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

		$tpl->setVariable("TITLE", FBuildPage::getTitle());
		if($user->pageVO) {
			$pageIdTop = $user->pageVO->pageIdTop ? $user->pageVO->pageIdTop : HOME_PAGE;
			$pageVOTop = new PageVO($pageIdTop,true);
			$tpl->setVariable("HOMESITE", $pageVOTop->prop('homesite'));
			if($user->pageVO->pageIdTop!=$user->pageVO->pageId) $tpl->setVariable('RSSPAGEID',$user->pageVO->pageId);
			if(!empty($user->pageVO->description)) $tpl->setVariable("DESCRIPTION", str_replace('"','',$user->pageVO->description));
		}
		if(false!==($pageHeading=FBuildPage::getHeading())) $tpl->setVariable('PAGEHEAD',$pageHeading);
		//---BODY PARAMETERS
		//---MAIN MENU
		$arrMenuItems = FMenu::topMenu();
		while($arrMenuItems) {
			$menuItem = array_shift($arrMenuItems);
			$tpl->setCurrentBlock("topmenuitem");
			$tpl->setVariable('LINK',$menuItem['LINK']);
			$tpl->setVariable('TEXT',$menuItem['TEXT']);
			//if($menuItem['pageId']==$user->pageVO->pageId) {  $tpl->touchBlock('topmenuactivelink'); }
			$tpl->parseCurrentBlock();
		}
		FProfiler::profile('FBuildPage--FSystem::topMenu');

		//---BANNER
		/*
		 if(!isset($_GET['nobanner'])) {
			$banner = FBanner::getBanner();
			if(!empty($banner)) {
			$tpl->setVariable("BANNER",$banner);
			$tpl->touchBlock('hasMainBanner');
			}
			}
			FProfiler::profile('FBuildPage--FSystem::grndbanner');
			*/
		if($user->pageAccess === true) {
				
			//breadcrumbs
			$breadcrumbs = FBuildPage::getBreadcrumbs();
			foreach($breadcrumbs as $crumb) {
				$tpl->setVariable('BREADNAME',$crumb['name']);
				if(isset($crumb['url'])) {
					$tpl->setVariable('BREADURL',$crumb['url']);
					$tpl->touchBlock('breadlinkend');
				}
				$tpl->parse('bread');
			}

			//---SECONDARY MENU
			$lomenuItems = FMenu::secondaryMenu($user->pageVO->pageId);
			if(!empty($lomenuItems)) {
				foreach($lomenuItems as $menuItem) {
					$tpl->setVariable('LOLINK',$menuItem['LINK']);
					$tpl->setVariable('LOTEXT',$menuItem['TEXT']);
					if(!empty($menuItem['ID'])) $tpl->setVariable('LOID',$menuItem['ID']);
					if(!empty($menuItem['CLASS'])) $tpl->setVariable('CLASS',$menuItem['CLASS']);
					if(isset($menuItem['LISTCLASS']))  $tpl->setVariable('LISTCLASS',$menuItem['LISTCLASS']);
					if(isset($menuItem['TITLE']))  $tpl->setVariable('LOTITLE',$menuItem['TITLE']);
					$tpl->parse('secondary-menu-item');
				}
			}
		}
		FProfiler::profile('FBuildPage--FSystem::secondaryMenu');

		//---LEFT PANEL POPULATING
		$fLeftpanel = new FLeftPanel(($user->pageVO)?($user->pageVO->pageId):(''), $user->userVO->userId, ($user->pageVO)?( $user->pageVO->typeId ):(''));
		$fLeftpanel->load();
		$fLeftpanel->show();
		FProfiler::profile('FBuildPage--FLeftPanel');
		$fLeftpanel = false;


		//---FOOTER INFO
		$cache = FCache::getInstance('l');
		$cachedArr = $cache->getData('profile','FSystem');
		$start = $cachedArr[0]['time'];

		//$pagesSum = FDBTool::getOne("select sum(hit) from sys_users", 'tCounter', 'default', 's', 0); $pagesSum.'::'.
		$tpl->setVariable("COUNTER", round((FProfiler::getmicrotime()-$start),3));
		FProfiler::profile('FBuildPage--footer');
		//---user tooltips - one per user avatar displayed
		$ttips = '';
		$cache = FCache::getInstance('l');
		if(($arrUserAvatarTips = $cache->getGroup('UavatarTip')) !==false ) $ttips .= implode("\n", $arrUserAvatarTips);
		$tpl->setVariable('USERTOOLTIPS',$ttips);
		FProfiler::profile('FBuildPage--user tooltips');
		//--- last check
		//--- js and css included just when needed
		$useDatePicker = false;
		$useTabs = false;
		$useSlimbox = false;
		$useSupernote = false;
		$useFajaxform = false;
		$useSwfobject = false;
		$useFuup = false;
		$useBBQ = false;
		foreach ($tpl->blockdata as $item) {
			if(strpos($item, 'datepicker') !== false) { $useDatePicker = true; }
			if(strpos($item, 'lightbox') !== false) { $useSlimbox = true; }
			if(strpos($item, 'fuup') !== false) { $useSwfobject = true; $useFuup=true; }
			if(strpos($item, 'tabs') !== false) { $useTabs = true; }
			//			if(strpos($item, 'supernote-') !== false) { $useSupernote = true; }
			if(strpos($item, 'fajaxform') !== false) { $useFajaxform = true; }
			if(strpos($item, 'fajaxa') !== false && strpos($item, 'hash') !== false) { $useBBQ = true; }
		}

		if($useDatePicker === true) {
			$tpl->touchBlock("juiHEAD"); //---js in the header
			$tpl->setVariable('JUI_URL_JS',URL_JS);
			$tpl->touchBlock("juiEND"); //---javascript on the end of the page
			$tpl->setVariable('DATEPICKER_URL_JS',URL_JS);
			$tpl->touchBlock("datepickerEND"); //---javascript on the end of the page
		}
		if($useSlimbox === true) {
			$tpl->touchBlock("slimboxHEAD");
			$tpl->setVariable('SLIMBOX_URL_JS',URL_JS);
			$tpl->touchBlock("slimboxEND");
		}
		if($useSwfobject === true) {
			$tpl->setVariable('SWFO_URL_JS',URL_JS);
			$tpl->touchBlock("swfo");
		}
		if($useFuup === true) {
			$tpl->touchBlock("fuup");
		}
		if($useTabs === true) {
			$tpl->touchBlock("juiHEAD"); //---js in the header
			$tpl->setVariable('TABS_URL_JS',URL_JS);
			$tpl->touchBlock("tabsEND");
			$tpl->setVariable('JUI_URL_JS',URL_JS);
			$tpl->touchBlock("juiEND"); //---javascript on the end of the page
		}
		if($useSupernote === true) {
			$tpl->setVariable('SUPERNOTE_URL_JS',URL_JS);
			$tpl->touchBlock("supernoteEND");
		}
		if($useFajaxform === true) {
			$tpl->setVariable('FORM_URL_JS',URL_JS);
			$tpl->touchBlock("fajaxformEND");
		}
		if($useBBQ===true) {
			$tpl->setVariable('BBQ_URL_JS',URL_JS);
			$tpl->touchBlock("bbq");
		}

		if($user->idkontrol===true) $tpl->touchBlock("userin");

		FProfiler::profile('FBuildPage--custom js sections');
		//---PRINT PAGE
		header("Content-Type: text/html; charset=".CHARSET);
		$tpl->show();
	}
}