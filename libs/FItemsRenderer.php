<?php
/*
	TODO: migrate itemIdBottom into text
	migrate pageIdBottom into text
	TODO: remove localUserZavatar user setting - nobody using it
*/
class FItemsRenderer {

	public $debug = false;
	
	public $hasDefaultSettings = true;

	private $tpl = false;
	private $tplType;
	private $tplParsed = '';
	private $customTemplateName = '';
	
	//---custom settings
	public $showDetail = false;

	private $initialized = false;
	
	public $itemIdInside = 0; //only for comments - it it needed? 
	
	private $signedUserId;
	private $signedPageId;

	/**
	 *
	 *TODO: THIS ADDS MORE PAIN FOR RENDERER CACHING
	 *solve problem on not user depend render	 
	 *initialize all vars on start and figureout if we can use standart renderer cached item
	 *-not for user editable item	 	 	 
	 *
	 **/	 	 	
	function init( $itemVO ) {
		if($this->initialized===false) {
			$this->initialized===true;
			$user = FUser::getInstance();
			$this->signedUserId = $user->idkontrol ? $user->userVO->userId : false;
			if($this->signedUserId !== false) $this->hasDefaultSettings = false;
			$this->signedPageId = $user->pageVO->pageId;
		}
		//check all modifiers
		if( $itemVO->editable === true ) $this->hasDefaultSettings = false;
		
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

	function render( $itemVO ) {
		//---get "local"
		$isDefault = $this->init( $itemVO ); //if true it is safe to take cached rendered item
		
		//TODO: do renderer caching is hasDefaultSettings===true 
		$cacheGroup = 'renderedItem';
		$cacheId = $this->itemId;

		$itemId = $itemVO->itemId;
		$itemUserId = $itemVO->userId;
		$pageId = $itemVO->pageId;
		$pageVO  = new PageVO($pageId);
		$typeId = $itemVO->typeId;
		$addon = $itemVO->addon;
		$enclosure = $itemVO->enclosure;
		
		//---INIT TEMPLATE
		if($this->tpl !== false && $typeId != $this->tplType) {
			$this->tplParsed .= $this->tpl->get();
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
		$vars['ITEMLINK'] = FSystem::getUri('i='.$itemId,'');
		$vars['PAGEID'] = $pageId;
		if($typeId=='blog' || $typeId=='galery') {
			$vars['DATELOCAL'] = $itemVO->dateStartLocal;
			$vars['DATEISO'] = $itemVO->dateStartIso;
		} else {
			$vars['DATELOCAL'] = $itemVO->dateCreatedLocal;
			$vars['DATEISO'] = $itemVO->dateCreatedIso;
		}
		if($itemVO->public != 1) {
			$touchedBlocks['notpublished']=true;
			$touchedBlocks['notpublishedheader']=true;
		}

		if(isset($itemVO->name)) $vars['AUTHOR'] = $itemVO->name;
		if($itemVO->isUnreaded === true) $touchedBlocks['unread']=true;
		if($itemVO->editable === true) {
			$vars['EDITURL'] = FSystem::getUri('i='.$itemId,$pageId,'u');
			$vars['DELETEURL']=FSystem::getUri('m=item-delete&d=item:'.$itemId,'','');
		}

		if($itemVO->text) {
			$vars['TEXT'] = $itemVO->text;
		}
		/**/
		$vars['HITS'] = $itemVO->hit;
		
		switch($typeId) {
			case 'blog':
				$user = FUser::getInstance();
								
				if($itemVO->categoryId > 0) {
					$categoryArr = FCategory::getCategory($itemVO->categoryId);
					$vars['CATEGORYNAME'] = $categoryArr[2];
					$vars['CATEGORYURL'] = FSystem::getUri('c='.$itemVO->categoryId,$itemVO->pageId);
					if( $this->showDetail===true ) {
						$touchedBlocks['categoryhidden'] = true;	
					}
				}
				
				if(!empty($itemVO->textLong)  ) {
					if($this->showDetail===true) {
						if(!isset($vars['TEXT'])) $vars['TEXT'] ='';
						$vars['TEXT'] .= '<br /><br />'."\n".$itemVO->textLong;
					} else {
						$vars['LONGURL'] = $vars['ITEMLINK'];
					}
				}
				if( $this->showDetail===true ) {
					$touchedBlocks['headhidden'] = true;
				}
				$vars['AVATAR'] = FAvatar::showAvatar( (int) $itemUserId);
				break;
			case 'event':
				//--EVENT RENDERER
				if($itemVO->categoryId > 0) {
					$categoryArr = FCategory::getCategory($itemVO->categoryId);
					$vars['CATEGORY'] = $categoryArr[2];
				}
				$vars['LOCATION'] = $itemVO->location;
				$vars['STARTDATETIMEISO'] = $itemVO->dateStartIso.(($itemVO->dateStartTime!='00:00')?('T'.$itemVO->dateStartTime):(''));
				$vars['STARTDATETIMELOCAL'] = $itemVO->dateStartLocal.(($itemVO->dateStartTime!='00:00')?(' '.$itemVO->dateStartTime):(''));
				if(!empty($itemVO->dateEndIso)) {
					$vars['ENDDATETIMEISO'] = $itemVO->dateEndIso.(($itemVO->dateEndTime!='00:00')?('T'.$itemVO->dateEndTime):(''));
					$vars['ENDDATETIMELOCAL'] = $itemVO->dateEndLocal.(($itemVO->dateEndTime!='00:00')?(' '.$itemVO->dateEndTime):(''));
				}

				if(!empty($enclosure)) {
					$flyerFilename = FEvents::flyerUrl($enclosure);
					$flyerFilenameThumb = FEvents::thumbUrl($enclosure);
					$vars['BIGFLYERLINK'] = $flyerFilename;
					$vars['FLYERTHUMBURL'] = $flyerFilenameThumb;
					$vars['IMGEVENTTITLE'] = $addon;
					$vars['IMGEVENTALT'] = $addon;
				} else {
					$vars['FLYERTHUMBURLDEFAULT'] = '/img/flyer_default.png';
				}
				if($this->showDetail === true) {
					if($itemVO->tag_weight > 0) {
						$arrTags = FItemTags::getItemTagList($itemId);
						foreach ($arrTags as $tag) {
							$tpl->setVariable('PARTICIPANTAVATAR',FAvatar::showAvatar($tag[0]));
							$tpl->parse('participant');
						}
					}
				}
								
				break;
			case 'forum':
				//--FORUM RENDERER
				if(!empty($enclosure)) {
					if(!isset($vars['TEXT'])) $vars['TEXT'] ='';
					$vars['TEXT'] .= '<br /><br />' . "\n" . $enclosure;
				}
				$vars['AVATAR'] = FAvatar::showAvatar( (int) $itemUserId);
				break;
			case 'galery':
				//--- GALERY RENDERER
				$pageVO->load();
				$vars['IMGALT'] = $pageVO->name.' '.$enclosure;
				$vars['IMGTITLE'] = $pageVO->name.' '.$enclosure;
				$vars['IMGURLTHUMB'] = $itemVO->thumbUrl;
				$vars['IMGURL'] = $itemVO->detailUrl;
				$vars['POSITION'] = $itemVO->prop('position');
				break;
		}
		/**/

		//---for logged users
		if ($this->signedUserId !== false) {
			//---thumb tag link
			$vars['TAG'] = FItemTags::getTag($itemId,$this->signedUserId,$typeId,$itemVO->tag_weight);
			//---user link and location
			if($itemUserId > 0) {
				if($typeId != 'galery') {
					$vars['AUTHORLINK'] = FSystem::getUri('who='.$itemUserId,'finfo');
					$touchedBlocks['authorlinkclose']=true;
				}
				if($typeId == 'forum') {
					if (FUser::isOnline( $itemUserId )) {
						$kde = FUser::getLocation( $itemUserId );
						$vars['USERLOCATION'] = $kde['name'];
						$vars['USERLOCATIONLINK'] = FSystem::getUri('',$kde['pageId'],$kde['param']);
					}
				}
			}
		}
		/**/
		$link = FSystem::getUri('i='.$itemId.(($addon)?('-'.FSystem::safeText($addon)):('')),$pageId);;
		//---BLOG / EVENT
		if( $addon ) {
			$vars['BLOGLINK'] = $link;
			$vars['BLOGTITLE'] = $addon;
		}
		if($this->showDetail === true) {
			$writeRule = $pageVO->prop('forumSet');
			if(false !== ($itemWriteRule = $itemVO->prop('forumSet'))) $writeRule = $itemWriteRule;
			$vars['COMMENTS'] = FForum::show($itemId, $writeRule, $this->itemIdInside,array('showHead'=>false,'simple'=>1) );
		}
		$vars['COMMENTLINK'] = $link;
		if($itemVO->unreaded > 0) { $vars['ALLNEWCNT'] = $itemVO->unreaded; }
		$vars['CNTCOMMENTS'] = $itemVO->cnt;
		
		//---PAGE NAME
		// TODO: find a different way to list reactions?
		// TODO: show top item if only on life page, not on same pageId or itemId page
		/*
		if($this->showPageLabel === true) {
			if($itemVO->itemIdTop > 0) {
				$itemTop = new ItemVO($itemVO->itemIdTop,true);
				$vars['TOP'] = $itemTop->render();
			}
		}
		*/
		/**/
		if(!empty($vars['TEXT'])) $vars['TEXT'] = FSystem::postText( $vars['TEXT'] );
		
		/* google maps */
		//TODO: fix caching position
		$position = $itemVO->prop('position');
		if(!empty($position)) {
			
			if(isset($_GET['map'])) {
			 $vars['MAPPOSITION'] = str_replace(";","\n",$position);
			 $vars['MAPTITLE'] = $addon;
			 $vars['MAPINFO'] = str_replace(array("\n","\r"),'',$itemVO->text);
			} else {
				$journey = explode(';',$position);
			   $vars['STATICITEMID'] = $itemVO->itemId;
			   $vars['STATICITEMTITLE'] = $addon;
			   $vars['STATICMARKERPOS'] = $journey[count($journey)-1];
			   if(count($journey)>1) $vars['STATICWPLIST'] = implode('|',$journey);
			 }
		}
		/**/

		//---FINAL PARSE
		if(isset($touchedBlocks)) $tpl->touchedBlocks = $touchedBlocks;
		$tpl->setVariable($vars);
		$tpl->parse();
	}

	function show() {
		$tpl = $this->tpl;
		$ret = $this->tplParsed;
		$this->tplParsed = '';
		if( $tpl ) {
			$ret .= $tpl->get();
			$this->tpl = false;
		}
		return $ret;
	}
}