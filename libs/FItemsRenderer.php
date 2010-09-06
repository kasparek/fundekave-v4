<?php
class FItemsRenderer {

	function __construct() {

	}

	var $debug = false;

	private $tpl = false;
	private $tplType;
	private $tplParsed = '';
	private $customTemplateName = '';
	//---item enablers
	public $showPageLabel = false;
	public $showTag = true;
	public $showComments = false;
	public $showCommentsNum = true;
	public $showText = true;
	public $showTooltip = false;
	public $openPopup = true;
	public $showRating = true;
	public $showHentryClass = true;
	public $inside = false;
	public $showPocketAdd = true;
	public $showFooter = true;
	public $showHeading = true;
	public $currentHeader = '';
	public $itemIdInside = 0;
	public $showBottomItem = true;
	public $thumbPreventCache = false;
	public $showDetail = false;

	private $initialized = false;
	private $localVars;

	function init() {
		if($this->initialized===false) {
			$this->initialized===true;
			$user = FUser::getInstance();
			$this->localVars['localUserId'] = $user->userVO->userId;
			$this->localVars['localUserPageId'] = $user->pageVO->pageId;
			$this->localVars['localUserZavatar'] = $user->userVO->zavatar;
			$this->localVars['localUserIdkontrol'] = $user->idkontrol;
			$this->localVars['localCSS'] = FSystem::getSkinCSSFilename();
		}
		return $this->localVars;
	}

	function setCustomTemplate($templateName) {
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
		extract($this->init());

		$itemId = $itemVO->itemId;
		$itemUserId = $itemVO->userId;
		$pageId = $itemVO->pageId;
		$typeId = $itemVO->typeId;
		$addon = $itemVO->addon;
		$enclosure = $itemVO->enclosure;

		//---check permissions to edit
		$enableEdit = false;
		if($itemUserId === $localUserId) {
			$enableEdit=true;
		} else {
			if(FRules::get($localUserId,$pageId,2)) {
				$enableEdit=true;
			}
		}
		/*.........zacina vypis prispevku.........*/

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
		if($this->showHentryClass === true) $touchedBlocks['hentry']=true;
		if($this->inside === true) {
			$touchedBlocks['thumbsdiv']=true;
			$touchedBlocks['thumbsdivend']=true;	
		}
		$vars['ITEMIDHTML'] = 'i'.$itemId;
		$vars['ITEMID'] = $itemId;
		$vars['ITEMLINK'] = FSystem::getUri('i='.$itemId,'');
		$vars['ITEMLINKICODIR'] = $localCSS;
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
		if($itemVO->unread === true) $touchedBlocks['unread']=true;
		if($enableEdit === true) {
			if($itemVO->editable === true && $localUserPageId == $pageId) {

				$vars['EDITURL'] = FSystem::getUri('i='.$itemId,$pageId,'u');

				//forum
				$vars['DELETEURL']=FSystem::getUri('m=items-delete&d=item:'.$itemId,'','');
				$vars['DELETECSSDIR']=$localCSS;
			}
		}

		if($this->showText === true && $itemVO->text) {
			$vars['TEXT'] = $itemVO->text;
		}
		/**/
		if($this->showRating === true) {
			$vars['HITS'] = $itemVO->hit;
			$vars['ITEMEYEDIR'] = $localCSS;
		}

		switch($typeId) {
			case 'blog':
				$user = FUser::getInstance();
								
				$detailId = '';
				if($user->itemVO) {
					if($detailId == $itemId) {
						$this->showDetail = true;
					}
				}
				
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
				if( $localUserZavatar == 1 ) {
					$vars['AVATAR'] = FAvatar::showAvatar( (int) $itemUserId);
				}
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
					$vars['FLYERTHUMBURLDEFAULT'] = $localCSS . '/img/flyer_default.png';
				}
				if($this->showComments === true) {
					if($itemVO->tag_weight > 0) {
						$arrTags = FItemTags::getItemTagList($itemId);
						foreach ($arrTags as $tag) {
							$tpl->setVariable('PARTICIPANTAVATAR',FAvatar::showAvatar($tag[0],array('showName'=>1)));
							$tpl->parse('participant');
						}
					}
				}
				if($this->showFooter === true) {
					if($enableEdit === true) {
						$vars['EDITLINK'] = FSystem::getUri('i='.$itemId,'event','u');
					}
				}
				break;
			case 'forum':
				//--FORUM RENDERER
				if( $enclosure ) {
					if(!isset($vars['TEXT'])) $vars['TEXT'] ='';
					$vars['TEXT'] .= '<br /><br />' . "\n" . $this->proccessItemEnclosure($enclosure);
				}
				if( $localUserZavatar == 1 ) {
					$vars['AVATAR'] = FAvatar::showAvatar( (int) $itemUserId);
				}
				break;
			case 'galery':
				//--- GALERY RENDERER
				$pageVO  = new PageVO($pageId,true);
				$vars['IMGALT'] = $pageVO->name.' '.$enclosure;
				$vars['IMGTITLE'] = $pageVO->name.' '.$enclosure;
				$vars['IMGURLTHUMB'] = $itemVO->thumbUrl.(($this->thumbPreventCache)?('?r='.rand()):(''));
				$vars['ADDONSTYLEWIDTH'] = ' style="width: '.$itemVO->thumbWidth.'px;"';
				//$vars['ADDONSTYLEHEIGHT'] = ' style="height: '.$itemVO->height.'px;"';
				$vars['POSITION'] = $itemVO->prop('position');

				if( $this->openPopup === true ) {
					$vars['IMGURLDETAIL'] = $itemVO->detailUrl;
					$touchedBlocks['popupc'] = true;
					$vars['POPUPCLIGHTBOXGROUP'] = '-'.$pageId;
				} else {
					$vars['IMGURLDETAIL'] = $itemVO->detailUrlToGalery;
				}
				if($this->showTooltip === true) {
					$vars['ITEMIDTOOLTIP'] = $itemId;
					$vars['PAGEIDTOOLTIP'] = $pageId;
					$vars['LINKPOPUP'] = $itemVO->detailUrl;
				}
				unset($pageVO);
				break;
		}
		/**/

		//---for logged users
		if ($localUserIdkontrol === true && $this->showFooter === true) {
			//---thumb tag link
			if($this->showTag === true) {
				$vars['TAG'] = FItemTags::getTag($itemId,$localUserId,$typeId,$itemVO->tag_weight);
			}
			/*
			 if($this->showPocketAdd === true) {
				$vars['POCKET'] = FPocket::getLink($itemId);
				}
				*/
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

			if($this->showHeading == true) {
				$vars['BLOGLINK'] = $link;
				$vars['BLOGTITLE'] = $addon;
			}

		}

		if($this->showComments === true) {
			$this->showCommentsNum = false;

			$writeRule = PageVO::getProperty($pageId,'forumSet');
			if(false !== ($itemWriteRule = ItemVO::getProperty($itemId,'forumSet',2))) $writeRule = $itemWriteRule;
			$vars['COMMENTS'] = FForum::show($itemId, $writeRule, $this->itemIdInside,array('showHead'=>false,'simple'=>1) );

		}
		if($this->showCommentsNum === true){
			$vars['COMMENTLINK'] = $link;
			$unReadedReactions = $itemVO->getNumUnreadComments( $localUserId );
			if($unReadedReactions > 0) { $vars['ALLNEWCNT'] = $unReadedReactions; }
			$vars['CNTCOMMENTS'] = $itemVO->cnt;
			$vars['COMMENTSCSSDIR'] = $localCSS;
		}

		/**/

		//---linked item
		if($this->showBottomItem === true) {
			if($itemVO->itemIdBottom > 0) {
				if(!isset($vars['TEXT'])) $vars['TEXT'] ='';
				$vars['TEXT'] .= '<br /><br />'."\n".'<a href="http://'.$_SERVER['SERVER_NAME'].'/'.FSystem::getUri('i='.$itemVO->itemIdBottom,'','').'">'.$itemVO->itemIdBottom.'</a>';
				unset($itemVOBottom);
			}
			if( $itemVO->pageIdBottom ) {
				if( FRules::get($localUserId,$itemVO->pageIdBottom,1) ) {
					$pageVO = new PageVO($itemVO->pageIdBottom,true);
					if(!isset($vars['TEXT'])) $vars['TEXT'] ='';
					$vars['TEXT'] .= '<br /><br />'."\n".'<a href="http://'.$_SERVER['SERVER_NAME'].'/'.FSystem::getUri('',$itemVO->pageIdBottom).'">'.$pageVO->name.'</a>';
					unset($pageVO);
				}
			}
		}
		/**/

		//---PAGE NAME
		if($this->showPageLabel === true) {
			if($itemVO->itemIdTop > 0) {
				$itemTop = new ItemVO($itemVO->itemIdTop,true);
				$vars['TOP'] = $itemTop->render();
			}
		}
		/**/
		if(!empty($vars['TEXT'])) $vars['TEXT'] = FSystem::postText( $vars['TEXT'] );
		
		/* google maps */
		//TODO: fix caching position
		$position = $itemVO->prop('position');
		if(!empty($position)) {
		
		$vars['MAPDIVITEMID'] = $itemVO->itemId;
		
		$to = array();
		$position = str_replace(' ','',trim($position));
		  $journeyTo = $itemVO->prop('journeyTo');
		  if(!empty($journeyTo)) {
			$to = explode("\n",$journeyTo);
			}
		   $to[] = $position;
		   $journeyFrom = $itemVO->prop('journeyFrom');
		   if(!empty($journeyFrom)) {
		   $from = explode("\n",$itemVO->prop('journeyFrom'));
		   $merge = array_merge($to,$from);
		   } else {
			 $merge = $to;
			 }
			if(isset($_GET['map'])) {
				$touchedBlocks['maplarge']=true;
			 //get bound points
			 $swArr = array(90,180); 
			 $neArr = array(-90,-180);
			 foreach($merge as $k=>$v) {
			    $wpLatLong = explode(',',str_replace(' ','',trim($v)));
			    if($wpLatLong[0] < $swArr[0]) $swArr[0] = $wpLatLong[0];
			    if($wpLatLong[0] > $neArr[0]) $neArr[0] = $wpLatLong[0];
			    if($wpLatLong[1] < $swArr[1]) $swArr[1] = $wpLatLong[1];
			    if($wpLatLong[1] > $neArr[1]) $neArr[1] = $wpLatLong[1];
			 }
			 $sw = implode(',',$swArr); 
			 $ne = implode(',',$neArr);
			 $vars['MAPITEMID'] = $itemVO->itemId;
			 $vars['INITPOS'] = $position;
			 $vars['ITEMCONTENT'] = '<strong>'.$addon.'</strong><br />.'.str_replace(array("\n","\r"),'',$itemVO->text); 
			 $vars['ITEMPOS'] = $position;
			 $vars['ITEMTITLE'] = $addon;
			 $vars['SWBOUND'] = $sw;
			 $vars['NEBOUND'] = $ne;
			 $vars['MAPCLASS'] = 'maplarge';
			 
			 $vars['JOURNEYITEMID'] = $itemVO->itemId;
			 while(count($merge)>0) {
			 	$wp = str_replace(' ','',trim(array_shift($merge)));
			 	$tpl->setVariable('WPPOS',$wp);
			 	if(count($merge)>0) { $tpl->touchBlock('wpeol'); }
				$tpl->parse('waypoint');
			 }
			 		   
			 $vars['JOURNEYCOLOR'] = 'ff0000';
		   $vars['JOURNEYSIZE'] = '2';
			 
			} else {
			
		   $vars['STATICITEMID'] = $itemVO->itemId;
		   $vars['STATICITEMTITLE'] = $addon;
		   $vars['STATICWIDTH'] = 200;
		   $vars['STATICHEIGHT'] = 200;
		   $vars['STATICMARKERPOS'] = $position;
		   $vars['STATICJOURNEYCOLOR'] = 'ff0000';
		   $vars['STATICJOURNEYSIZE'] = '2';
		   $vars['MAPCLASS'] = 'mapsmall';
		   
		   while(count($merge)>0) {
			 	$wp = str_replace(' ','',trim(array_shift($merge)));
			 	$tpl->setVariable('STATICWPPOS',$wp);
			 	if(count($merge)>0) { $tpl->touchBlock('staticwpeol'); }
				$tpl->parse('staticwp');
			 }
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

	/**
	 * SUPPORT
	 * */
	static function proccessItemEnclosure($enclosure) {
		$ret = false;
		if($enclosure!='') {
			if (preg_match("/(jpeg|jpg|gif|bmp|png)$/i",$enclosure)) {
				$ret = '<img src="' . $enclosure . '" />';
			} elseif (preg_match("/^(http:\/\/)/",$enclosure)) {
				$ret = '<a href="' . $enclosure . '" rel="external">' . $enclosure . '</a>';
			} else $ret = $enclosure;
		}
		return $ret;
	}

}