<?php
class FItems extends FDBTool {

	const TYPE_DEFAULT = 'forum';

	//---current type
	private $typeId;
	//---list of ItemVOs
	public $data;
	//---using user permissions
	private $byPermissions = false;
	//---items removed because no access
	public $itemsRemoved = 0;
	
	//---options
	public $thumbInSysRes = false;


	function __construct() {
		parent::__construct('sys_pages_items as i','i.itemId');
		$this->fetchmode = 1;
	}

	static function TYPES_VALID() {
		return array('forum','galery','blog','event');
	}
	
	function initDetail($itemId) {
		$itemCheck = $this->getRow("select itemIdTop,typeId from sys_pages_items where itemId='".$itemId."'");
		if($itemCheck[0] > 0) {
			$this->itemIdInside = $itemId;
			$itemId = $itemCheck[0];
		}
		if($itemId > 0 && $this->showComments) {
			//---add discussion
			FForum::process($itemId);
		}
		$this->addWhere("i.itemId='".$itemId."'");
		if(!FRules::getCurrent(2)) {
			$this->addWhere('i.public = 1');
		}
		return $itemId;
	}

	function initData($typeId='forum', $byPermissions = false, $strictType=false) {
		$this->queryReset();
		if(!empty($typeId)) $this->typeId = $typeId;
		$doPagesJoin = true;
		//---check permissions for given user
		if($byPermissions!==false) { 
			$this->byPermissions = $byPermissions; 
		}
		//---strict type
		if(!empty($typeId) && $strictType==true) {
			$this->addWhere("i.typeId='".$typeId."'");
		}
		
		//---set select
		$this->setSelect($this->getTypeColumns($typeId));
		/*
		 * TODO: refactor
		if($this->showPageLabel==true || empty($typeId) || $typeId=='galery') {
			$this->fQuerySelectDefault['pageName'] = 'p.name';
			if($doPagesJoin) $this->addJoin("join sys_pages as p on p.pageId=i.pageId");
		}
		if(empty($typeId) || $typeId=='blog') {
			$user = FUser::getInstance();
			if($user->idkontrol===true) {
				$this->addJoin('left join sys_pages_items_readed_reactions as u on u.itemId=i.itemId and u.userId="'.$user->userVO->userId.'"');
				$this->fQuerySelectDefault['readedCnt'] = 'u.cnt as readed';
			}
		}
		*/
		//---check for public
		if(!FRules::getCurrent( 2 )) {
			$this->addWhere('i.public = 1');
		}
		
	}
	
	
	function getData($from=0, $count=0) {
		$this->arrData = array();
		$itemTypeId = $this->typeId;

		if($this->byPermissions === false) {
			$arr = $this->getContent($from, $count);
		} else {
			$itemsCount = 0;
			$page = 0;
			$arr = array();
			while(count($arr) < $count || $count==0) {
				$arrTmp = $this->getContent($from + ($page*$count), $count);
				$page++;
				if(empty($arrTmp)) break; //---no records
				else {
					$this->itemsRemoved = 0;
					foreach($arrTmp as $row) {
						//---check premissions
						if(FRules::get($this->byPermissions,$row[2],1)) {
							$arr[] = $row;
							$itemsCount++;
							if($itemsCount == $count && $count!=0) break;
						} else {
							//not permission for post
							$this->itemsRemoved++;
						}
					}
				}
				//---we have got all in once
				if($count == 0) break;
			}
		}

		if(!empty($arr)) {
			//---map items
			foreach($arr as $row) {
				$itemVO = new ItemVO();
				$itemVO->map( $row );
				if($this->thumbInSysRes) $itemVO->thumbInSysRes = true;
				$this->data[] = $itemVO;
			}
			
		}
		if($this->debug==1) print_r($this->data);
		return $this->data;
	}
	private $tpl;
	private $tplType;
	private $tplParsed = '';
	private $customTemplateName = '';
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
			$this->tpl = new fTemplateIT($this->getTemplateName($typeId));
			$this->tplType = $typeId;
		}
		return $this->tpl;
	}
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
	//---item enablers
	public $showPageLabel = false;
	
	public $showTag = true;
	public $showComments = false;
	public $showText = true;
	public $showTooltip = true;
	public $thumbInSysRes = false;
	public $openPopup = true;
	public $showRating = true;
	public $showHentryClass = true;
	public $showPocketAdd = true;
	public $showFooter = true;
	public $showHeading = true;
	public $currentHeader = '';
	public $itemIdInside = 0;
	public $showBottomItem = true;
	public $enableEdit = true;

	function pop() {
		if($this->arrData) return array_shift($this->arrData);
	}

	function parse($itemId=0) {
		$user = FUser::getInstance();
		if($itemId>0) {
			if(count($this->arrData)>1) {
				foreach ($this->arrData as $item) {
					if($item['itemId']==$itemId) $searchedItem = $item;
					else $arr[] = $item;
				}
				$arr = array_reverse($arr);
				$arr[] = $searchedItem;
				$this->arrData = array_reverse($arr);
			}
		}
		if($arr = $this->pop()) {
			//chechk permissions to edit
			$this->enableEdit = false;
			if(FRules::get($user->userVO->userId,$arr['pageId'],2) || $arr['userId']==$user->userVO->userId) {
				$this->enableEdit=true;
			}
			/*.........zacina vypis prispevku.........*/
			$tpl = $this->itemTpl($arr['typeId']);
			if($this->debug) print_r($this->tpl);
			$tpl->setCurrentBlock('item');
			//---common for all items
			if($this->showHentryClass==true) $tpl->touchBlock('hentry');
			$tpl->setVariable('ITEMIDHTML', 'i'.$arr['itemId']);
			$tpl->setVariable('ITEMID', $arr['itemId']);
			$tpl->setVariable('PAGEID', $arr['pageId']);
			$tpl->setVariable('DATELOCAL',$arr['dateLocal']);
			$tpl->setVariable('DATEISO',$arr['dateIso']);
			 
			if($arr['public'] != 1) {
				$tpl->touchBlock('notpublished');
				$tpl->touchBlock('notpublishedheader');
			}
			 
			if(isset($arr['name'])) $tpl->setVariable('AUTHOR',$arr['name']);
			if(!empty($arr['unread'])) $tpl->touchBlock('unread');
			if($this->enableEdit === true) {
				if(isset($arr['editItemId']) && $user->pageVO->pageId == $arr['pageId']) {
					$tpl->setVariable('EDITID',$arr['editItemId']); //--- FORUM/delete-BLOG/edit
					$tpl->setVariable('EDITPAGEID',$arr['pageId'].'u');
				}
			}
			if($this->showText==true && !empty($arr['text'])) $tpl->setVariable('TEXT',$arr['text']);
			//---event only
			if($arr['typeId']=='event') {
				if($arr['categoryId']>0) {
					$categoryArr = FPages::getCategory($arr['categoryId']);
					$tpl->setVariable('CATEGORY',$categoryArr[2]);
				}
				$tpl->setVariable('LOCATION',$arr['location']);

				$tpl->setVariable('STARTDATETIMEISO',$arr['startDateIso'].(($arr['startTime']!='00:00')?('T'.$arr['startTime']):('')));
				$tpl->setVariable('STARTDATETIMELOCAL',$arr['startDateLocal'].(($arr['startTime']!='00:00')?(' '.$arr['startTime']):('')));
				if(!empty($arr['endDateIso'])) {
					$tpl->setVariable('ENDDATETIMEISO',$arr['endDateIso'].(($arr['endTime']!='00:00')?('T'.$arr['endTime']):('')));
					$tpl->setVariable('ENDDATETIMELOCAL',$arr['endDateLocal'].(($arr['endTime']!='00:00')?(' '.$arr['endTime']):('')));
				}

				if(!empty($arr['enclosure'])) {
					$flyerFilenameThumb = FEvents::thumbUrl($arr['enclosure']);
					$flyerFilename = FEvents::flyerUrl($arr['enclosure']);
					if(!file_exists($flyerFilenameThumb)) {
						//---create thumb
						$conf = FConf::getInstance();
						$fImg = new FImgProcess($flyerFilename,$flyerFilenameThumb
						,array('quality'=>$conf->a['events']['thumb_quality']
						,'width'=>$conf->a['events']['thumb_width'],'height'=>0));
					}
					if(file_exists($flyerFilename)) {
						$arrSize = getimagesize($flyerFilename);
						$tpl->setVariable('BIGFLYERLINK',$flyerFilename.'?width='.($arrSize[0]+20).'&height='.($arrSize[1]+20));
						$tpl->setVariable('FLYERTHUMBURL',$flyerFilenameThumb);
						$tpl->setVariable('IMGEVENTTITLE',$arr['addon']);
						$tpl->setVariable('IMGEVENTALT',$arr['addon']);
					}
				} else {
					$tpl->setVariable('FLYERTHUMBURLDEFAULT',FUser::getSkinCSSFilename() . '/img/flyer_default.png');
				}
				if($this->showComments == true) {

					if($arr['tag_weight'] > 0) {

						$arrTags = FItems::getItemTagList($arr['itemId']);

						foreach ($arrTags as $tag) {
							$tpl->setCurrentBlock('participant');
							$tpl->setVariable('PARTICIPANTAVATAR',FAvatar::showAvatar($tag[0],array('showName'=>1)));
							$tpl->parseCurrentBlock();
						}
					}
				}
				if($this->showFooter) {
					if($user->userVO->userId == $arr['userId'] || FRules::getCurrent(2)) {
						$tpl->setVariable('EDITLINK','?k=eventu&amp;i='.$arr['itemId']);
					}
				}
			}
			//---forum only
			if($arr['typeId']=='forum') {
				if(!empty($arr['enclosure'])) $tpl->setVariable('ENCLOSURE',FItems::proccessItemEnclosure($arr['enclosure']));

				if($user->userVO->zavatar == 1) {
					$avatarId = (int) $arr['userId'];
					$avatar = FAvatar::showAvatar($avatarId);
					$tpl->setVariable('AVATAR',$avatar);
				}
			}
			 
			//---for logged users
			if ($user->idkontrol && $this->showFooter==true) {
				//---thumb tag link
				if($this->showTag==true) {
					FItems::initTagXajax();
					$cache = FCache::getInstance('s',60);
					if(false === $cache->getData($arr['itemId'],'itemTags')) $cache->setData($arr['tag_weight']);
					$tpl->setVariable('TAG',FItems::getTag($arr['itemId'],$user->userVO->userId,$arr['typeId']));
				}
				if($this->showPocketAdd==true) {
					$tpl->setVariable('POCKET',fPocket::getLink($arr['itemId']));
				}
				//---user link and location
				if($arr['userId'] > 0) {
					if($arr['typeId']!='galery') {
						$tpl->setVariable('AUTHORLINK','?k=finfo&who='.$arr['userId']);
						$tpl->touchBlock('authorlinkclose');
					}
					if($arr['typeId']=='forum') {
						if ($user->isOnline($arr['userId'])) {
							$kde = $user->getLocation($arr['userId']);
							$tpl->setVariable('USERLOCATION',$kde['name']);
							$tpl->setVariable('USERLOCATIONLINK','?k='.$kde['pageId'].$kde['param']);
						}
					}
				}
			}
			 
			if($this->showPageLabel==true) {
				$tpl->touchBlock('haspagelabel');
				$tpl->setVariable('PAGELINK','?k='.$arr['pageId'].'-'.FSystem::safetext($arr['pageName']).(($arr['typeId']=='forum')?('&i='.$arr['itemId'].'#i'.$arr['itemId']):('')));
				$tpl->setVariable('PAGENAME',$arr['pageName']);
			}
			//---BLOG / EVENT
			if(isset($arr['addon'])) {
				$link = '?k='.$arr['pageId'].'&amp;i='.$arr['itemId'].'-'.FSystem::safeText($arr['addon']);
				if($this->showHeading==true || $arr['typeId']=='event') {
					$tpl->setVariable('BLOGLINK',$link);
					$tpl->setVariable('BLOGTITLE',$arr['addon']);
				} else {
					$this->currentHeader = $arr['addon'];
				}

				if($this->showComments == true) {
					$writeRule = FPages::getProperty($arr['pageId'],'forumSet');
					if(false !== $itemWriteRule = FItems::getProperty($arr['itemId'],'forumSet',2)) $writeRule = $itemWriteRule;

					$tpl->setVariable('COMMENTS', FForum::show($arr['itemId'],$writeRule,$this->itemIdInside));
				} else {



					$tpl->setVariable('COMMENTLINK',$link);

					if(!isset($arr['commentsCnt'])) $arr['commentsCnt'] = 0;

					if(!isset($arr['readedCnt'])) $arr['readedCnt'] = $arr['commentsCnt'];
					$unReadedReactions = $arr['commentsCnt'] - ($arr['readedCnt'] * 1);

					if($unReadedReactions > 0) {
						$tpl->setVariable('ALLNEWCNT',$unReadedReactions);
					}

					$tpl->setVariable('CNTCOMMENTS',$arr['commentsCnt']);
				}
			}
			//---GALERY item
			if($arr['typeId'] == 'galery') {
				
				$tpl->setVariable('IMGALT',$arr['pageName'].' '.$arr['enclosure']);
				$tpl->setVariable('IMGTITLE',$arr['pageName'].' '.$arr['enclosure']);
				$tpl->setVariable('IMGURLTHUMB',$arr['thumbUrl']);
				$tpl->setVariable('ADDONSTYLEWIDTH',' style="width: '.$arr['width'].'px;"');
				//$tpl->setVariable('ADDONSTYLEHEIGHT',' style="height: '.$arr['height'].'px;"');
				if($this->showRating==true) $tpl->setVariable('HITS',$arr['hit']);

				if($this->openPopup) {
					$tpl->setVariable('IMGURLDETAIL',$arr['detailUrlToPopup']);
					$tpl->touchBlock('popupc');
					$tpl->setVariable('POPUPCLIGHTBOXGROUP','-'.$arr['pageId']);
				} else {
					$tpl->setVariable('IMGURLDETAIL',$arr['detailUrlToGalery']);
				}
				if($this->showTooltip) {
					$tpl->setVariable('ITEMIDTOOLTIP',$arr['itemId']);
					$tpl->setVariable('PAGEIDTOOLTIP',$arr['pageId']);
					$tpl->setVariable('LINKPOPUP',$arr['detailUrlToPopup']);
				}
			}
			//---linked item
			if($this->showBottomItem) {
				if($arr['itemIdBottom']>0) {
					$fItem = new FItems();
					$fItem->enableEdit = false;
					$fItem->showPageLabel = true;
					$fItem->initData('',$user->userVO->userId);
					$fItem->getItem($arr['itemIdBottom']);
					$fItem->getData();
					if(!empty($fItem->arrData)) {
						$fItem->parse();
						$tpl->setVariable('ITEMBOTTOM',$fItem->show());
					}
					unset($fItem);
				}
				if(!empty($arr['pageIdBottom'])) {
					if(FRules::get($user->userVO->userId,$arr['pageIdBottom'],1)) {
						$tpl->setVariable('ITEMBOTTOM','<h3><a href="?k='.$arr['pageIdBottom'].'">'.FPages::pageAttribute($arr['pageIdBottom']).'</a></h3>');
					}
				}

			}
			$tpl->parseCurrentBlock();

			if($this->debug) {
				print_r($tpl);
				print_r($arr);
				echo $tpl->get();
			}
			return $arr;
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


	/**
	 * chechk if item exists
	 */
	static function itemExists($itemId) {
		$q = "select count(1) from sys_pages_items where itemId='".$itemId."'";
		return $this->getOne($q,$itemId.'exist','fitems','l');
	}
}