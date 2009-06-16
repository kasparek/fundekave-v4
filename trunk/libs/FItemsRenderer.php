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
		
		if($this->tpl && $typeId!='' && $typeId!=$this->tplType) {
			$this->tplParsed .= $this->tpl->get();
			$this->tpl = false;
		}
		
		if(!$this->tpl && $typeId!='') {
			$this->tpl = new FTemplateIT($this->getTemplateName($typeId));
			$this->tplType = $typeId;
		}
		
		return $this->tpl;
	}



	function render( $itemVO ) {
		$user = FUser::getInstance();

		//---render item

		//chechk permissions to edit
		$enableEdit = false;
		if(FRules::get($user->userVO->userId,$itemVO->pageId,2) || $itemVO->userId == $user->userVO->userId) {
			$enableEdit=true;
		}
		/*.........zacina vypis prispevku.........*/
		$tpl = $this->itemTpl($itemVO->typeId);
		if($this->debug) print_r($this->tpl);
		$tpl->setCurrentBlock('item');
		//---common for all items
		if($this->showHentryClass==true) $tpl->touchBlock('hentry');
		$tpl->setVariable('ITEMIDHTML', 'i'.$itemVO->itemId);
		$tpl->setVariable('ITEMID', $itemVO->itemId);
		$tpl->setVariable('PAGEID', $itemVO->pageId);
		$tpl->setVariable('DATELOCAL', $itemVO->dateCreatedLocal);
		$tpl->setVariable('DATEISO', $itemVO->dateCreatedIso);

		if($itemVO->public != 1) {
			$tpl->touchBlock('notpublished');
			$tpl->touchBlock('notpublishedheader');
		}

		if(isset($itemVO->name)) $tpl->setVariable('AUTHOR',$itemVO->name);
		if($itemVO->unread === true) $tpl->touchBlock('unread');
		if($enableEdit === true) {
			if($itemVO->editable === true && $user->pageVO->pageId == $itemVO->pageId) {
				$tpl->setVariable('EDITID', $itemVO->itemId); //--- FORUM/delete-BLOG/edit
				$tpl->setVariable('EDITPAGEID', $itemVO->pageId.'u');
			}
		}
		if($this->showText==true && !empty($itemVO->text)) $tpl->setVariable('TEXT',$itemVO->text);

		switch($itemVO->typeId) {
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
				$tpl->setVariable('STARTDATETIMEISO',$itemVO->dateStartIso.(($itemVO->timeStart!='00:00')?('T'.$itemVO->timeStart):('')));
				$tpl->setVariable('STARTDATETIMELOCAL',$itemVO->dateStartLocal.(($itemVO->timeStart!='00:00')?(' '.$itemVO->timeStart):('')));
				if(!empty($itemVO->endDateIso)) {
					$tpl->setVariable('ENDDATETIMEISO',$itemVO->dateEndIso.(($itemVO->timeEnd!='00:00')?('T'.$itemVO->timeEnd):('')));
					$tpl->setVariable('ENDDATETIMELOCAL',$itemVO->dateEndLocal.(($itemVO->timeEnd!='00:00')?(' '.$itemVO->timeEnd):('')));
				}

				if(!empty($itemVO->enclosure)) {
					$flyerFilename = FEvents::flyerUrl($itemVO->enclosure);
					$flyerFilenameThumb = FEvents::thumbUrl($itemVO->enclosure);
					FEvents::createThumb($itemVO->enclosure);
					if(file_exists($flyerFilename)) {
						$arrSize = getimagesize($flyerFilename);
						$tpl->setVariable('BIGFLYERLINK',$flyerFilename.'?width='.($arrSize[0]+20).'&height='.($arrSize[1]+20));
						$tpl->setVariable('FLYERTHUMBURL',$flyerFilenameThumb);
						$tpl->setVariable('IMGEVENTTITLE',$itemVO->addon);
						$tpl->setVariable('IMGEVENTALT',$itemVO->addon);
					}
				} else {
					$tpl->setVariable('FLYERTHUMBURLDEFAULT',FUser::getSkinCSSFilename() . '/img/flyer_default.png');
				}
				if($this->showComments == true) {
					if($itemVO->tag_weight > 0) {
						$arrTags = FItems::getItemTagList($itemVO->itemId);
						foreach ($arrTags as $tag) {
							$tpl->setCurrentBlock('participant');
							$tpl->setVariable('PARTICIPANTAVATAR',FAvatar::showAvatar($tag[0],array('showName'=>1)));
							$tpl->parseCurrentBlock();
						}
					}
				}
				if($this->showFooter) {
					if($user->userVO->userId == $itemVO->userId || FRules::getCurrent(2)) {
						$tpl->setVariable('EDITLINK',FUser::getUri('i='.$itemVO->itemId,'event','u'));
					}
				}
				break;
			case 'forum':
				/**
				 * FORUM RENDERER
				 */
				if(!empty($itemVO->enclosure)) $tpl->setVariable('ENCLOSURE',FSystem::proccessItemEnclosure($itemVO->enclosure));
				if($user->userVO->zavatar == 1) {
					$avatar = FAvatar::showAvatar( (int) $itemVO->userId);
					$tpl->setVariable('AVATAR',$avatar);
				}
				break;
			case 'galery':
				/**
				 * GALERY RENDERER
				 */
				$pageVO  = new PageVO($itemVO->pageId,true);
				$tpl->setVariable('IMGALT',$pageVO->name.' '.$itemVO->enclosure);
				$tpl->setVariable('IMGTITLE',$pageVO->name.' '.$itemVO->enclosure);
				$tpl->setVariable('IMGURLTHUMB',$itemVO->thumbUrl);
				$tpl->setVariable('ADDONSTYLEWIDTH',' style="width: '.$itemVO->thumbWidth.'px;"');
				//$tpl->setVariable('ADDONSTYLEHEIGHT',' style="height: '.$itemVO->height.'px;"');
				if($this->showRating==true) $tpl->setVariable('HITS',$itemVO->hit);

				if($this->openPopup) {
					$tpl->setVariable('IMGURLDETAIL',$itemVO->detailUrlToPopup);
					$tpl->touchBlock('popupc');
					$tpl->setVariable('POPUPCLIGHTBOXGROUP','-'.$itemVO->pageId);
				} else {
					$tpl->setVariable('IMGURLDETAIL',$itemVO->detailUrlToGalery);
				}
				if($this->showTooltip) {
					$tpl->setVariable('ITEMIDTOOLTIP',$itemVO->itemId);
					$tpl->setVariable('PAGEIDTOOLTIP',$itemVO->pageId);
					$tpl->setVariable('LINKPOPUP',$itemVO->detailUrlToPopup);
				}
				break;
		}

		//---for logged users
		if ($user->idkontrol && $this->showFooter==true) {
			//---thumb tag link
			if($this->showTag==true) {
				$cache = FCache::getInstance('s',60);
				if(false === $cache->getData($itemVO->itemId,'itemTags')) $cache->setData($itemVO->tag_weight);
				$tpl->setVariable('TAG',FItemTags::getTag($itemVO->itemId,$user->userVO->userId,$itemVO->typeId));
			}
			if($this->showPocketAdd==true) {
				$tpl->setVariable('POCKET',FPocket::getLink($itemVO->itemId));
			}
			//---user link and location
			if($itemVO->userId > 0) {
				if($itemVO->typeId!='galery') {
					$tpl->setVariable('AUTHORLINK','?k=finfo&who='.$itemVO->userId);
					$tpl->touchBlock('authorlinkclose');
				}
				if($itemVO->typeId == 'forum') {
					if ($user->isOnline( $itemVO->userId )) {
						$kde = $user->getLocation( $itemVO->userId );
						$tpl->setVariable('USERLOCATION',$kde['name']);
						$tpl->setVariable('USERLOCATIONLINK','?k='.$kde['pageId'].$kde['param']);
					}
				}
			}
		}

		//---PAGE NAME
		if($this->showPageLabel==true) {
			$tpl->touchBlock('haspagelabel');
			$pageVO  = new PageVO($itemVO->pageId,true);
			$tpl->setVariable('PAGELINK',FUser::getUri((($itemVO->typeId=='forum')?('&i='.$itemVO->itemId.'#i'.$itemVO->itemId):('')),$itemVO->pageId));
			$tpl->setVariable('PAGENAME',$pageVO->name);
		}

		//---BLOG / EVENT
		if(isset($itemVO->addon)) {
			$link = FUser::getUri('i='.$itemVO->itemId.'-'.FSystem::safeText($itemVO->addon),$itemVO->pageId);
			if($this->showHeading==true) {
				$tpl->setVariable('BLOGLINK',$link);
				$tpl->setVariable('BLOGTITLE',$itemVO->addon);
			}
			if($this->showComments == true) {
				$writeRule = FPages::getProperty($itemVO->pageId,'forumSet');
				if(false !== ($itemWriteRule = ItemVO::getProperty($itemVO->itemId,'forumSet',2))) $writeRule = $itemWriteRule;
				$tpl->setVariable('COMMENTS', FForum::show($itemVO->itemId, $writeRule, $this->itemIdInside));
			} else {
				$tpl->setVariable('COMMENTLINK',$link);
				$unReadedReactions = $itemVO->getNumUnreadComments( $user->userVO->userId );
				if($unReadedReactions > 0) {
					$tpl->setVariable('ALLNEWCNT',$unReadedReactions);
				}
				$tpl->setVariable('CNTCOMMENTS',$itemVO->cnt);
			}
		}

		//---linked item
		if($this->showBottomItem) {
			if($itemVO->itemIdBottom > 0) {
				$itemVOBottom = new ItemVO($itemVO->itemIdBottom, true);
				if(FRules::get($user->userVO->userId, $itemVOBottom->pageId,1)) {
					$tpl->setVariable('ITEMBOTTOM',$itemVOBottom->render());
				}
			}
			if(!empty($itemVO->pageIdBottom)) {
				if(FRules::get($user->userVO->userId,$itemVO->pageIdBottom,1)) {
					$pageVO = new PageVO($itemVO->pageIdBottom,true);
					$tpl->setVariable('ITEMBOTTOM','<h3><a href="'.FUser::getUri('',$itemVO->pageIdBottom).'">'.$pageVO->name.'</a></h3>');
				}
			}

		}

		//---FINAL PARSE
		$tpl->parseCurrentBlock();

		if($this->debug) {
			print_r($tpl);
			print_r($itemVO);
			echo $tpl->get();
		}

	}

	function show() {
		$tpl = & $this->itemTpl();
		$ret = $this->tplParsed;
		$this->tplParsed = '';
		if($tpl) {
			if($this->debug) print_r(tpl);
			$ret .= $tpl->get();
			$tpl = false;
		}
		return $ret;
	}
}