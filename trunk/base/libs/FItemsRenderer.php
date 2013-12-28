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
			$vars['TEXT'] = $pageVO->get('name');
			if($pageVO->typeId=='galery') $vars['BADGE'] = $pageVO->cnt;
			$vars['DATA'] = '[[ITEM]]';
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
				//if($this->showPage) $cached = $this->addPageName($cached,$itemVO);
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
		if($itemVO->typeId!='galery' && $this->showDetail) {
			$nextUri = '#'; 
			$prevUri = '#';
			if(($itemNext = $itemVO->getNext(true,$itemVO->typeId=='galery'))!==false) $nextUri = FSystem::getUri('i='.$itemNext,$itemVO->pageId);
			if(($itemPrev = $itemVO->getPrev(true,$itemVO->typeId=='galery'))!==false) $prevUri = FSystem::getUri('i='.$itemPrev,$itemVO->pageId);
			if(!$itemNext) $touchedBlocks['prevbtndis']=true;
			if(!$itemPrev) $touchedBlocks['nextbtndis']=true;
			$vars['PREVURL'] = $nextUri;
			$vars['NEXTURL'] = $prevUri;		
		}
		
		$vars['ITEMIDHTML'] = 'i'.$itemId;
		$vars['ITEMID'] = $itemId;
		$link = FSystem::getUri('i='.$itemId.((!empty($itemVO->addon))?('-'.FText::safeText($itemVO->addon)):('')),$pageId);
		$vars['TITLEURL'] = $vars['ITEMLINK'] = $link;
		if(!empty($itemVO->addon)) $vars['TITLE'] = $itemVO->addon;
		$vars['PAGEID'] = $pageId;
		//---top item link
		if(!empty($itemVO->itemIdTop)) $vars['TOPITEMLINK'] = FSystem::getUri('i='.$itemVO->itemIdTop,$itemVO->pageId);
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
		}

		//modifiers to standart
		if($itemVO->isUnreaded === true) {
			$touchedBlocks['unread']=true;
		}

		if($itemVO->editable === true) {
			$user = FUser::getInstance();
			if($itemVO->typeId=='forum' || $user->pageVO->pageId == $pageId) {
				$vars['EDITURL'] = FSystem::getUri('i='.$itemId,$pageId,'u');
				$vars['DELETEURL']=FSystem::getUri('m=item-delete&d=item='.$itemId,'','');
			}
		}

		if($itemVO->typeId!='event' && !empty($itemVO->textLong)) {
			if($this->showDetail===true) {
				$vars['TEXT'] .= (strpos($itemVO->text,'<p>')===false?'<br><br>':'') . $itemVO->textLong;
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
			$vars['GOOMAPTHUMB'] = FItemsRenderer::gmaps($itemVO,true);
			$vars['GOOMAP'] = FItemsRenderer::gmaps($itemVO);
		}
		
		$vars['TEXT'] = FText::postProcess( $vars['TEXT'] );
		//---FINAL PARSE
		if(isset($touchedBlocks)) $tpl->touchedBlocks = $touchedBlocks;
		$tpl->setVariable($vars);
		$ret = $cached = $tpl->get();
		//if($this->showPage) $ret = $this->addPageName($ret,$itemVO);
		$this->tplParsed []= $ret;
		$tpl->init();
		
		if($isDefault) {
			$cache->setData($cached,$cacheId.(($this->showDetail)?('detail'):('')),$cacheGroup);
		}
	}
	
	static function gmaps($itemVO,$thumb=false) {
	
		$vars = array();
		$smarker = '';
		$journey = array();
		$position = $itemVO->prop('position');
		if(empty($position)) return '';
		$distance = (int) $itemVO->prop('distance');
		$journey = explode(';',$position);
		$journeyLen = count($journey);
		$smarker = $journey[$journeyLen-1];
		if($journeyLen>1) {
			$geoEncode = new GooEncodePoly();
			$swpList = $geoEncode->encode($journey);
		}
		if($thumb) {
			return '<div class="pull-right">'
			.'<a id="mapThumb'.$itemVO->itemId.'" href="#map-large-'.$itemVO->itemId.'" title="Map detail: '.$itemVO->addon.'" class="mapThumbLink">'
			.'<img width="100" height="100" '
			.'src="http://maps.google.com/maps/api/staticmap?size=100x100&markers='.$smarker.'&maptype=terrain&sensor=false'.(!empty($swpList)?'&path=enc:'.$swpList:'').'" '
			.'title="'.$itemVO->addon.'" alt="Google Maps" /></a></div>';
		} else {
			return '<a id="map-large-'.$itemVO->itemId.'"></a><div id="map'.$itemVO->itemId.'" class="mapLarge hidden">'
			.'<div class="mapsData"><input type="hidden" class="geoData" title="'.$itemVO->addon.'" value="'.implode("\n",$journey).'" />'
			.'<div class="geoInfo"><h3>'.$itemVO->addon.'</h3><p>'.FText::preProcess($itemVO->text,array('plaintext'=>1))
			.($distance>0?'<p><strong>Vzdalenost: '.$distance.'NM</strong></p>':'')
			.'</p></div></div></div>';
		}
	} 
	
	function getLast() {
		return $this->tplParsed[count($this->tplParsed)-1];
	}
	function setLast($rendered) {
		$this->tplParsed[count($this->tplParsed)-1] = $rendered;
	}

	function addContent($content) {
		$this->tplParsed[] = $content;
	}
	
	function show() {
		$ret = implode($this->tplParsed);
		$this->tplParsed = array();
		return $ret;
	}
}