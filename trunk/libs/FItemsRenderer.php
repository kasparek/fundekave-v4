<?php
class FItemsRenderer {

	public $debug = false;
	
	public $hasDefaultSettings = true;

	private $tpl = false;
	private $tplType;
	private $tplParsed = '';
	private $customTemplateName = '';
	
	//---custom settings
	public $showDetail = false;
	public $showPage = false;

	private $initialized = false;
		
	private $signedUserId;
	 	 	
	function init( $itemVO ) {
		if($this->initialized===false) {
			$this->initialized===true;
			$user = FUser::getInstance();
			$this->signedUserId = $user->idkontrol ? $user->userVO->userId : false;
		}
		//check all modifiers
		if($itemVO->isUnreaded) $this->hasDefaultSettings = false;
		if($itemVO->editable) $this->hasDefaultSettings = false;
		return $this->hasDefaultSettings;
	}

	function setOption($key,$val) {
	  $this->hasDefaultSettings = false;
	  //TODO: check if key exist
	  $this->{$key} = $val;
	}

	function setCustomTemplate($templateName) {
		$this->hasDefaultSettings = false;
		$this->customTemplateName = $templateName;
	}

	function getTemplateName($typeId) {
		if(empty($this->customTemplateName)) {
			return 'item.'.$typeId.'.tpl.html';
		} else {
			return $this->customTemplateName;
		}
	}

	function addPageName($rendered,$itemVO) {
	  $cacheGroup = 'renderedPagelink';
		$cacheId = $itemVO->pageId;
		$cache = FCache::getInstance('f',0);
		$page = $cache->getData($cacheId,$cacheGroup);
		if($page===false) {
			$tpl = FSystem::tpl($this->getTemplateName($typeId));
			$tpl->parse('item');
			$vars['URL'] = '?k='.$page->pageId.'-'.FSystem::safetext($page->name));
			if(!empty($itemVO->pageVO->pageIco)) {
				$tpl->setVariable("AVATARURL", URL_PAGE_AVATAR.$itemVO->pageVO->pageIco);
			} else {
				$tpl->setVariable("AVATARURL", FConf::get('pageavatar',$itemVO->pageVO->typeId));
			}
			$vars['AVATARALT'] = FLang::$TYPEID[$page->typeId]);
			$vars['AVATARNAME'] = FLang::$TYPEID[$page->typeId]).': '.$itemVO->pageVO->name; 
			$vars['PAGENAME'] = $itemVO->pageVO->name;
			$vars['ITEM'] = '[[ITEM]]';
			$tpl->setVariable($vars);
			$page = $tpl->get('item');
			$page = $cache->setData($page,$cacheId,$cacheGroup);
		} else {
			FProfiler::write('FItemsRenderer::addPageName--RENDER PAGENAME FROM CACHE-'.$cacheId);
		}
		$rendered = str_replace('[[ITEM]]',$rendered,$page);
		return $rendered;
	}

	function render( $itemVO ) {
		//---get "local"
		$isDefault = $this->init( $itemVO ); //if true it is safe to take cached rendered item
		
		$cacheGroup = 'renderedItem';
		$cacheId = $itemVO->itemId;
		if($isDefault) {
			//try cache
			$cache = FCache::getInstance('f',0);
			$cached = $cache->getData($cacheId.(($this->showDetail)?('detail'):('')),$cacheGroup);
			if($cached!==false) {
				if($this->showPage) $cached = $this->addPageName($cached,$itemVO);
				$this->tplParsed .= $cached;
				FProfiler::write('FItemsRenderer::render--RENDER LOADED FROM CACHE-'.$cacheId);
				return;
			}
		}

		$itemId = $itemVO->itemId;
		$itemUserId = $itemVO->userId;
		$pageId = $itemVO->pageId;
		$pageVO  = new PageVO($pageId);
		$typeId = $itemVO->typeId;
		
		//---INIT TEMPLATE
		if($this->tpl !== false && $typeId != $this->tplType) {
			$this->tpl = false;
		}
		if( $this->tpl === false ) {
			$this->tpl = FSystem::tpl($this->getTemplateName($typeId));
			$this->tplType = $typeId;
		}
		$tpl = $this->tpl;

		//---common for all items
		$touchedBlocks['hentry']=true;
		$vars['ITEMIDHTML'] = 'i'.$itemId;
		$vars['ITEMID'] = $itemId;
		$link = FSystem::getUri('i='.$itemId.((!empty($itemVO->addon))?('-'.FSystem::safeText($itemVO->addon)):('')),$pageId);;
		$vars['ITEMLINK'] = $link;
		if(!empty($itemVO->addon)) $vars['ITEMTITLE'] = $itemVO->addon;
		$vars['PAGEID'] = $pageId;
		//---thumb tag link
		$vars['TAG'] = FItemTags::getTag($itemId,$this->signedUserId,$typeId,$itemVO->tag_weight);
		
		if($typeId!='forum') {
			$vars['DATELOCAL'] = $itemVO->dateCreatedLocal;
			$vars['DATEISO'] = $itemVO->dateCreatedIso;
		} else {
			$vars['DATELOCAL'] = $itemVO->dateStartLocal;
			$vars['DATEISO'] = $itemVO->dateStartIso;
		}
		
		$vars['AUTHOR'] = $itemVO->name;
		$vars['AUTHORLINK'] = FSystem::getUri('who='.$itemUserId,'finfo');
		$vars['AVATAR'] = FAvatar::showAvatar( (int) $itemUserId);
		$vars['TEXT'] = FSystem::postText( $itemVO->text );
		$vars['HITS'] = $itemVO->hit;
		$vars['LOCATION'] = $itemVO->location;
		
		if($itemVO->categoryId > 0) {
			$categoryArr = FCategory::getCategory($itemVO->categoryId);
			$vars['CATEGORYNAME'] = $categoryArr[2];
			$vars['CATEGORYURL'] = FSystem::getUri('c='.$itemVO->categoryId,$itemVO->pageId);
		}
		
		if($itemVO->public != 1) {
			$touchedBlocks['notpublished']=true;
			$touchedBlocks['notpublishedheader']=true;
		}
		
		if(!empty($itemVO->enclosure)) {
			$vars['IMGALT'] = $itemVO->enclosure;
			$vars['IMGTITLE'] = $itemVO->addon.' '.$itemVO->pageVO->name.' '.$itemVO->enclosure;
			$vars['IMGURLTHUMB'] = $itemVO->thumbUrl;
			$vars['IMGURL'] = $itemVO->detailUrl;
		} else {
		  $vars['FLYERTHUMBURLDEFAULT'] = '/img/flyer_default.png';
		}
		
		//modifiers to standart
		if($itemVO->isUnreaded === true) {
			$touchedBlocks['unread']=true;
		}
		
		if($itemVO->editable === true) {
			$vars['EDITURL'] = FSystem::getUri('i='.$itemId,$pageId,'u');
			$vars['DELETEURL']=FSystem::getUri('m=item-delete&d=item:'.$itemId,'','');
		}
		
		if(!empty($itemVO->textLong)  ) {
			if($this->showDetail===true) {
				$vars['TEXT'] .= '<br /><br />'."\n". $itemVO->textLong;
			} else {
				$vars['LONGURL'] = $vars['ITEMLINK'];
			}
		}
		
		if($itemVO->typeId=='event') {
				//--EVENT RENDERER
				$vars['STARTDATETIMEISO'] = $itemVO->dateStartIso.(($itemVO->dateStartTime!='00:00')?('T'.$itemVO->dateStartTime):(''));
				$vars['STARTDATETIMELOCAL'] = $itemVO->dateStartLocal.(($itemVO->dateStartTime!='00:00')?(' '.$itemVO->dateStartTime):(''));
				if(!empty($itemVO->dateEndIso)) {
					$vars['ENDDATETIMEISO'] = $itemVO->dateEndIso.(($itemVO->dateEndTime!='00:00')?('T'.$itemVO->dateEndTime):(''));
					$vars['ENDDATETIMELOCAL'] = $itemVO->dateEndLocal.(($itemVO->dateEndTime!='00:00')?(' '.$itemVO->dateEndTime):(''));
				}
		}
		
		$vars['COMMENTLINK'] = $link;
		$vars['CNTCOMMENTS'] = $itemVO->cnt;
		if($itemVO->unreaded > 0) $vars['ALLNEWCNT'] = $itemVO->unreaded;
				
		/* google maps */
		$position = $itemVO->prop('position');
		if(!empty($position)) {
			if($this->showDetail === true) {
			 $vars['MAPPOSITION'] = str_replace(";","\n",$position);
			 $vars['MAPTITLE'] = $itemVO->addon;
			 $vars['MAPINFO'] = str_replace(array("\n","\r"),'',$itemVO->text);
			} else {
				$journey = explode(';',$position);
				$vars['STATICURL'] = $link;
				$vars['STATICITEMTITLE'] = $itemVO->addon;
			   $vars['STATICMARKERPOS'] = $journey[count($journey)-1];
			   if(count($journey)>1) $vars['STATICWPLIST'] = implode('|',$journey);
			 }
		}
		/**/
		$vars['TEXT'] = FSystem::postText( $vars['TEXT'] );
		//---FINAL PARSE
		if(isset($touchedBlocks)) $tpl->touchedBlocks = $touchedBlocks;
		$tpl->setVariable($vars);
		$ret = $cached = $tpl->get();
		if($this->showPage) $ret = $this->addPageName($ret,$itemVO); 
		$this->tplParsed .= $ret;
		$tpl->init();
		if($isDefault) {
			$cache->setData($cached,$cacheId.(($this->showDetail)?('detail'):('')),$cacheGroup);
		}
	}

	function show() {
		return $this->tplParsed;
	}
}