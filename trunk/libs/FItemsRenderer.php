<?php
class FItemsRenderer {

	function __construct() {

	}
	
	var $debug = false;

	private $tpl;
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


	function &itemTpl($typeId='') {
		
		if(!empty($this->tpl) && $typeId!='' && $typeId != $this->tplType) {
			$this->tplParsed .= $this->tpl->get();
			unset($this->tpl);
		}
		
		if(empty($this->tpl) && $typeId!='') {
			$this->tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
			$this->tpl->loadTemplatefile($this->getTemplateName($typeId));
			$this->tplType = $typeId;
		}
		
		return $this->tpl;
	}

	function render( $itemVO ) {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;
		
		$itemId = $itemVO->itemId;
		$itemUserId = $itemVO->userId;
		$pageId = $itemVO->pageId;
		$typeId = $itemVO->typeId;
		$addon = $itemVO->addon;
    
		FProfiler::profile('FItemsRenderer::render--InSTANCES',true);
		
    	//---check permissions to edit
		$enableEdit = false;
		if(FRules::get($userId,$pageId,2) || $itemUserId == $userId) {
			$enableEdit=true;
		}
		FProfiler::profile('FItemsRenderer::render--EDITABLE CHECKED',true);
		/*.........zacina vypis prispevku.........*/
		$tpl = $this->itemTpl( $typeId );
		
		$tpl->setCurrentBlock();
		//---common for all items
		if($this->showHentryClass==true) $tpl->touchBlock('hentry');
		$tpl->setVariable('ITEMIDHTML', 'i'.$itemId);
		$tpl->setVariable('ITEMID', $itemId);
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
			if($itemVO->editable === true && $user->pageVO->pageId == $pageId) {
				$tpl->setVariable('EDITID', $itemId); //--- FORUM/delete-BLOG/edit
				$tpl->setVariable('EDITPAGEID', $pageId.'u');
			}
		}
		if($this->showText==true && !empty($itemVO->text)) $tpl->setVariable('TEXT',$itemVO->text);
		
		FProfiler::profile('FItemsRenderer::render--BASE DATA',true);

		switch($typeId) {
			case 'event':
				/**
				 * EVENT RENDERER
				 *
				 */
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

				if(!empty($itemVO->enclosure)) {
					$flyerFilename = FEvents::flyerUrl($itemVO->enclosure);
					if(file_exists($flyerFilename)) {
					 $flyerFilenameThumb = FEvents::thumbUrl($itemVO->enclosure);
					 //FEvents::createThumb($itemVO->enclosure);
						$arrSize = getimagesize($flyerFilename);
						$tpl->setVariable('BIGFLYERLINK',$flyerFilename.'?width='.($arrSize[0]+20).'&height='.($arrSize[1]+20));
						$tpl->setVariable('FLYERTHUMBURL',$flyerFilenameThumb);
						$tpl->setVariable('IMGEVENTTITLE',$addon);
						$tpl->setVariable('IMGEVENTALT',$addon);
					}
				} else {
					$tpl->setVariable('FLYERTHUMBURLDEFAULT',FUser::getSkinCSSFilename() . '/img/flyer_default.png');
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
					if($enableEdit===true) {
						$tpl->setVariable('EDITLINK', FUser::getUri('m=event-edit&d=result:fajaxContent;item:'.$itemId,'event','u'));
					}
				}
				break;
			case 'forum':
				/**
				 * FORUM RENDERER
				 */
				if(!empty($itemVO->enclosure)) {
					$tpl->setVariable('ENCLOSURE',FItemsRenderer::proccessItemEnclosure($itemVO->enclosure));	
				}
				if($user->userVO->zavatar == 1) {
					$tpl->setVariable('AVATAR', FAvatar::showAvatar( (int) $itemUserId));
				}
				break;
			case 'galery':
				/**
				 * GALERY RENDERER
				 */
				$pageVO  = new PageVO($pageId,true);
				$tpl->setVariable('IMGALT',$pageVO->name.' '.$itemVO->enclosure);
				$tpl->setVariable('IMGTITLE',$pageVO->name.' '.$itemVO->enclosure);
				$tpl->setVariable('IMGURLTHUMB',$itemVO->thumbUrl);
				$tpl->setVariable('ADDONSTYLEWIDTH',' style="width: '.$itemVO->thumbWidth.'px;"');
				//$tpl->setVariable('ADDONSTYLEHEIGHT',' style="height: '.$itemVO->height.'px;"');
				if($this->showRating==true) $tpl->setVariable('HITS',$itemVO->hit);

				if($this->openPopup) {
					$tpl->setVariable('IMGURLDETAIL',$itemVO->detailUrlToPopup);
					$tpl->touchBlock('popupc');
					$tpl->setVariable('POPUPCLIGHTBOXGROUP','-'.$pageId);
				} else {
					$tpl->setVariable('IMGURLDETAIL',$itemVO->detailUrlToGalery);
				}
				if($this->showTooltip) {
					$tpl->setVariable('ITEMIDTOOLTIP',$itemId);
					$tpl->setVariable('PAGEIDTOOLTIP',$pageId);
					$tpl->setVariable('LINKPOPUP',$itemVO->detailUrlToPopup);
				}
				unset($pageVO);
				break;
		}
		
		FProfiler::profile('FItemsRenderer::render--TYPE CUSTOM',true);

		//---for logged users
		if ($user->idkontrol === true && $this->showFooter === true) {
			//---thumb tag link
			if($this->showTag === true) {
				$tpl->setVariable('TAG', FItemTags::getTag($itemId,$userId,$typeId,$itemVO->tag_weight));
			}
			if($this->showPocketAdd === true) {
				$tpl->setVariable('POCKET',FPocket::getLink($itemId));
			}
			//---user link and location
			if($itemUserId > 0) {
				if($typeId != 'galery') {
					$tpl->setVariable('AUTHORLINK',FUser::getUri('who='.$itemUserId,'finfo'));
					$tpl->touchBlock('authorlinkclose');
				}
				if($typeId == 'forum') {
					if (FUser::isOnline( $itemUserId )) {
						$kde = FUser::getLocation( $itemUserId );
						$tpl->setVariable('USERLOCATION',$kde['name']);
						$tpl->setVariable('USERLOCATIONLINK',FUser::getUri('',$kde['pageId'],$kde['param']));
					}
				}
			}
		}
		FProfiler::profile('FItemsRenderer::render--REGISTERED USERS',true);

		//---PAGE NAME
		if($this->showPageLabel==true) {
			$tpl->touchBlock('haspagelabel');
			$pageVO = new PageVO($pageId,true);
			$tpl->setVariable('PAGELINK',FUser::getUri((($typeId=='forum')?('&i='.$itemId.'#i'.$itemId):('')),$pageId));
			$tpl->setVariable('PAGENAME',$pageVO->name);
			unset($pageVO);
		}
		FProfiler::profile('FItemsRenderer::render--PAGE NAME',true);

		//---BLOG / EVENT
		if(!empty($addon)) {
			$link = FUser::getUri('i='.$itemId.'-'.FSystem::safeText($addon),$pageId);
			if($this->showHeading==true) {
				$tpl->setVariable('BLOGLINK',$link);
				$tpl->setVariable('BLOGTITLE',$addon);
			}
			if($this->showComments == true) {
				$writeRule = FPages::getProperty($pageId,'forumSet');
				if(false !== ($itemWriteRule = ItemVO::getProperty($itemId,'forumSet',2))) $writeRule = $itemWriteRule;
				$tpl->setVariable('COMMENTS', FForum::show($itemId, $writeRule, $this->itemIdInside));
			} else {
				$tpl->setVariable('COMMENTLINK',$link);
				$unReadedReactions = $itemVO->getNumUnreadComments( $userId );
				if($unReadedReactions > 0) {
					$tpl->setVariable('ALLNEWCNT',$unReadedReactions);
				}
				$tpl->setVariable('CNTCOMMENTS',$itemVO->cnt);
			}
		}
		FProfiler::profile('FItemsRenderer::render--ADDON PRESENT',true);

		//---linked item
		if($this->showBottomItem === true) {
			if($itemVO->itemIdBottom > 0) {
				$itemVOBottom = new ItemVO($itemVO->itemIdBottom, true, array('showPageLabel'=>true));
				if(FRules::get($userId, $itemVOBottom->pageId,1)) {
					$tpl->setVariable('ITEMBOTTOM',$itemVOBottom->render());
				}
				unset($itemVOBottom);
			}
			if(!empty($itemVO->pageIdBottom)) {
				if(FRules::get($userId,$itemVO->pageIdBottom,1)) {
					$pageVO = new PageVO($itemVO->pageIdBottom,true);
					$tpl->setVariable('ITEMBOTTOM','<h3><a href="'.FUser::getUri('',$itemVO->pageIdBottom).'">'.$pageVO->name.'</a></h3>');
					unset($pageVO);
				}
			}
		}
		FProfiler::profile('FItemsRenderer::render--LINK BOTTOM ITEM',true);

		//---FINAL PARSE
		$tpl->parseCurrentBlock();
		FProfiler::profile('FItemsRenderer::render--PARSE',true);

	}

	function show() {
		$tpl = $this->tpl;
		$ret = $this->tplParsed;
		$this->tplParsed = '';
		if($tpl) {
			$ret .= $tpl->get();
			unset($this->tpl);
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