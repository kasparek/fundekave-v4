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
	static function getHeading() {
		$user = FUser::getInstance();
		if($user->pageVO->showHeading===false) return '';
		if(!empty($user->pageVO->htmlName)) {
			return $user->pageVO->htmlName;
		} else if(empty($user->pageVO->name)) {
			return false;
		} else {
			return $user->pageVO->name;
		}
	}

	static function getBreadcrumbs() {
		$user = FUser::getInstance();
		
		//$cacheid = 'breadcrumbs';
		//$grpid = 'page/'.$user->pageId;
		//$cache = FCache::getInstance('f');
		//$breadcrumbs = $cache->getData($cacheid,$grpid);
		$breadcrumbs=false;
		
		if($breadcrumbs===false) {
			$breadcrumbs = array();
			//breadcrumbs
			$pageIdTop = $user->pageVO->pageIdTop ? $user->pageVO->pageIdTop : HOME_PAGE;
			if($pageIdTop!=$user->pageVO->pageId) {
				$pageTop = FactoryVO::get('PageVO',$pageIdTop);
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
						$breadcrumbs[] = array('name'=>$itemName,'url'=>FSystem::getUri('i='.$user->itemVO->itemId,'',''));
					}
				}
		
				if($user->whoIs>0) {
					$breadcrumbs[] = array('name'=>FUser::getgidname($user->whoIs),'url'=>FSystem::getUri('who='.$user->whoIs));
				}
				
				if(!empty($user->pageParam)) {
					switch($user->pageParam) {
						case 'e':
						case 'sa':
							$breadcrumbs[] = array('name'=>'Nastaveni');
							break;
						case 'u':
							$breadcrumbs[] = array('name'=>'Upravit');
							break;
						case 'h':
							$breadcrumbs[] = array('name'=>'Nastenka');
							break;
						case 'm':
							$breadcrumbs[] = array('name'=>'Mapa');
							break;
						//TODO: use localization file
					}
				}
			}
			unset($breadcrumbs[count($breadcrumbs)-1]['url']);
			//$cache->setData($breadcrumbs,$cacheid,$grpid);
		}
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
			if(!preg_match("/(.html)$/",$template)) {
				if( class_exists($template) ) {
					call_user_func(array($template, 'process'),$data);
				}
			}
		}
	}

	static function baseContent( $data ) {
		FProfiler::write('FBuildPage::baseContent--START');
		$tpl = FBuildPage::getInstance();
		$user = FUser::getInstance();
		
		if(FRules::getCurrent(FConf::get('settings','perm_add_forum')))
			FMenu::secondaryMenuAddItem(FSystem::getUri('t=forum','foall','a'), FLang::$LABEL_PAGE_FORUM_NEW);
		if(FRules::getCurrent(FConf::get('settings','perm_add_blog')))
			FMenu::secondaryMenuAddItem(FSystem::getUri('t=blog','foall','a'), FLang::$LABEL_PAGE_BLOG_NEW);
		if(FRules::getCurrent(FConf::get('settings','perm_add_galery')))
			FMenu::secondaryMenuAddItem(FSystem::getUri('t=galery','foall','a'), FLang::$LABEL_PAGE_GALERY_NEW);
		
		if($user->pageAccess == true) {
			switch($user->pageParam) {
				case 'sa':
				case 'e':
					$template = 'page_PageEdit';
					break;
					/* stats */
				/*case 's':
					$template = 'page_PageStat';
					break;*/
					/* home */
				case 'h':
					$template='';
					$user->pageVO->content = FText::postProcess($user->pageVO->prop('home'));
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
				if(class_exists($template)) call_user_func(array($template, 'build'),$data);
				FProfiler::write('FBuildPage::baseContent--TPL PROCESSED');
			} else {
				//NOT TEMPLATE AT ALL
				FBuildPage::addTab(array("MAINDATA"=>FText::postProcess($user->pageVO->content).'<div class="clearfix"></div>'));
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
				
			}
			//SUPERADMIN access - tlacitka na nastaveni stranek
			if(FRules::get($user->userVO->userId,'sadmi',2)) {
				FMenu::secondaryMenuAddItem(FSystem::getUri('',$pageId,'sa'),FLang::$BUTTON_PAGE_SETTINGS);
			}
			FProfiler::write('FBuildPage::baseContent--BUTTONS ADDED');
		}
	}

	static function show( $data ) {
		$actionNum=0;
		$user = FUser::getInstance();

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
		if($user->pageVO) {
			$user->pageVO->tplVars['NUMCOLMAIN'] = 9;
			
			if(FConf::get('settings','mid_col')) {
				if(empty($user->pageParam) && ($user->pageVO->typeId=='top' || $user->pageVO->typeId=='blog') && $user->pageVO->typeIdChild != 'galery') {
					$pageListOut = page_PagesList::build(array(),array('typeId'=>'galery','return'=>true,'nopager'=>true));
					
					if(!empty($pageListOut)) {
						$user->pageVO->tplVars['MIDCOL'] = $pageListOut;
						$user->pageVO->tplVars['NUMCOLMAIN'] -= 2;
					}
				}
			}
		}
				
		if(empty($user->pageVO)) {
			FError::write_log("FBuildPage::show - missing page - pageid'".$user->pageId."'");
		}

		//---ERROR MESSAGES
		//priority bootstrap - success, info, warning, danger
		$arrMsg = FError::get();
		$outmsg = array();
		if(!empty($arrMsg)){
			foreach ($arrMsg as $k=>$v) $outmsg[] = $k . (($v>1)?(' ['.$v.']'):(''));
			foreach ($outmsg as $v) {
				$tpl->setVariable("MSGPRIORITY",'danger');
				$tpl->setVariable("MSGTEXT",$v);
				$tpl->parse('sysmsg');
			}
			FError::reset();
		}
		
		$arrMsg = FError::get(1);
		$outmsg = array();
		if(!empty($arrMsg)){
			foreach ($arrMsg as $k=>$v) $okmsg[]=$k . (($v>1)?(' ['.$v.']'):(''));
			foreach ($outmsg as $v) {
				$tpl->setVariable("MSGPRIORITY",'info');
				$tpl->setVariable("MSGTEXT",$v);
				$tpl->parse('sysmsg');
			}
			FError::reset(1);
		}
		
		//---HEADER
		$homeUrl = FSystem::getUri('',HOME_PAGE,'');
		$brandLabel = FConf::get('settings','brand_label');
		if(!empty($brandLabel)) {
			$tpl->setVariable('BRAND_URL', $homeUrl);
			$tpl->setVariable('BRAND_LABEL', $brandLabel);
		}
		$tpl->setVariable('HOME_PAGE', FSystem::getUri('',HOME_PAGE,''));
		$tpl->setVariable("CHARSET", FConf::get('internationalization','charset'));
		$tpl->setVariable("GOOGLEID", GOOGLE_ANAL_ID);
		
		if($user->idkontrol) {
			$tpl->setVariable('SIGNED_AVATAR',FAvatar::showAvatar(-1));
			$tpl->setVariable('SIGNED_NAME',$user->userVO->name);
						
			$q = "select count(1) from sys_pages_items where typeId='request' and addon='".$user->userVO->userId."'";
			$reqNum = FDBTool::getOne($q);//,'friendrequest','default','s',120);
			if($reqNum>0) {
				$tpl->setVariable('REQUESTSNUM',$reqNum);
				$actionNum += $reqNum;
			}
			
			$q = "SELECT l.userId,u.name FROM sys_users_logged as l join sys_users as u on u.userId=l.userId "
			."WHERE subdate(NOW(),interval ".USERVIEWONLINE." second)<l.dateUpdated and l.userId!='".$user->userVO->userId."' "
			."ORDER BY l.dateUpdated desc";
			if (false !== ($arrpra = FDBTool::getAll($q))) {
				if(!empty($arrpra)) {
					$tpl->setVariable('NUMFRIENDSONLINE',count($arrpra));
					foreach ($arrpra as $pra){
						$tpl->setVariable('FRIENDID',$pra[0]);
						$tpl->setVariable('FRIENDNAME',$pra[1]);
						$tpl->setVariable('FRIENDAVATAR',FAvatar::getAvatarUrl($pra[0]));
						$tpl->parse('onlineuser');
					}
				}
			}
		} else {
			$tpl->touchBlock('loginform');
		}
  
		if($user->pageVO) {
			$tpl->setVariable('PAGEID', $user->pageVO->pageId);
			$tpl->setVariable("MSGPOLLTIME", (int) FConf::get('settings','msg_polling_time'.($user->pageVO->pageId=='fpost'?'_boosted':'')));
			//searchform
			if($user->idkontrol && !$user->pageVO->prop('hideSearchbox')) $tpl->setVariable("SEARCHACTION", FSystem::getUri('','searc','',array('short'=>true)));
			$tpl->setVariable("TITLE", FBuildPage::getTitle());
			$pageIdTop = $user->pageVO->pageIdTop ? $user->pageVO->pageIdTop : HOME_PAGE;
			$pageVOTop = FactoryVO::get('PageVO',$pageIdTop);
			$tpl->setVariable("HOMESITE", $pageVOTop->prop('homesite'));
			if($user->pageVO->pageIdTop!=$user->pageVO->pageId) $tpl->setVariable('RSSPAGEID',$user->pageVO->pageId);
			if(!empty($user->pageVO->description)) $tpl->setVariable("DESCRIPTION", str_replace('"','',$user->pageVO->description));
			if(false!==($pageHeading=FBuildPage::getHeading())) if(!empty($pageHeading)) $tpl->setVariable('PAGEHEAD',$pageHeading);
			
			$topbanner = $pageVOTop->prop('topbanner');
			if(!empty($topbanner)) {
				$topbannerData = array();
				$topbanner = explode("\n",$topbanner);
				if(strpos($topbanner[0],';')!==false) $firstBanner = explode(';',$topbanner[0]);
				else $firstBanner = explode(',',$topbanner[0]);
				if($firstBanner[4]>0) {
					$cache = FCache::getInstance('d',$firstBanner[4]);
					$topbannerCache = $cache->getData($pageVOTop->pageId,'topbanner');
					if($topbannerCache!==false) $topbannerData = $topbannerCache;
				}
				if(empty($topbannerData)) {
					$topbannerRow = array_shift($topbanner);
					array_push($topbanner,$topbannerRow);
					$pageVOTop->prop('topbanner',implode("\n",$topbanner));
					$topbannerData['h'] = $firstBanner[1];
					$topbannerData['v'] = $firstBanner[2];
					$topbannerData['m'] = $firstBanner[3];
					
					if(strpos($firstBanner[0],'http')===0) $topbannerData['i'] = $firstBanner[0];
					else {
						$itemVO = new ItemVO($firstBanner[0],true);
						if($itemVO->itemId) {
							$topbannerData['i'] = $itemVO->getImageUrl(null,'1600x1600/prop',true);
							$pageBanner = FactoryVO::get('PageVO',$itemVO->pageId);
							$topbannerData['t'] = $pageBanner->get('name');
							$topbannerData['u'] = FSystem::getUri('i='.$itemVO->itemId,$itemVO->pageId,'');
						}
					}
					$cache->setData($topbannerData);
				}

				if($topbannerData) {
					$tpl->setVariable('TOPBANHEIGHT',$topbannerData['h']);
					$tpl->setVariable('TOPBANMARGIN',$topbannerData['m']);
					$tpl->setVariable('TOPBANVALIGN',$topbannerData['v']);
					if(!empty($topbannerData['u'])) $tpl->setVariable('TOPBANURL',$topbannerData['u']);
					if(!empty($topbannerData['t'])) $tpl->setVariable('TOPBANTITLE',$topbannerData['t']);
					$tpl->setVariable('TOPBANIMG',$topbannerData['i']);
				}
			}
		}
		
		//---BODY PARTS
		
		//---MAIN MENU
		$cache = FCache::getInstance($user->idkontrol?'s':'f',0);
		$menu = $cache->getData('mainmenu');
		//TODO:remove
		$menu = false;
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
		
    //---BREADCRUMBS & SECONDARY MENU
		if($user->pageAccess === true) {
      if($user->pageVO) {
				//---BREADCRUMBS
				if(!FConf::get('settings','breadcrumbs_hide')) {
				$breadcrumbs = FBuildPage::getBreadcrumbs();
				foreach($breadcrumbs as $crumb) {
					
					if(isset($crumb['url'])) {
						$tpl->setVariable('BREADNAME',$crumb['name']);
						$tpl->setVariable('BREADURL',$crumb['url']);
						$tpl->parse('breadcrumb');
					} else {
						$tpl->setVariable('BREADHARD',$crumb['name']);
					}
					
				}
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
		}
		FProfiler::write('FBuildPage--FSystem::secondaryMenu');

		//---LEFT PANEL POPULATING
		$hasSidebarData = false;
		$showSidebar = true;
    if(FConf::get('settings','sidebar_off')) {
      $showSidebar = false;
    } else if($user->pageVO){
			$showSidebar = $user->pageVO->showSidebar;
			if($showSidebar!==false) $showSidebar = !$user->pageVO->prop('hideSidebar');
		}
    
		if($showSidebar) {
			$fsidebar = new FSidebar(($user->pageVO)?($user->pageVO->pageId):(''), $user->userVO->userId, ($user->pageVO)?( $user->pageVO->typeId ):(''));
			$fsidebar->load();
			$hasSidebarData = $fsidebar->show();
			FProfiler::write('FBuildPage--FSidebar');
		}
		$tpl->setVariable("USER", $user->idkontrol?'1':'0');
		$tpl->setVariable("AUTH", $user->getRemoteAuthToken());
		//post messages
		if($user->userVO->hasNewMessages()) {
			$tpl->setVariable('NEWPOST',$user->userVO->newPost);
			$tpl->setVariable('NEWPOSTFROMNAME',$user->userVO->newPostFrom);
			$tpl->setVariable('MESSAGENUM',$user->userVO->newPost);
			$actionNum += $user->userVO->newPost;
		} else {
			$tpl->touchBlock('msgHidden');
		}
		$skinName = SKIN;
		$cssUrl = ((strpos(URL_CSS,'http://')===false)?STATIC_DOMAIN.URL_CSS:URL_CSS);
		$skinRestUrl = '';
		if(!empty($skinName)) {
			$skinRestUrl = 'skin/'.$skinName;
			$tpl->setVariable('URL_SKIN',$cssUrl.$skinRestUrl);
			$tpl->parse('skincss');
			$tpl->setVariable('URL_SKIN',$cssUrl.$skinRestUrl);
		}
		$tpl->setVariable('URL_FAVICON',$cssUrl.$skinRestUrl.'/images/favicon.ico');
		
		$brandLogo = FConf::get('settings','brand_logo');
		if(!empty($brandLogo)) {
			$tpl->setVariable('BRAND_LOGO',$cssUrl.$skinRestUrl.'/images/'.$brandLogo);
			$tpl->setVariable('BRAND_LOGO_URL',$homeUrl);
		}
		
		if($actionNum>0) {
			$tpl->setVariable('ACTIONSNUM',$actionNum);
		}
		
		$bsskin = FConf::get('settings','bssskin_default');
		
		if($user->idkontrol) {
			$bsskin = $user->userVO->prop('skin');
		} else {
			$cache = FCache::getInstance('s');
			$bsskinCached = $cache->getData('bsskin');
			if($bsskinCached!==false) $bsskin = $bsskinCached;
		}
		if(!empty($bsskin)) {
			$tpl->setVariable('BOOTSTRAP_SKIN','.'.$bsskin);
		}
		
		//---custom code
		$cClassname = 'page_'.HOME_PAGE;
		try {
		  if(class_exists($cClassname)) {
			call_user_func(array($cClassname, 'show'),$tpl);
		  }
		} catch(Exception $e){;}
    
		if(!$hasSidebarData) {
			$user->pageVO->tplVars['NUMCOLMAIN'] += 3;
		}
		
		if(!empty($user->pageVO->tplVars)) {
			$tpl->setVariable($user->pageVO->tplVars);
		}
	
		//---GET PAGE DATA
		$data = $tpl->get();
		$data = FSystem::superVars($data);
		//$data = preg_replace('/\s\s+/', ' ', $data); //strip whitespace
		
		FProfiler::write('FBuildPage--complete');
		$user->updateTotalItemsNum();
		return $data;
	}
}