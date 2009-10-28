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
	public $showText = true;
	public $showTooltip = true;
	public $openPopup = true;
	public $showRating = true;
	public $showHentryClass = true;
	public $showPocketAdd = true;
	public $showFooter = true;
	public $showHeading = true;
	public $currentHeader = '';
	public $itemIdInside = 0;
	public $showBottomItem = true;
	public $thumbPreventCache = false;

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
			$this->tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
			$this->tpl->loadTemplatefile($this->getTemplateName($typeId));
			$this->tplType = $typeId;
		}
		$tpl = $this->tpl;

		$tpl->setCurrentBlock();
		//---common for all items
		if($this->showHentryClass === true) $tpl->touchBlock('hentry');
		$tpl->setVariable('ITEMIDHTML', 'i'.$itemId);
		$tpl->setVariable('ITEMID', $itemId);
		$tpl->setVariable('ITEMLINK', FSystem::getUri('i='.$itemId,''));
		$tpl->setVariable('PAGEID', $pageId);
		$tpl->setVariable('DATELOCAL', $itemVO->dateCreatedLocal);
		$tpl->setVariable('DATEISO', $itemVO->dateCreatedIso);

		if($itemVO->public != 1) {
			$tpl->touchBlock('notpublished');
			$tpl->touchBlock('notpublishedheader');
		}

		if(isset($itemVO->name)) $tpl->setVariable('AUTHOR',$itemVO->name);
		if($itemVO->unread === true) $tpl->touchBlock('unread');
		if($enableEdit === true) {
			if($itemVO->editable === true && $localUserPageId == $pageId) {
				$tpl->setVariable('EDITID', $itemId); //--- FORUM/delete-BLOG/edit
				$tpl->setVariable('EDITPAGEID', $pageId.'u');
			}
		}

		if($this->showText === true && $itemVO->text) {
			$text = $itemVO->text;
			//$words = explode(' ',$text);
			//$shorten = array_slice($words,0,150);
			//$text = FSystem::textins(implode(' ',$shorten));
			//$text = implode(' ',$shorten);
			//if blog and not in detail shorten text to 100words
			$tpl->setVariable('TEXT', $text);
		}
		/**/


		switch($typeId) {
			case 'event':
				//--EVENT RENDERER
				if($itemVO->categoryId > 0) {
					$categoryArr = FCategory::getCategory($itemVO->categoryId);
					$tpl->setVariable('CATEGORY',$categoryArr[2]);
				}
				$tpl->setVariable('LOCATION',$itemVO->location);
				$tpl->setVariable('STARTDATETIMEISO',$itemVO->dateStartIso.(($itemVO->dateStartTime!='00:00')?('T'.$itemVO->dateStartTime):('')));
				$tpl->setVariable('STARTDATETIMELOCAL',$itemVO->dateStartLocal.(($itemVO->dateStartTime!='00:00')?(' '.$itemVO->dateStartTime):('')));
				if(!empty($itemVO->dateEndIso)) {
					$tpl->setVariable('ENDDATETIMEISO',$itemVO->dateEndIso.(($itemVO->dateEndTime!='00:00')?('T'.$itemVO->dateEndTime):('')));
					$tpl->setVariable('ENDDATETIMELOCAL',$itemVO->dateEndLocal.(($itemVO->dateEndTime!='00:00')?(' '.$itemVO->dateEndTime):('')));
				}

				if(!empty($enclosure)) {
					$flyerFilename = FEvents::flyerUrl($enclosure);
					if(file_exists($flyerFilename)) {
						$flyerFilenameThumb = FEvents::thumbUrl($enclosure);
						//FEvents::createThumb($enclosure);
						$arrSize = getimagesize($flyerFilename);
						$tpl->setVariable('BIGFLYERLINK',$flyerFilename.'?width='.($arrSize[0]+20).'&height='.($arrSize[1]+20));
						$tpl->setVariable('FLYERTHUMBURL',$flyerFilenameThumb);
						$tpl->setVariable('IMGEVENTTITLE',$addon);
						$tpl->setVariable('IMGEVENTALT',$addon);
					}
				} else {
					$tpl->setVariable('FLYERTHUMBURLDEFAULT', $localCSS . '/img/flyer_default.png');
				}
				if($this->showComments === true) {
					if($itemVO->tag_weight > 0) {
						$arrTags = FItemTags::getItemTagList($itemId);
						foreach ($arrTags as $tag) {
							$tpl->setCurrentBlock('participant');
							$tpl->setVariable('PARTICIPANTAVATAR',FAvatar::showAvatar($tag[0],array('showName'=>1)));
							$tpl->parseCurrentBlock();
						}
					}
				}
				if($this->showFooter === true) {
					if($enableEdit === true) {
						$tpl->setVariable('EDITLINK', FSystem::getUri('m=event-edit&d=result:fajaxContent;item:'.$itemId,'event','u'));
					}
				}
				break;
			case 'forum':
				//--FORUM RENDERER
				if( $enclosure ) {
					$tpl->setVariable('ENCLOSURE',$this->proccessItemEnclosure($enclosure));
				}
				if( $localUserZavatar == 1 ) {
					$tpl->setVariable('AVATAR', FAvatar::showAvatar( (int) $itemUserId));
				}
				break;
			case 'galery':
				//--- GALERY RENDERER
				$pageVO  = new PageVO($pageId,true);
				$tpl->setVariable('IMGALT',$pageVO->name.' '.$enclosure);
				$tpl->setVariable('IMGTITLE',$pageVO->name.' '.$enclosure);
				$tpl->setVariable('IMGURLTHUMB',$itemVO->thumbUrl.(($this->thumbPreventCache)?('?r='.rand()):('')));
				$tpl->setVariable('ADDONSTYLEWIDTH',' style="width: '.$itemVO->thumbWidth.'px;"');
				//$tpl->setVariable('ADDONSTYLEHEIGHT',' style="height: '.$itemVO->height.'px;"');
				if($this->showRating === true) $tpl->setVariable('HITS',$itemVO->hit);

				if( $this->openPopup === true ) {
					$tpl->setVariable('IMGURLDETAIL',$itemVO->detailUrlToPopup);
					$tpl->touchBlock('popupc');
					$tpl->setVariable('POPUPCLIGHTBOXGROUP','-'.$pageId);
				} else {
					$tpl->setVariable('IMGURLDETAIL',$itemVO->detailUrlToGalery);
				}
				if($this->showTooltip === true) {
					$tpl->setVariable('ITEMIDTOOLTIP',$itemId);
					$tpl->setVariable('PAGEIDTOOLTIP',$pageId);
					$tpl->setVariable('LINKPOPUP',$itemVO->detailUrlToPopup);
				}
				unset($pageVO);
				break;
		}
		/**/
		
		//---for logged users
		if ($localUserIdkontrol === true && $this->showFooter === true) {
			//---thumb tag link
			if($this->showTag === true) {
				$tpl->setVariable('TAG', FItemTags::getTag($itemId,$localUserId,$typeId,$itemVO->tag_weight));
			}
			/*
			if($this->showPocketAdd === true) {
				$tpl->setVariable('POCKET',FPocket::getLink($itemId));
			}
			*/
			//---user link and location
			if($itemUserId > 0) {
				if($typeId != 'galery') {
					$tpl->setVariable('AUTHORLINK',FSystem::getUri('who='.$itemUserId,'finfo'));
					$tpl->touchBlock('authorlinkclose');
				}
				if($typeId == 'forum') {
					if (FUser::isOnline( $itemUserId )) {
						$kde = FUser::getLocation( $itemUserId );
						$tpl->setVariable('USERLOCATION',$kde['name']);
						$tpl->setVariable('USERLOCATIONLINK',FSystem::getUri('',$kde['pageId'],$kde['param']));
					}
				}
			}
		}
		/**/

		//---PAGE NAME
		if($this->showPageLabel === true) {
			$tpl->touchBlock('haspagelabel');
			$pageVO = new PageVO($pageId,true);
			$tpl->setVariable('PAGELINK',FSystem::getUri((($typeId=='forum')?('i='.$itemId.'#i'.$itemId):('')),$pageId));
			$tpl->setVariable('PAGENAME',$pageVO->name);
			unset($pageVO);
		}
		/**/
		
		//---BLOG / EVENT
		if( $addon ) {
			$link = FSystem::getUri('i='.$itemId.'-'.FSystem::safeText($addon),$pageId);
			if($this->showHeading == true) {
				$tpl->setVariable('BLOGLINK',$link);
				$tpl->setVariable('BLOGTITLE',$addon);
			}
			if($this->showComments == true) {
				$writeRule = FPages::getProperty($pageId,'forumSet');
				if(false !== ($itemWriteRule = ItemVO::getProperty($itemId,'forumSet',2))) $writeRule = $itemWriteRule;
				$tpl->setVariable('COMMENTS', FForum::show($itemId, $writeRule, $this->itemIdInside));
			} else {
				$tpl->setVariable('COMMENTLINK',$link);
				$unReadedReactions = $itemVO->getNumUnreadComments( $localUserId );
				if($unReadedReactions > 0) {
					$tpl->setVariable('ALLNEWCNT',$unReadedReactions);
				}
				$tpl->setVariable('CNTCOMMENTS',$itemVO->cnt);
			}
		}
		/**/
		
		//---linked item
		if($this->showBottomItem === true) {
			if($itemVO->itemIdBottom > 0) {
				$itemVOBottom = new ItemVO($itemVO->itemIdBottom, true, array('showTooltip'=>false,'showPageLabel'=>true));
				if($itemVOBottom->typeId == 'galery') {
					$tpl->touchBlock('withCommented');
					$tpl->touchBlock('commentedFloat');
				}
				if(FRules::get($localUserId, $itemVOBottom->pageId,1)) {
					$tpl->setVariable('ITEMBOTTOM',$itemVOBottom->render());
				}
				unset($itemVOBottom);
			}
			if( $itemVO->pageIdBottom ) {
				if( FRules::get($localUserId,$itemVO->pageIdBottom,1) ) {
					$pageVO = new PageVO($itemVO->pageIdBottom,true);
					$tpl->setVariable('ITEMBOTTOM','<h3><a href="'.FSystem::getUri('',$itemVO->pageIdBottom).'">'.$pageVO->name.'</a></h3>');
					unset($pageVO);
				}
			}
		}
		/**/
		
		//---FINAL PARSE
		$tpl->parseCurrentBlock();
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
			if (preg_match("/(jpeg|jpg|gif|bmp|png|JPEG|JPG|GIF|BMP|PNG)$/",$enclosure)) {
				$ret = '<a href="'.$enclosure.'" rel="lightbox"><img src="' . $enclosure . '"></a>';
			} elseif (preg_match("/^(http:\/\/)/",$enclosure)) {
				$ret = '<a href="' . $enclosure . '" rel="external">' . $enclosure . '</a>';
			} else $ret = $enclosure;
		}
		return $ret;
	}

}