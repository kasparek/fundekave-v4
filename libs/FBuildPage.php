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
		if($pageIdTop!=$user->pageVO->pageId) {
			$pageTop = new PageVO($pageIdTop);
		} else {
			$pageTop = $user->pageVO;
		}
		if($pageTop->pageId) {
			$homesite = $pageTop->prop('homesite');
			if(strpos($pageTop->prop('homesite'),'http:')===false) $homesite = 'http://'.$homesite;
			$breadcrumbs[] = array('name'=>str_replace('http://','',$homesite),'url'=>$homesite);
		}

		//typ
		$typeId = isset(FLang::$TYPEID[$user->pageVO->typeId]) ? $user->pageVO->typeId : '';
		$typeId = isset(FLang::$TYPEID[$user->pageParam]) ? $user->pageParam : $typeId; 
		if(!empty($typeId)) {
			//TODO:refactor - cache???
			//prehled
			$breadcrumbs[] = array('name'=>FDBTool::getOne("select text from sys_menu where pageId='foall'"),'url'=>FSystem::getUri('','foall',''));
			//typ
			$breadcrumbs[] = array('name'=>FLang::$TYPEID[$typeId],'url'=>FSystem::getUri('','foall',$typeId));
			//category
			if($user->pageVO->categoryId > 0) {
				$categoryArr = FCategory::getCategory($user->pageVO->categoryId);
				if(!empty($categoryArr))
					$breadcrumbs[] = array('name'=>$categoryArr[2],'url'=>FSystem::getUri('c='.$user->pageVO->categoryId,'foall',''));
			}
		}

		if(!empty($_REQUEST['date'])) $date = FSystem::checkDate($_REQUEST['date']);
		if(!empty($date)) $breadcrumbs[] = array('name'=>date(FConf::get('internationalization','date'),strtotime($date)),'url'=>FSystem::getUri('date='.$date));

		//stranka
		if(!empty($user->pageVO->name)) $breadcrumbs[] = array('name'=>$user->pageVO->name,'url'=>FSystem::getUri('',$user->pageVO->pageId,''));

		if($user->itemVO) $categoryId = $user->itemVO->categoryId;
		if(!empty($_REQUEST['c'])) $categoryId = (int) $_REQUEST['c'];
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
	}

	static function getTabs() {
	 return self::$tabsArr;
	}

	static function process( $data ) {
		$user = FUser::getInstance();
		if($user->pageAccess == true) {
			if($user->pageParam=='sa' || $user->pageParam == 'e') $template = 'page_PageEdit';
			else $template = $user->pageVO->template;
			if($template != '') {
				if (!preg_match("/(.html)$/",$template)) {
					if( class_exists($template) ) {
						call_user_func(array($template, 'process'),$data);
					}
				}
			}
		}
	}

	static function baseContent( $data ) {
		FProfiler::write('FBuildPage::baseContent--START');
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
					$template='';
					$user->pageVO->content = FSystem::postText($user->pageVO->prop('home'));
					if(empty($user->pageVO->content)) $user->pageVO->content = FLang::$MESSAGE_FORUM_HOME_EMPTY;
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
			FProfiler::write('FBuildPage::baseContent--TPL READY');
			if($template != '') {
				if ($staticTemplate === false) {
					//DYNAMIC TEMPLATE
					FProfiler::write('FBuildPage::baseContent--TPL LOADED');
					if( class_exists($template) ) {
						call_user_func(array($template, 'build'),$data);
					}
					FProfiler::write('FBuildPage::baseContent--TPL PROCESSED');
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
			FProfiler::write('FBuildPage::baseContent--CONTENT DONE');
			//DEFAULT TLACITKA - pro typy - galery, blog, forum
			$pageId = $user->pageVO->pageId;

			if($user->pageVO->typeId == 'forum' && $user->pageParam!='h') {
				$homePage = $user->pageVO->prop('home');
				if(!empty($homePage)) {
					FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'h'), FLang::$LABEL_HOME);
				}
			}

			if($user->idkontrol==true && ($staticTemplate==true || $user->pageVO->typeId == 'forum' || $user->pageVO->typeId == 'galery' || $user->pageVO->typeId == 'blog')) {
				if(empty($user->pageParam)) {
					if($user->pageVO->userIdOwner != $user->userVO->userId) {
						FMenu::secondaryMenuAddItem(FSystem::getUri('m=user-book&d=page:'.$pageId), ((0 == $user->pageVO->favorite)?(FLang::$LABEL_BOOK):(FLang::$LABEL_UNBOOK)), array('id'=>'bookButt','class'=>'fajaxa'));
					}

					if(FRules::getCurrent(2)) {
						FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'e'),FLang::$LABEL_SETTINGS);
					}
				}
				//TODO:refactor and use again
				//FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'p'), FLang::$LABEL_POLL);
				//FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'s'), FLang::$LABEL_STATS);
			}
			//SUPERADMIN access - tlacitka na nastaveni stranek
			if(FRules::get($user->userVO->userId,'sadmi',2)) {
				FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'sa'),FLang::$BUTTON_PAGE_SETTINGS,array('parentClass'=>'opposite'));
			}

			FProfiler::write('FBuildPage::baseContent--BUTTONS ADDED');
			/**/
		}
	}

	static function show( $data ) {

		FBuildPage::baseContent( $data );
		FProfiler::write('FBuildPage--FBuildPage::baseContent');

		$tpl = FBuildPage::getInstance();

		$tabsArr = FBuildPage::getTabs();
		if($tabsArr) {
			foreach($tabsArr as $tab) {
				$tpl->setCurrentBlock('content');
				foreach ($tab as $k=>$v)  {
					if($v!='') $tpl->setVariable($k, $v);
				}
				$tpl->parseCurrentBlock();
			}
		}

		$user = FUser::getInstance();
		//---ERROR MESSAGES
		$arrMsg = FError::get();
		if(!empty($arrMsg)){
			foreach ($arrMsg as $k=>$v) {
				$tpl->setVariable("ERRORMSG", $k . (($v>1)?(' ['.$v.']'):('')) );
				$tpl->parse("errormsg");
			}
			FError::reset();
		}
		$arrMsg = FError::get(1);
		if(!empty($arrMsg)){
			foreach ($arrMsg as $k=>$v) {
				$tpl->setVariable("OKMSG", $k . (($v>1)?(' ['.$v.']'):('')) );
				$tpl->parse("okmsg");
			}
			FError::reset(1);
		}
		//---HEADER
		$tpl->setVariable("CHARSET", FConf::get('internationalization','charset'));
		$tpl->setVariable("ASSETS_URL", URL_ASSETS);
		$tpl->setVariable("GOOGLEID", GOOGLE_ANAL_ID);

		$tpl->setVariable("CLIENT_WIDTH", $user->userVO->clientWidth*1);
		$tpl->setVariable("CLIENT_HEIGHT", $user->userVO->clientHeight*1);

		//searchform
		$tpl->setVariable("SEARCHACTION", FSystem::getUri('','searc','',array('short'=>true)));

		$tpl->setVariable("TITLE", FBuildPage::getTitle());
		if($user->pageVO) {
			$pageIdTop = $user->pageVO->pageIdTop ? $user->pageVO->pageIdTop : HOME_PAGE;
			$pageVOTop = new PageVO($pageIdTop);
			$tpl->setVariable("HOMESITE", $pageVOTop->prop('homesite'));
			if($user->pageVO->pageIdTop!=$user->pageVO->pageId) $tpl->setVariable('RSSPAGEID',$user->pageVO->pageId);
			if(!empty($user->pageVO->description)) $tpl->setVariable("DESCRIPTION", str_replace('"','',$user->pageVO->description));
		}
		if(false!==($pageHeading=FBuildPage::getHeading())) if(!empty($pageHeading)) $tpl->setVariable('PAGEHEAD',$pageHeading);
		//---BODY PARAMETERS
		//---MAIN MENU - cached rendered
		$cache = FCache::getInstance($user->idkontrol?'s':'f',0);
		$menu = $cache->getData('menu'.HOME_PAGE,'main');
		
		if($menu===false) {
			$arrMenuItems = FMenu::topMenu();
			while($arrMenuItems) {
				$menuItem = array_shift($arrMenuItems);
				$tpl->setVariable('LINK',$menuItem['LINK']);
				$tpl->setVariable('TEXT',$menuItem['TEXT']);
				//if($menuItem['pageId']==$user->pageVO->pageId) {  $tpl->touchBlock('topmenuactivelink'); }
				$tpl->parse("topmenuitem");
			}
			$tpl->parse('menu');
			$menu = $tpl->get('menu');
			$cache->setData($menu);
		} else {
			$tpl->setVariable("CACHEDMENU", $menu);
		}
		
		FProfiler::write('FBuildPage--FSystem::topMenu');

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
			$lomenuItems = FMenu::secondaryMenu();
			if(!empty($lomenuItems)) {
				foreach($lomenuItems as $menuItem) {
					$tpl->setVariable('LOLINK',$menuItem['LINK']);
					$tpl->setVariable('LOTEXT',$menuItem['TEXT']);
					$options = $menuItem['options'];
					if(isset($options['id'])) $tpl->setVariable('LOID',$options['id']);
					if(isset($options['class'])) $tpl->setVariable('CLASS',$options['class']);
					if(isset($options['title'])) $tpl->setVariable('LOTITLE',$options['title']);
					if(isset($options['parentClass'])) $tpl->setVariable('LISTCLASS',$options['parentClass']);
					$tpl->parse('secondary-menu-item');
				}
			}
		}
		FProfiler::write('FBuildPage--FSystem::secondaryMenu');

		//---LEFT PANEL POPULATING
		$showSidebar = true;
		if($user->pageVO) $showSidebar=$user->pageVO->showSidebar;
		if($showSidebar) {
			$fLeftpanel = new FLeftPanel(($user->pageVO)?($user->pageVO->pageId):(''), $user->userVO->userId, ($user->pageVO)?( $user->pageVO->typeId ):(''));
			$fLeftpanel->load();
			$fLeftpanel->show();
			FProfiler::write('FBuildPage--FLeftPanel');
		}

		//--- last check
		//--- js and css included just when needed
		$useDatePicker = false;
		$useTabs = false;
		$useSwfobject = false;
		$useFuup = false;
		$useMaps = false;

		foreach ($tpl->blockdata as $item) {
			if(strpos($item, 'datepicker') !== false) { $useDatePicker = true; }
			if(strpos($item, 'fuup') !== false) { $useSwfobject = true; $useFuup=true; }
			if(strpos($item, 'tabs') !== false) { $useTabs = true; }
			if(strpos($item, 'mapSmall') !== false) { $useMaps = true; }
		}

		if($useDatePicker === true) {
			$tpl->touchBlock("juiCSS"); //---js in the header
			$tpl->touchBlock("juiLoad"); //---javascript on the end of the page

			$tpl->touchBlock("datepickerLoad"); //---javascript on the end of the page
			$tpl->touchBlock("datepickerInit");
		}
		if($useSwfobject === true) {
			$tpl->touchBlock("swfoLoad");
		}
		if($useFuup === true) {
			$tpl->touchBlock("fuupInit");
		}
		if($useTabs === true) {
			$tpl->touchBlock("juiCSS"); //---js in the header
			$tpl->touchBlock("juiLoad"); //---javascript on the end of the page
			$tpl->touchBlock("tabsInit");
		}
		if($user->idkontrol===true) {
			$tpl->touchBlock("signedInit");
		}
		if($useMaps===true) {
			$tpl->touchBlock("mapsLoad");
		}

		//post messages
		if($user->userVO->hasNewMessages()) {
			$tpl->setVariable('NEWPOST',$user->userVO->newPost);
			$tpl->setVariable('NEWPOSTFROMNAME',$user->userVO->newPostFrom);
		} else {
			$tpl->touchBlock('msgHidden');
		}

		FProfiler::write('FBuildPage--custom js sections');
		//---PRINT PAGE
		header("Content-Type: text/html; charset=".FConf::get('internationalization','charset'));

		$data = $tpl->get();
		//replace super variables
		$data = FSystem::superVars($data);
		
		//$data = preg_replace('/\s\s+/', ' ', $data);
		$data = str_replace("\t", '', $data);
				
		echo $data;
	}
}