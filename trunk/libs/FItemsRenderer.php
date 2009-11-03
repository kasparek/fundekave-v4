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
			$this->tpl = FSystem::tpl($this->getTemplateName($typeId));
			$this->tplType = $typeId;
		}
		$tpl = $this->tpl;

		//---common for all items
		if($this->showHentryClass === true) $touchedBlocks['hentry']=true;
		$vars['ITEMIDHTML'] = 'i'.$itemId;
		$vars['ITEMID'] = $itemId;
		$vars['ITEMLINK'] = FSystem::getUri('i='.$itemId,'');
		$vars['PAGEID'] = $pageId;
		$vars['DATELOCAL'] = $itemVO->dateCreatedLocal;
		$vars['DATEISO'] = $itemVO->dateCreatedIso;

		if($itemVO->public != 1) {
			$touchedBlocks['notpublished']=true;
			$touchedBlocks['notpublishedheader']=true;
		}

		if(isset($itemVO->name)) $vars['AUTHOR'] = $itemVO->name;
		if($itemVO->unread === true) $touchedBlocks['unread']=true;
		if($enableEdit === true) {
			if($itemVO->editable === true && $localUserPageId == $pageId) {
				$vars['EDITID'] = $itemId; //--- FORUM/delete-BLOG/edit
				$vars['EDITPAGEID'] = $pageId.'u';
			}
		}

		if($this->showText === true && $itemVO->text) {
			$text = $itemVO->text;
			//$words = explode(' ',$text);
			//$shorten = array_slice($words,0,150);
			//$text = FSystem::textins(implode(' ',$shorten));
			//$text = implode(' ',$shorten);
			//if blog and not in detail shorten text to 100words
			$vars['TEXT'] = $text;
		}
		/**/


		switch($typeId) {
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
					if(file_exists($flyerFilename)) {
						$flyerFilenameThumb = FEvents::thumbUrl($enclosure);
						//FEvents::createThumb($enclosure);
						$arrSize = getimagesize($flyerFilename);
						$vars['BIGFLYERLINK'] = $flyerFilename.'?width='.($arrSize[0]+20).'&height='.($arrSize[1]+20);
						$vars['FLYERTHUMBURL'] = $flyerFilenameThumb;
						$vars['IMGEVENTTITLE'] = $addon;
						$vars['IMGEVENTALT'] = $addon;
					}
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
						$vars['EDITLINK'] = FSystem::getUri('m=event-edit&d=result:fajaxContent;item:'.$itemId,'event','u');
					}
				}
				break;
			case 'forum':
				//--FORUM RENDERER
				if( $enclosure ) {
					$vars['ENCLOSURE'] = $this->proccessItemEnclosure($enclosure);
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
				if($this->showRating === true) $vars['HITS'] = $itemVO->hit;

				if( $this->openPopup === true ) {
					$vars['IMGURLDETAIL'] = $itemVO->detailUrlToPopup;
					$touchedBlocks['popupc'] = true;
					$vars['POPUPCLIGHTBOXGROUP'] = '-'.$pageId;
				} else {
					$vars['IMGURLDETAIL'] = $itemVO->detailUrlToGalery;
				}
				if($this->showTooltip === true) {
					$vars['ITEMIDTOOLTIP'] = $itemId;
					$vars['PAGEIDTOOLTIP'] = $pageId;
					$vars['LINKPOPUP'] = $itemVO->detailUrlToPopup;
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

		//---PAGE NAME
		if($this->showPageLabel === true) {
			$touchedBlocks['haspagelabel']=true;
			$pageVO = new PageVO($pageId,true);
			$vars['PAGELINK'] = FSystem::getUri((($typeId=='forum')?('i='.$itemId.'#i'.$itemId):('')),$pageId);
			$vars['PAGENAME'] = $pageVO->name;
			unset($pageVO);
		}
		/**/
		
		//---BLOG / EVENT
		if( $addon ) {
			$link = FSystem::getUri('i='.$itemId.'-'.FSystem::safeText($addon),$pageId);
			if($this->showHeading == true) {
				$vars['BLOGLINK'] = $link;
				$vars['BLOGTITLE'] = $addon;
			}
			if($this->showComments == true) {
				$writeRule = FPages::getProperty($pageId,'forumSet');
				if(false !== ($itemWriteRule = ItemVO::getProperty($itemId,'forumSet',2))) $writeRule = $itemWriteRule;
				$vars['COMMENTS'] = FForum::show($itemId, $writeRule, $this->itemIdInside);
			} else {
				$vars['COMMENTLINK'] = $link;
				$unReadedReactions = $itemVO->getNumUnreadComments( $localUserId );
				if($unReadedReactions > 0) {
					$vars['ALLNEWCNT'] = $unReadedReactions;
				}
				$vars['CNTCOMMENTS'] = $itemVO->cnt;
			}
		}
		/**/
		
		//---linked item
		if($this->showBottomItem === true) {
			if($itemVO->itemIdBottom > 0) {
				$itemVOBottom = new ItemVO($itemVO->itemIdBottom, true, array('showTooltip'=>false,'showPageLabel'=>true));
				if($itemVOBottom->typeId == 'galery') {
					$touchedBlocks['withCommented']=true;
					$touchedBlocks['commentedFloat']=true;
				}
				if(FRules::get($localUserId, $itemVOBottom->pageId,1)) {
					$vars['ITEMBOTTOM'] = $itemVOBottom->render();
				}
				unset($itemVOBottom);
			}
			if( $itemVO->pageIdBottom ) {
				if( FRules::get($localUserId,$itemVO->pageIdBottom,1) ) {
					$pageVO = new PageVO($itemVO->pageIdBottom,true);
					$vars['ITEMBOTTOM'] = '<h3><a href="'.FSystem::getUri('',$itemVO->pageIdBottom).'">'.$pageVO->name.'</a></h3>';
					unset($pageVO);
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
			if (preg_match("/(jpeg|jpg|gif|bmp|png|JPEG|JPG|GIF|BMP|PNG)$/",$enclosure)) {
				$ret = '<a href="'.$enclosure.'" rel="lightbox"><img src="' . $enclosure . '"></a>';
			} elseif (preg_match("/^(http:\/\/)/",$enclosure)) {
				$ret = '<a href="' . $enclosure . '" rel="external">' . $enclosure . '</a>';
			} else $ret = $enclosure;
		}
		return $ret;
	}

}