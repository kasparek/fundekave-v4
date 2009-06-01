<?php
class FItems extends FDBTool {

	const TYPE_DEFAULT = 'forum';

	//---current type
	private $typeId;
	//---list of ItemVOs
	public $itemsList;
	//---using user permissions
	private $byPermissions = false;
	//---items removed because no access
	public $itemsRemoved = 0;


	function __construct() {
		parent::__construct('sys_pages_items as i','i.itemId');
	}

	static function TYPES_VALID() {
		return array('forum','galery','blog','event');
	}

	private $fQuerySelectDefault = array('itemId'=>'i.itemId','userId'=>'i.userId','pageId'=>'i.pageId','text'=>'i.text','enclosure'=>'i.enclosure','tag_weight'=>'i.tag_weight','pageIdBottom'=>'i.pageIdBottom','itemIdBottom'=>'i.itemIdBottom','public'=>'i.public');

	private $fQuerySelectType = array('galery'=>array('galeryDir'=>'p.galeryDir','addon'=>'i.addon','hit'=>'i.hit','filesize'=>'i.filesize','pageParams'=>'p.pageParams','pageDateUpdated'=>'p.dateUpdated','pageName'=>'p.name','dateLocal'=>"date_format(i.dateCreated ,'{#datetime_local#}')",'dateIso'=>"date_format(i.dateCreated ,'{#datetime_iso#}')"),
	'blog'=>array('name'=>'i.name','addon'=>'i.addon','commentsCnt'=>'i.cnt','dateLocal'=>"date_format(i.dateCreated ,'{#date_local#}')",'dateIso'=>"date_format(i.dateCreated ,'{#date_iso#}')"),
	'forum'=>array('name'=>'i.name','dateLocal'=>"date_format(i.dateCreated ,'{#datetime_local#}')",'dateIso'=>"date_format(i.dateCreated ,'{#datetime_iso#}')",),
	'event'=>array('name'=>'i.name','addon'=>'i.addon','commentsCnt'=>'i.cnt','categoryId'=>'i.categoryId','location'=>'i.location','startDateLocal'=>"date_format(i.dateStart ,'{#date_local#}')",'startDateIso'=>"date_format(i.dateStart ,'{#date_iso#}')",'endDateLocal'=>"date_format(i.dateEnd ,'{#date_local#}')",'endDateIso'=>"date_format(i.dateEnd ,'{#date_iso#}')",'startTime'=>"date_format(i.dateStart ,'{#time_short#}')",'endTime'=>"date_format(i.dateEnd ,'{#time_short#}')",'dateLocal'=>"date_format(i.dateCreated ,'{#date_local#}')",'dateIso'=>"date_format(i.dateCreated ,'{#date_iso#}')"));

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
		if($byPermissions!==false) { 
			$this->byPermissions = $byPermissions; 
		}
		if(empty($typeId)) {
			$this->fQuerySelectDefault['typeId'] = 'i.typeId';
		} elseif($strictType==true) {
			$this->addWhere("i.typeId='".$typeId."'");
		}
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

		if(!FRules::getCurrent( 2 )) {
			$this->addWhere('i.public = 1');
		}

		$this->setSelect($this->getTypeColumns($typeId));

	}
	function getTypeColumns($typeId,$getKeysArray=false) {
		if(!empty($typeId)) $arrSelect = array_merge($this->fQuerySelectDefault,$this->fQuerySelectType[$typeId]);
		else {
			$arrSelect = $this->fQuerySelectDefault;
			foreach($this->fQuerySelectType as $arrTmp) $arrSelect = array_merge($arrSelect,$arrTmp);
		}
		if($getKeysArray) return array_keys($arrSelect);
		else return implode(",",$arrSelect);
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

		$arrCols = $this->getTypeColumns($this->typeId,true);

		if(!empty($arr)) {
			if($this->typeId=='galery' || $this->typeId=='') {
				$galery = new FGalery();
				//---TODO: set itemVO - systhumb = true
				if($this->thumbInSysRes) $galery->thumbInSysRes = true;
			}
			foreach($arr as $row) {
				$arrColsLength = count($arrCols);
				for($x=0;$x<$arrColsLength;$x++) {
					$namedRow[$arrCols[$x]] = $row[$x];
				}
				if(isset($namedRow['typeId'])) $itemTypeId = $namedRow['typeId'];
				else $namedRow['typeId'] = $itemTypeId;
				switch ($itemTypeId) {

					case 'forum':
						if(FForum::isUnreadedMess($namedRow['itemId'])) $namedRow['unread'] = 1; else $namedRow['unread'] = 0;
						break;

					case 'galery':
						//--galery process
						$namedRow = $galery->prepare($namedRow);
						break;

				}
				$user = FUser::getInstance();
				if (($user->userVO->userId > 0 && $user->userVO->userId == $namedRow['userId']) || FRules::get($user->userVO->userId,$namedRow['pageId'],2)) $namedRow['editItemId'] = $namedRow['itemId'];
				$this->arrData[] = $namedRow;
			}
		}
		if($this->debug==1) print_r($this->arrData);
		return $this->arrData;
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