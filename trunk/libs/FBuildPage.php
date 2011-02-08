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
			$title = $user->pageVO->htmlName ? $user->pageVO->htmlName : $user->pageVO->name;
			if(!empty($user->pageVO->htmlTitle)) $pageTitle[] = $user->pageVO->htmlTitle;
			else if(!empty($title)) $pageTitle[] = $title;
			if(BASEPAGETITLE!="") $pageTitle[] = BASEPAGETITLE;
			else {
				//use top page name if BASEPAGETITLE empty
				if($user->pageVO->pageId!=HOME_PAGE) {
					$pageTitle[] = FDBTool::getOne("select name from sys_pages where pageId='".HOME_PAGE."'"); 
				}
			}
			
			return implode(" - ",$pageTitle);
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
		if($pageIdTop!=$user->pageVO->pageId) {
			//typ
			$typeId = isset(FLang::$TYPEID[$user->pageVO->typeId]) ? $user->pageVO->typeId : '';
			$typeId = isset(FLang::$TYPEID[$user->pageParam]) ? $user->pageParam : $typeId;
			$typeCrumb=false;
			$pageCrumb=true;
			$pageCategory=0;
			if(!empty($typeId)) {
				//prehled
				$breadcrumbs[] = array('name'=>FDBTool::getOne("select name from sys_pages where pageId='foall'"),'url'=>FSystem::getUri('','foall',''));
				//typ
				$typeCrumb=true;
				$pageCrumb=false;
				$breadcrumbs[] = array('name'=>FLang::$TYPEID[$typeId],'url'=>FSystem::getUri('','foall',$typeId));
					
				if($user->pageVO->categoryId > 0) $pageCategory = $user->pageVO->categoryId;
			}
			if(empty($pageCategory)) {
				if($user->categoryVO && $user->pageVO->typeId=='top') $pageCategory=$user->categoryVO->categoryId;
			}
	
			//category
			if($pageCategory>0) {
				$categoryArr = FCategory::getCategory($pageCategory);
				if(!empty($categoryArr)) {
					$pageCrumb=false;
					if($typeCrumb==false) $breadcrumbs[] = array('name'=>FLang::$TYPEID[$categoryArr[1]],'url'=>FSystem::getUri('','foall',$categoryArr[1]));
					$breadcrumbs[] = array('name'=>$categoryArr[2],'url'=>FSystem::getUri('c='.$pageCategory,'foall',$categoryArr[1]));
				}
			}
	
			if(!empty($_REQUEST['date'])) $date = FSystem::checkDate($_REQUEST['date']);
			if(!empty($date)) $breadcrumbs[] = array('name'=>date(FConf::get('internationalization','date'),strtotime($date)),'url'=>FSystem::getUri('date='.$date));
	
			if(isset(FLang::$TYPEID[$user->pageVO->typeId])) $pageCrumb=true;
			//stranka
			if(!empty($user->pageVO->name) && $pageCrumb) $breadcrumbs[] = array('name'=>$user->pageVO->name,'url'=>FSystem::getUri('',$user->pageVO->pageId,''));
	
			$itemCategory=0;
			if($user->categoryVO && $user->pageVO->typeId!='top') $itemCategory=$user->categoryVO->categoryId;
			if($user->itemVO) $itemCategory = $user->itemVO->categoryId;
			if($itemCategory>0) {
				$categoryArr = FCategory::getCategory($itemCategory);
				if(!empty($categoryArr))
				$breadcrumbs[] = array('name'=>$categoryArr[2],'url'=>FSystem::getUri('c='.$itemCategory,$user->pageVO->pageId,''));
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
			if(empty($template)) $template='page_ItemsList';
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
					FMenu::secondaryMenuAddItem(FSystem::getUri('','',''),FLang::$BUTTON_PAGE_BACK);
					$user->pageVO->content = FSystem::postText($user->pageVO->prop('home'));
					if(empty($user->pageVO->content)) $user->pageVO->content = FLang::$MESSAGE_FORUM_HOME_EMPTY;
					break;
					/* GOOgle mapi */
				case 'm':
					$template = 'page_Map';
					break;
				default:
					$template = $user->pageVO->template;
					if(empty($template) && $user->pageVO->typeId!='culture') $template='page_ItemsList';
					break;
			}
			if($template != '') {
				//DYNAMIC TEMPLATE
				FProfiler::write('FBuildPage::baseContent--TPL LOADED');
				if( class_exists($template)) call_user_func(array($template, 'build'),$data);
				FProfiler::write('FBuildPage::baseContent--TPL PROCESSED');
			} else {
				//NOT TEMPLATE AT ALL
				FBuildPage::addTab(array("MAINDATA"=>FSystem::postText($user->pageVO->content).'<div class="clearbox"></div>'));
			}
			FProfiler::write('FBuildPage::baseContent--CONTENT COMPLETE');
			//DEFAULT TLACITKA - pro typy - galery, blog, forum
			$pageId = $user->pageVO->pageId;
			if($user->pageVO->typeId == 'forum' && $user->pageParam!='h') {
				$homePage = $user->pageVO->prop('home');
				if(!empty($homePage)) {
					FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'h'), FLang::$LABEL_HOME);
				}
			}

			if($user->idkontrol==true && ($user->pageVO->typeId == 'culture' || $user->pageVO->typeId == 'forum' || $user->pageVO->typeId == 'galery' || $user->pageVO->typeId == 'blog')) {
				if(empty($user->pageParam) && empty($user->itemVO)) {
					if(FRules::getCurrent(FConf::get('settings','perm_book'))){
						if($user->pageVO->userIdOwner != $user->userVO->userId){
							FMenu::secondaryMenuAddItem(FSystem::getUri('m=user-book&d=page:'.$pageId), ((0 == $user->pageVO->favorite)?(FLang::$LABEL_BOOK):(FLang::$LABEL_UNBOOK)), array('id'=>'bookButt','class'=>'fajaxa'));
						}
					}
					if(FRules::getCurrent(2) && empty($user->itemVO)) {
						FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'e'),FLang::$LABEL_SETTINGS);
					}
				}
				//TODO:refactor classes and use again
				//FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'p'), FLang::$LABEL_POLL);
				//FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'s'), FLang::$LABEL_STATS);
			}
			//SUPERADMIN access - tlacitka na nastaveni stranek
			if(FRules::get($user->userVO->userId,'sadmi',2)) {
				FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'sa'),FLang::$BUTTON_PAGE_SETTINGS,array('parentClass'=>'opposite'));
			}
			FProfiler::write('FBuildPage::baseContent--BUTTONS ADDED');
		}
	}

	static function show( $data ) {
		FBuildPage::baseContent( $data );

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
			foreach ($arrMsg as $k=>$v) $errmsg[] = $k . (($v>1)?(' ['.$v.']'):(''));
			$tpl->setVariable("ERRORMSG",implode('<br />',$errmsg));
			FError::reset();
		}
		$arrMsg = FError::get(1);
		if(!empty($arrMsg)){
			foreach ($arrMsg as $k=>$v) $okmsg[]=$k . (($v>1)?(' ['.$v.']'):(''));
			$tpl->setVariable("OKMSG",implode('<br />',$okmsg));
			FError::reset(1);
		}
		//---HEADER
		$tpl->setVariable('HOME_PAGE', FSystem::getUri('',HOME_PAGE,''));
		$tpl->setVariable('PAGEID', $user->pageVO->pageId);
		$tpl->setVariable("CHARSET", FConf::get('internationalization','charset'));
		$tpl->setVariable("GOOGLEID", GOOGLE_ANAL_ID);

		$tpl->setVariable("CLIENT_WIDTH", $user->userVO->clientWidth*1);
		$tpl->setVariable("CLIENT_HEIGHT", $user->userVO->clientHeight*1);

		$tpl->setVariable("MSGPOLLTIME", (int) FConf::get('settings','msg_polling_time'.($user->pageVO->pageId=='fpost'?'_boosted':'')));

		//searchform
		if(!$user->pageVO->prop('hideSearchbox')) $tpl->setVariable("SEARCHACTION", FSystem::getUri('','searc','',array('short'=>true)));
		
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
		$menu = $cache->getData('menu','main');

		if($menu===false) {
			$arrMenuItems = FMenu::topMenu();
			while($arrMenuItems) {
				$menuItem = array_shift($arrMenuItems);
				$tpl->setVariable('LINK',$menuItem['LINK']);
				$tpl->setVariable('TEXT',$menuItem['TEXT']);
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
					$tpl->parse('smitem');
				}
			}
		}
		FProfiler::write('FBuildPage--FSystem::secondaryMenu');

		//---LEFT PANEL POPULATING
		$showSidebar = true;
		if($user->pageVO) {
			$showSidebar = $user->pageVO->showSidebar;
			if($showSidebar!==false) $showSidebar = !$user->pageVO->prop('hideSidebar');
		}
		if($showSidebar) {
			$fsidebar = new FSidebar(($user->pageVO)?($user->pageVO->pageId):(''), $user->userVO->userId, ($user->pageVO)?( $user->pageVO->typeId ):(''));
			$fsidebar->load();
			$fsidebar->show();
			FProfiler::write('FBuildPage--FSidebar');
		}
		$tpl->setVariable("USER",$user->idkontrol?'1':'0');
		//post messages
		if($user->userVO->hasNewMessages()) {
			$tpl->setVariable('NEWPOST',$user->userVO->newPost);
			$tpl->setVariable('NEWPOSTFROMNAME',$user->userVO->newPostFrom);
		} else {
			$tpl->touchBlock('msgHidden');
		}

		//---PRINT PAGE
		header("Content-Type: text/html; charset=".FConf::get('internationalization','charset'));

		$data = $tpl->get();
		//replace super variables
		$data = FSystem::superVars($data);
		$data = preg_replace('/\s\s+/', ' ', $data);
    FProfiler::write('FBuildPage--complete');
		echo $data;
	}
}