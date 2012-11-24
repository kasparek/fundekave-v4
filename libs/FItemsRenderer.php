<?php
class FItemsRenderer {

	public $debug = false;

	public $hasDefaultSettings = true;

	private $tpl = false;
	private $tplType;
	private $tplParsed = array();
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
		$this->{$key} = $val;
	}

	function setCustomTemplate($templateName) {
		$this->hasDefaultSettings = false;
		$this->customTemplateName = $templateName;
	}

	function getTemplateName($typeId) {
		if(!empty($this->customTemplateName)) return $this->customTemplateName;
		return 'item.'.$typeId.'.tpl.html';
	}

	function addPageName($rendered,$itemVO) {
		$cacheGroup = 'page/'.$itemVO->pageId;
		$cacheId = 'pagelink';
		$cache = FCache::getInstance('f');
		$page = $cache->getData($cacheId,$cacheGroup);
		if($page===false) {
			$tpl = FSystem::tpl('item.pagelink.tpl.html');
			$pageVO = $itemVO->pageVO;
			$pTop=$pageVO->get('pageIdTop');
			$homeUrl='';
			if(!empty($pTop) && $pTop!=HOME_PAGE){
				$pVOTop = FactoryVO::get('PageVO',$pTop);
				$homeUrl = ' - '.$pVOTop->prop('homesite');
			}
			$vars['URL'] = FSystem::getUri('',$itemVO->pageId,'',array('name'=>$pageVO->get('name')));
			$vars['PAGENAME'] = $pageVO->get('name') . $homeUrl;
			$vars['ITEM'] = '[[ITEM]]';
			$tpl->setVariable($vars);
			$page = $tpl->get();
			$cache->setData($page,$cacheId,$cacheGroup);
		}
		$rendered = str_replace('[[ITEM]]',$rendered,$page);
		return $rendered;
	}

	function render( $itemVO ) {
		
		if(!$itemVO->itemId) {
			FError::write_log('RENDERER - empty item');
			return;
		}
		//---get "local"
		$isDefault = $this->init( $itemVO ); //if true it is safe to take cached rendered item

		$cacheGroup = 'page/'.$itemVO->pageId.'/item';
		$cacheId = $itemVO->itemId;
		if($isDefault) {
			//try cache
			$cache = FCache::getInstance('f');
			$cached = $cache->getData($cacheId.(($this->showDetail)?('detail'):('')),$cacheGroup);
			if($cached!==false) {
				if($this->showPage) $cached = $this->addPageName($cached,$itemVO);
				$this->tplParsed[] = $cached;
				return;
			}
		}
		
		if($itemVO->loaded!==true) $itemVO->load();
		if($itemVO->prepared!==true) $itemVO->prepare();

		$itemId = $itemVO->itemId;
		$itemUserId = $itemVO->userId;
		$pageId = $itemVO->pageId;
		$typeId = $itemVO->typeId;

    

		//---INIT TEMPLATE
		if($this->tpl !== false && $typeId != $this->tplType) {
			$this->tpl = false;
		}
		if( $this->tpl === false ) {
			if(empty($typeId)) {
				FError::write_log('FItemsRenderer::render - missing typeId for template itemId:'.$itemId.' pageId:'.$pageId);
			}
			$this->tpl = FSystem::tpl($this->getTemplateName($typeId));
			$this->tplType = $typeId;
		}
		$tpl = $this->tpl;

		//---common for all items
		$touchedBlocks['hentry']=true;
		$vars['ITEMIDHTML'] = 'i'.$itemId;
		$vars['ITEMID'] = $itemId;
		$link = FSystem::getUri('i='.$itemId.((!empty($itemVO->addon))?('-'.FSystem::safeText($itemVO->addon)):('')),$pageId);;
		$vars['TITLEURL'] = $vars['ITEMLINK'] = $link;
		if(!empty($itemVO->addon)) $vars['TITLE'] = $itemVO->addon;
		$vars['PAGEID'] = $pageId;
		//---thumb tag link
		if($typeId!='galery')
			$vars['TAG'] = FItemTags::getTag($itemId,$this->signedUserId,$typeId,$itemVO->tag_weight);

		if($typeId=='forum' || $typeId=='event') {
			$vars['DATELOCAL'] = $itemVO->dateCreatedLocal;
			$vars['DATEISO'] = $itemVO->dateCreatedIso;
		} else {
			$vars['DATELOCAL'] = $itemVO->dateStartLocal;
			$vars['DATEISO'] = $itemVO->dateStartIso;
		}

		$vars['AUTHOR'] = $itemVO->name;
		if($itemUserId>0) {
			$vars['AUTHORLINK'] = FSystem::getUri('who='.$itemUserId.'#tabs-profil','finfo');
			$touchedBlocks['aaclose']=true;	
		}
		$vars['AVATAR'] = FAvatar::showAvatar( (int) $itemUserId);
		$vars['TEXT'] = $itemVO->text;
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
			$vars['IMGURLTHUMB'] = $itemVO->thumbUrl;
			$vars['IMGURL'] = $itemVO->detailUrl;
			$vars['IMGURLBIG'] = $itemVO->bigUrl;
		} else {
			if($itemVO->typeId=='event') $vars['FLYERTHUMBURLDEFAULT'] = '/img/flyer_default.png';
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
				$vars['LONGTITLE'] = $itemVO->addon;
				$vars['LONGURL'] = $vars['ITEMLINK'];
			}
		}

		if($itemVO->typeId=='event') {
			//--EVENT RENDERER
			$vars['STARTDATETIMEISO'] = $itemVO->dateStartIso.(($itemVO->dateStartTime!='00:00')?('T'.$itemVO->dateStartTime):(''));
			$vars['STARTDATETIMELOCAL'] = $itemVO->dateStartLocal.(($itemVO->dateStartTime!='00:00')?(' '.$itemVO->dateStartTime):(''));
			if(!empty($itemVO->dateEnd)) {
				$vars['ENDDATETIMEISO'] = $itemVO->dateEndIso.(($itemVO->dateEndTime!='00:00')?('T'.$itemVO->dateEndTime):(''));
				$vars['ENDDATETIMELOCAL'] = $itemVO->dateEndLocal.(($itemVO->dateEndTime!='00:00')?(' '.$itemVO->dateEndTime):(''));
			}
		}

		$showCommentNum=true;
		if($itemVO->typeId=='forum') {
			$pageType = $itemVO->pageVO->get('typeId');
			$showCommentNum=false; 
		}
		
		if($showCommentNum) {
			$vars['COMMENTLINK'] = $link;
			$vars['CNTCOMMENTS'] = $itemVO->cnt;
			if($itemVO->unreaded > 0) $vars['ALLNEWCNT'] = $itemVO->unreaded;
		}

		//---google maps
		if($itemVO->prop('position')) {
			$vars = array_merge($vars,FItemsRenderer::gmaps($itemVO));
			$touchedBlocks['map']=true; //to display map icon
		}
		
		$vars['TEXT'] = FSystem::postText( $vars['TEXT'] );
		//---FINAL PARSE
		if(isset($touchedBlocks)) $tpl->touchedBlocks = $touchedBlocks;
		$tpl->setVariable($vars);
		$ret = $cached = $tpl->get();
		if($this->showPage) $ret = $this->addPageName($ret,$itemVO);
		$this->tplParsed []= $ret;
		$tpl->init();
		
		if($isDefault) {
			$cache->setData($cached,$cacheId.(($this->showDetail)?('detail'):('')),$cacheGroup);
		}
	}
	
	static function gmaps($itemVO) {
		$vars = array();
		
		$position = $itemVO->prop('position');
		$distance = $itemVO->prop('distance');
		if(empty($position)) return $vars;
		$journey = explode(';',$position);
		$vars['SMAPITEMID'] = $vars['MAPITEMID'] = $itemVO->itemId;
		$vars['MAPPOSITION'] = implode("\n",$journey);
		$vars['SMAPTITLE'] = $vars['MAPTITLE'] = $itemVO->addon;
		$vars['SMAPINFO'] = $vars['MAPINFO'] = FSystem::textins($itemVO->text,array('plaintext'=>1));
		$vars['SMARKERPOS'] = $journey[count($journey)-1];
		
		
		if($distance>0) $vars['DISTANCE'] = $distance;
		if(count($journey)>1) {
			$geoEncode = new GooEncodePoly();
			$vars['SWPLIST'] = 'enc:'.$geoEncode->encode($journey);
		}
		return $vars;
	} 
	
	function getLast() {
		return $this->tplParsed[count($this->tplParsed)-1];
	}
	function setLast($rendered) {
		$this->tplParsed[count($this->tplParsed)-1] = $rendered;
	}
	
	function show() {
		return implode($this->tplParsed);
	}
}