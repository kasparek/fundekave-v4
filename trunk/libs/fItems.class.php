<?php
class fItems extends fQueryTool {
	
	static function TYPES_VALID() { 
    	return array('forum','galery','blog','event');
	}
    const TYPE_DEFAULT = 'forum';
    
    function __construct() {
     global $db;
     parent::__construct('sys_pages_items as i','i.itemId',$db);
    }
    /**
     * TAGGING section
     *
     * @param number $itemId
     * @param number $userId
     * @param tinyint $weight
     * @param varchar(255) $tag
     * @return boolean
     */
    static function itemExists($itemId) {
        global $db;
        return $db->getOne("select count(1) from sys_pages_items where itemId='".$itemId."'");
    }
    static function tag($itemId,$userId,$weight=1,$tag='') {
        global $db,$user;
        $user->arrUsers['mytags'] = array();
        $db->query('update sys_pages_items set tag_weight=tag_weight+1 where itemId="'.$itemId.'"');
        return $db->query("insert into sys_pages_items_tag values 
        ('".$itemId."','".$userId."',".(($tag!='')?("'".fSystem::textins($tag,array('plainText'=>1))."'"):('null')).",'".($weight*1)."',now())");
    }
    static function removeTag($itemId,$userId) {
        global $db,$user;
        if($db->getOne("select count(1) from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'")) {
            $db->query('update sys_pages_items set tag_weight=tag_weight-1 where itemId="'.$itemId.'"');
            $user->resetGroupTimeCache('itemTags');
            unset($user->arrUsers['mytags']);
            return $db->query("delete from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'");
        }
    }
    static function isTagged($itemId,$userId) {
        global $db,$user;
        if($itemId>0 && $userId>0) {
          if(isset($user->arrUsers['mytags'][$userId][$itemId])) return $user->arrUsers['mytags'][$userId][$itemId];
          else {
            return $user->arrUsers['mytags'][$userId][$itemId] = $db->getOne("select count(1) from sys_pages_items_tag where userId='".$userId."' and itemId='".$itemId."'");
          }
        }
    }
    static function totalTags($itemId) {
        global $db,$user;
        if($itemId > 0) {
          $ret = $user->getTimeCache('itemTags',$itemId,60);
          if($ret === false) {
            $ret = $db->getOne("select sum(weight) from sys_pages_items_tag where itemId='".$itemId."'");
            $user->saveTimeCache($ret);
          }
          return $ret;
        }
    }
    static function initTagXajax() {
        fXajax::register('user_tag');
    }
    static function tagLabel($itemId,$typeId='') {
        global $TAGLABELS,$db;
        if($typeId=='') $typeId = $db->getOne("select typeId from sys_pages_items where itemId='".$itemId."'");
        $totalTags = fItems::totalTags($itemId);
        $tagLabel = '';
        if($totalTags == 1) $tagLabel = '1 '.$TAGLABELS[$typeId][1];
        elseif ($totalTags > 1 && $totalTags < 5) $tagLabel = $totalTags.' '.$TAGLABELS[$typeId][2];
        elseif ($totalTags >= 5) $tagLabel = $totalTags.' '.$TAGLABELS[$typeId][3];
        return $tagLabel;
    }
    static function getTagLink($itemId,$userId,$typeId='',$removable=false) {
        global $db,$user,$TAGLABELS;
        if($typeId=='') $typeId = $db->getOne("select typeId from sys_pages_items where itemId='".$itemId."'");
        if(fItems::isTagged($itemId,$userId)) {
            return '<span class="tagIs">'.fItems::tagLabel($itemId,$typeId).(($removable==true)?(' <a href="'.$user->getUri('rt='.$itemId).'">'.$TAGLABELS[$typeId][4].'</a>'):('')).'</span>';
        } else {
            return '<span id="tag'.$itemId.'" class="tagMe"><a href="?k='.$user->currentPageId.'&t='.$itemId.'" class="tagLink" id="t'.$itemId.'">'.$TAGLABELS[$typeId][0].'</a> '.fItems::tagLabel($itemId).'</span>';
        }
    }
    static function getItemTagList($itemId) {
        global $db;
        $arr = $db->getAll("select userId,tag,weight from sys_pages_items_tag where itemId='".$itemId."'");
        return $arr;
    }
    /** 
     * item print functions
     **/
    
    public $arrData;
    public $itemsRemoved = 0;
    private $typeId;
    private $byPermissions = false;
    private $fQuerySelectDefault = array('itemId'=>'i.itemId','userId'=>'i.userId','pageId'=>'i.pageId',
        'text'=>'i.text',
        'enclosure'=>'i.enclosure','tag_weight'=>'i.tag_weight',
        'pageIdBottom'=>'i.pageIdBottom','itemIdBottom'=>'i.itemIdBottom'
        );
    private $fQuerySelectType = array(
        'galery'=>array('galeryDir'=>'p.galeryDir', 
            'addon'=>'i.addon','hit'=>'i.hit','filesize'=>'i.filesize',
            'pageParams'=>'p.pageParams','pageDateUpdated'=>'p.dateUpdated',
            'pageName'=>'p.name',
            'dateLocal'=>"date_format(i.dateCreated ,'{#datetime_local#}')"
            ,'dateIso'=>"date_format(i.dateCreated ,'{#datetime_iso#}')"
            ),
            
        'blog'=>array('name'=>'i.name','addon'=>'i.addon','commentsCnt'=>'i.cnt',
            'dateLocal'=>"date_format(i.dateCreated ,'{#date_local#}')",
            'dateIso'=>"date_format(i.dateCreated ,'{#date_iso#}')"
            ),
                
        'forum'=>array('name'=>'i.name','dateLocal'=>"date_format(i.dateCreated ,'{#datetime_local#}')",
            'dateIso'=>"date_format(i.dateCreated ,'{#datetime_iso#}')",
            ),
         'event'=>array('name'=>'i.name','addon'=>'i.addon','commentsCnt'=>'i.cnt','categoryId'=>'i.categoryId','location'=>'i.location',
            'startDateLocal'=>"date_format(i.dateStart ,'{#date_local#}')",
            'startDateIso'=>"date_format(i.dateStart ,'{#date_iso#}')",
            'endDateLocal'=>"date_format(i.dateEnd ,'{#date_local#}')",
            'endDateIso'=>"date_format(i.dateEnd ,'{#date_iso#}')",
            'startTime'=>"date_format(i.dateStart ,'{#time#}')",
            'endTime'=>"date_format(i.dateEnd ,'{#time#}')",
            'dateLocal'=>"date_format(i.dateCreated ,'{#date_local#}')",
            'dateIso'=>"date_format(i.dateCreated ,'{#date_iso#}')"
            ),
        );
    function initDetail($itemId) {
        $itemCheck = $this->db->getRow("select itemIdTop,typeId from sys_pages_items where itemId='".$itemId."'");
        if($itemCheck[0]>0) {
            $this->itemIdInside =$itemId;
            $itemId = $itemCheck[0];
        }
        if($itemId > 0 && $this->showComments) {
    	    //---add discussion
    	    fForum::process($itemId);
    	}
        $this->addWhere("i.itemId='".$itemId."'");
        return $itemId;
    }
    function initData($typeId='forum',$byPermissions = false,$strictType=false) {
      global $db,$user;
      $this->queryReset();
      if(!empty($typeId)) $this->typeId = $typeId;
      $doPagesJoin = true;
      if($byPermissions!==false) { $this->byPermissions = $byPermissions; }
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
        if($user->idkontrol) {
          $this->addJoin('left join sys_pages_items_readed_reactions as u on u.itemId=i.itemId and u.userId="'.$user->gid.'"');
          $this->fQuerySelectDefault['unReadedCnt'] = '(i.cnt-u.cnt)';
        }
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
      global $user;
        $this->arrData = array();
        $itemTypeId = $this->typeId;
        
        if($this->byPermissions===false) {
          $arr = $this->getContent($from, $count);
        } else {
          $itemsCount = 0;
          $page = 0;
          $arr = array();
          while(count($arr) < $count || $count==0) {
            $arrTmp = $this->getContent($from+($page*$count), $count);
            $page++;
            if(empty($arrTmp)) break;
            else {
              $this->itemsRemoved = 0;
              foreach($arrTmp as $row) {
                if(fRules::get($this->byPermissions,$row[2],1)) {
                    $arr[]=$row;
                    $itemsCount++;
                    if($itemsCount == $count && $count!=0) break;
                } else {
                    //not permission for post
                    $this->itemsRemoved++;
                }
              }
            }
            if($count == 0) break;
          }
        }        
        
        $arrCols = $this->getTypeColumns($this->typeId,true);
        
        if(!empty($arr)) {
          if($this->typeId=='galery' || $this->typeId=='') {
              $galery = new fGalery();
              if($this->thumbInSysRes) $galery->_thumbInSysRes = true;
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
              if(fForum::isUnreadedMess($namedRow['itemId'])) $namedRow['unread'] = 1; else $namedRow['unread'] = 0;
              break;
              
              case 'galery':
              //--galery process
              $namedRow = $galery->prepare($namedRow);
              break;
              
            }
            if (($user->gid > 0 && $user->gid == $namedRow['userId']) || fRules::get($user->gid,$namedRow['pageId'],2)) $namedRow['editItemId'] = $namedRow['itemId'];
            $this->arrData[] = $namedRow;
          }
        }
      if($this->debug==1) print_r($this->arrData);
    }            
    private $tpl;
    private $tplType;
    private $tplParsed = '';
    function &itemTpl($typeId='') {
      if($this->tpl && $typeId!='' && $typeId!=$this->tplType) {
        $this->tplParsed .= $this->tpl->get();
        $this->tpl = false;
      }
      if(!$this->tpl && $typeId!='') {
        $this->tpl = new fTemplateIT('item.'.$typeId.'.tpl.html');
        $this->tplType = $typeId;
      }
      return $this->tpl; 
    }
    static function proccessItemEnclosure($enclosure) {
        $ret = false;
		if($enclosure!='') {
		  if (preg_match("/(jpeg|jpg|gif|bmp|png|JPEG|JPG|GIF|BMP|PNG)$/",$enclosure)) {
		      $ret = '<a href="'.$enclosure.'" target="_blank"><img src="' . $enclosure . '"></a>';
		  } elseif (preg_match("/^(http:\/\/)/",$enclosure)) {
		      $ret = '<a href="' . $enclosure . '" target="_blank">' . $enclosure . '</a>';
		  } else $ret = $enclosure;
		}
		return $ret;
    }
    //---item enablers
    public $showPageLabel = false;
    public $xajaxSwitch = false;
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
  		global $user,$conf;
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
  		    $this->enableEdit=false;
  		    if(fRules::get($user->gid,$arr['pageId'],2) || $arr['userId']==$user->gid) {
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
  	if(isset($arr['name'])) $tpl->setVariable('AUTHOR',$arr['name']);
  	if(!empty($arr['unread'])) $tpl->touchBlock('unread');
  	if($this->enableEdit==true) {
  	 if(isset($arr['editItemId']) && $user->currentPageId == $arr['pageId']) {
  	     $tpl->setVariable('EDITID',$arr['editItemId']); //--- FORUM/delete-BLOG/edit
  	 }
  	}
  	if($this->showText==true && !empty($arr['text'])) $tpl->setVariable('TEXT',$arr['text']);
  	//---event only
  	if($arr['typeId']=='event') {
  	    if($arr['categoryId']>0) {
  	      $categoryArr = fPages::getCategory($arr['categoryId']);
  	      $tpl->setVariable('CATEGORY',$categoryArr[2]);
  	    }
  	    $tpl->setVariable('LOCATION',$arr['location']);
  	    
  	    $tpl->setVariable('STARTDATETIMEISO',$arr['startDateIso'].(($arr['startTime']!='00:00:00')?(' '.$arr['startTime']):('')));
  	    $tpl->setVariable('STARTDATETIMELOCAL',$arr['startDateLocal'].(($arr['startTime']!='00:00:00')?('T'.$arr['startTime']):('')));
  	    if(!empty($arr['endDateIso'])) {
      	    $tpl->setVariable('ENDDATETIMEISO',$arr['endDateIso'].(($arr['endTime']!='00:00')?(' '.$arr['endTime']):('')));
      	    $tpl->setVariable('ENDDATETIMELOCAL',$arr['endDateLocal'].(($arr['endTime']!='00:00')?('T'.$arr['endTime']):('')));
  	    }
  	    
  	    if(!empty($arr['enclosure'])) {
            $flyerFilenameThumb = fEvents::thumbUrl($arr['enclosure']);
            $flyerFilename = fEvents::flyerUrl($arr['enclosure']);
            if(!file_exists($flyerFilenameThumb)) {
            //---create thumb
            	$fImg = new fImgProcess($flyerFilename,$flyerFilenameThumb
                ,array('quality'=>$conf['events']['thumb_quality']
                ,'width'=>$conf['events']['thumb_width'],'height'=>0));
            }
            if(file_exists($flyerFilename)) {
      	        $arrSize = getimagesize($flyerFilename);
          	    $tpl->setVariable('BIGFLYERLINK',$flyerFilename.'?width='.($arrSize[0]+20).'&height='.($arrSize[1]+20));
          	    $tpl->setVariable('FLYERTHUMBURL',$flyerFilenameThumb);
          	    $tpl->setVariable('IMGEVENTTITLE',$arr['addon']);
          	    $tpl->setVariable('IMGEVENTALT',$arr['addon']);
            }
  	    }
  	    if($this->showComments == true) {
  	     
  	     if($arr['tag_weight'] > 0) {
  	     
  	         $arrTags = fItems::getItemTagList($arr['itemId']);
  	     
  	         foreach ($arrTags as $tag) {
  	             $tpl->setCurrentBlock('participant');
  	             $tpl->setVariable('PARTICIPANTAVATAR',$user->showAvatar($tag[0],array('showName'=>1)));
  	             $tpl->parseCurrentBlock();
  	         }
  	     }
  	    }
  	    if($this->showFooter) {
  	        if($user->gid == $arr['userId'] || fRules::get($user->gid,$user->currentPageId,2)) $tpl->setVariable('EDITLINK','?k=eventu&i='.$arr['itemId']);
  	    }
  	}
  	//---forum only
  	if($arr['typeId']=='forum') {
      if(!empty($arr['enclosure'])) $tpl->setVariable('ENCLOSURE',fItems::proccessItemEnclosure($arr['enclosure']));
      
      if($user->zidico==1) {
          $avatarId = (int) $arr['userId'];
          $avatar = $user->showAvatar($avatarId);
          $tpl->setVariable('AVATAR',$avatar);
      }
  	}
  	
  	//---for logged users
  	if ($user->idkontrol && $this->showFooter==true) {
     //---thumb tag link
  	 if($this->showTag==true) {
        fItems::initTagXajax();
        if(false === $user->getTimeCache('itemTags',$arr['itemId'],60)) $user->saveTimeCache($arr['tag_weight']);
        $removableTag = false;
        if($arr['typeId']=='event') $removableTag = true;
        $tpl->setVariable('TAG',fItems::getTagLink($arr['itemId'],$user->gid,$arr['typeId'],$removableTag));
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
      $tpl->setVariable('PAGELINK','?k='.$arr['pageId'].(($arr['typeId']=='forum')?('&i='.$arr['itemId'].'#i'.$arr['itemId']):('')));
      $tpl->setVariable('PAGENAME',$arr['pageName']);
    }
    //---BLOG
    if(isset($arr['addon'])) {
      $link = '?k='.$arr['pageId'].'&i='.$arr['itemId'].'-'.fSystem::safeText($arr['addon']);
      if($this->showHeading==true) {
        $tpl->setVariable('BLOGLINK',$link);
        $tpl->setVariable('BLOGTITLE',$arr['addon']);
      } else {
          $this->currentHeader = $arr['addon'];
      }
      
      if($this->showComments == true) {
        $tpl->setVariable('COMMENTS', fForum::show($arr['itemId'],$user->idkontrol,$this->itemIdInside));
      } else {
        $tpl->setVariable('COMMENTLINK',$link);
        if(isset($arr['unReadedCnt'])) {
        	if($arr['unReadedCnt'] > 0) $tpl->setVariable('ALLNEWCNT',$arr['unReadedCnt']);
        }
        if(!isset($arr['commentsCnt'])) $arr['commentsCnt'] = 0;
        $tpl->setVariable('CNTCOMMENTS',$arr['commentsCnt']);
    	}
    }
    //---GALERY item
    if($arr['typeId'] == 'galery') {
      if($user->idkontrol==true) if($this->xajaxSwitch==true) $tpl->touchBlock('xajaxSwitch');
      $tpl->setVariable('IMGALT',$arr['pageName'].' '.$arr['enclosure']);
      $tpl->setVariable('IMGTITLE',$arr['pageName'].' '.$arr['enclosure']);
      $tpl->setVariable('IMGURLTHUMB',$arr['thumbUrl']);
      $tpl->setVariable('ADDONSTYLEWIDTH',' style="width: '.$arr['width'].'px;"');
      $tpl->setVariable('ADDONSTYLEHEIGHT',' style="height: '.$arr['height'].'px;"');
      if($this->showRating==true) $tpl->setVariable('HITS',$arr['hit']);
      
      if($this->openPopup) {
        $tpl->setVariable('IMGURLDETAIL',$arr['detailUrlToPopup']);
        $tpl->touchBlock('popupc');
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
            $fItem = new fItems();
            $fItem->enableEdit = false;
            $fItem->showPageLabel = true;
            $fItem->initData('',$user->gid);
            $fItem->getItem($arr['itemIdBottom']);
            $fItem->getData();
            if(!empty($fItem->arrData)) {
                $fItem->parse();
                $tpl->setVariable('ITEMBOTTOM',$fItem->show());
                unset($fItem);
            }
        }
        if(!empty($arr['pageIdBottom'])) {
            if(fRules::get($user->gid,$arr['pageIdBottom'],1)) {
                $tpl->setVariable('ITEMBOTTOM','<h3><a href="?k='.$arr['pageIdBottom'].'">'.fPages::pageAttribute($arr['pageIdBottom']).'</a></h3>');
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
    * FILTER TOOLBAR FUNCTIONS
    * */    
    static function &getTagToolbarData() {
      global $user;
      $arr = & $user->arrTagItems[$user->currentPageId];
      if(!isset($arr['order'])) $arr['order'] = 0;
      if(!isset($arr['date'])) $arr['date'] = 0;
      if(!isset($arr['interval'])) $arr['interval'] = 1;
      if(($arr['order'] == 0 && $arr['interval'] == 1) && (empty($arr['searchStr']))) {
          $arr['enabled'] = 0;
      } else {
          $arr['enabled'] = 1;
      }
      return $arr; 
    }
    static function isToolbarEnabled() {
        $toolbarData = &fItems::getTagToolbarData();
        return $toolbarData['enabled'];
    }
    static function setTagToolbarDefaults($array) {
        $toolbarData = &fItems::getTagToolbarData();
        if($toolbarData['enabled']==0) {
            foreach ($array as $k=>$v) {
            	$toolbarData[$k] = $v;
            }
        }
    }
    static function getIntervalConf($par) {
        $arr = array(
        2=>(array('m'=>'1 year','f'=>'Y','p'=>'Y','db'=>'%Y')),
        3=>(array('m'=>'1 month','f'=>'Y-m','p'=>'Y m','db'=>'%Y-%m')),
        4=>(array('m'=>'1 week','f'=>'Y-W','p'=>'Y W','db'=>'%Y-%U')),
        5=>(array('m'=>'1 day','f'=>'Y-m-d','p'=>'d.m.Y','db'=>'%Y-%m-%d'))
        );
        return $arr[$par];
    }
        
    static function getTagToolbar($showHits=true,$params=array()) {
      global $user;
      $toolbarData = &fItems::getTagToolbarData();
      $tpl = new fTemplateIT("thumbup.toolbar.tpl.html");
      
      if(isset($toolbarData['search'])) {
          $tpl->touchBlock('search');
          $tpl->touchBlock('ordbydate');
      }
      if(isset($toolbarData['searchStr'])) $tpl->setVariable('SEARCHTEXT',$toolbarData['searchStr']);
      if(isset($toolbarData['usersWho'])) $tpl->setVariable('SEARCHWHO',$toolbarData['usersWho']);
      
      if($showHits==true) $tpl->touchBlock('hits');
      $orderBlocksArr = array(1=>'thumbdesc',2=>'thumbmydesc',3=>'hit',4=>'hitreg',5=>'bydate');
      $intervalBlocksArr = array(2=>'dateintyear',3=>'dateintmonth',4=>'dateintweek',5=>'dateintday');
      if($toolbarData['order']>0) $tpl->touchBlock($orderBlocksArr[$toolbarData['order']]);
      if($toolbarData['interval']>1) $tpl->touchBlock($intervalBlocksArr[$toolbarData['interval']]);
      if($toolbarData['interval'] > 1) {
          $intConfArr = fItems::getIntervalConf($toolbarData['interval']);
          if(empty($toolbarData['date'])) {
              $toolbarData['date'] = Date($intConfArr['f']);
          }
          //---prepare current to print
          global $MONTHS;
          $modify = $intConfArr['m'];
          $format = $intConfArr['p'];
          if($toolbarData['interval'] == 4) $date = str_replace('-','-W',$toolbarData['date']);
          elseif ($toolbarData['interval'] == 2) $date = $toolbarData['date'].'01-01';
          else $date = $toolbarData['date'];
          
          $dateNext = new DateTime($date);
          
          $current = $dateNext->format($format);
          if($toolbarData['interval'] == 3) {
              list($year,$month) = explode(" ",$current);
              $current = $year . ' ' . $MONTHS[$month];
          } elseif ($toolbarData['interval'] == 4) {
            $weekStart = new DateTime($date.'-1');
            $weekEnd = new DateTime($date.'-7');
            $current = $weekStart->format('d.m.Y').' - '.$weekEnd->format('d.m.Y');
          }
          $dateNext->modify("+".$modify);
          if($dateNext->format("Ymd")<=date("Ymd")) {
              $next = $dateNext->format($format);
              if($toolbarData['interval'] == 3) {
                  list($year,$month) = explode(" ",$next);
                  $next = $year . ' ' . $MONTHS[$month];
              } elseif ($toolbarData['interval'] == 4) {
                $next = '';
              }
          }
          $datePrev = new DateTime($date);
          $datePrev->modify("-".$modify);
          if($datePrev->format("Ymd")>'19800101') {
              $previous = $datePrev->format($format);
              if($toolbarData['interval'] == 3) {
                  list($year,$month) = explode(" ",$previous);
                  $previous = $year . ' ' . $MONTHS[$month];
              } elseif ($toolbarData['interval'] == 4) {
                $previous = '';
              }
          }
          if(isset($previous)) {
              $tpl->setVariable('PREVIOUSLINK',$user->getUri('tuda=prev'));
              $tpl->setVariable('PREVIOUSTEXT',PAGER_PREVIOUS . (($previous!='')?(' ' .$previous):('')));
          }
          if(isset($current)) $tpl->setVariable('CURRENTDATE',$current);
          if(isset($next)) {
              $tpl->setVariable('NEXTLINK',$user->getUri('tuda=next'));
              $tpl->setVariable('NEXTTEXT',(($next!='')?($next .' '):('')). PAGER_NEXT);
          }
      }
      if($toolbarData['enabled']==0) $tpl->touchBlock('tudis');
      $tpl->setVariable('FORMACTION',$user->getUri());

      return $tpl->get();
    }
    
    static function setTagToolbar() {
      $toolbarData = &fItems::getTagToolbarData();
      if(isset($_POST['thumbupreset'])) $toolbarData = array();
      else {
        if(isset($_POST['searchText'])) {
            //---add for fullsearch
            $toolbarData['searchStr'] = fSystem::textins($_POST['searchText'],array('plainText'=>1));
        }
        if(isset($_POST['searchUser'])) {
            $toolbarData['usersWho'] = fSystem::textins($_POST['searchUser'],array('plainText'=>1));
        }
        if(isset($_POST['tuorder'])) $toolbarData['order'] = (int) $_POST['tuorder'];
        if(isset($toolbarData['interval'])) $oldInterval = $toolbarData['interval']; else $oldInterval = -1;
        if(isset($_POST['tuint'])) $toolbarData['interval'] = (int) $_POST['tuint'];
        //---create next,prev links, show current date
        if(isset($toolbarData['interval'])) {
          if($toolbarData['interval']>1 && (empty($toolbarData['date']) || $oldInterval!=$toolbarData['interval'])) {
              //---create default - current date
              $intConfArr = fItems::getIntervalConf($toolbarData['interval']);
              if(!empty($toolbarData['date'])) {
                if($oldInterval==4) $date = str_replace('-','-W',$toolbarData['date']);
                else $date = $toolbarData['date'];
              } else $date='';
              $date = new DateTime($date);
              $toolbarData['date'] = $date->format($intConfArr['f']);
          
          } elseif ($toolbarData['interval']<2) unset($toolbarData['date']);
        }
        if(isset($_GET['tuda']) && $toolbarData['interval']>1) {
            $intConfArr = fItems::getIntervalConf($toolbarData['interval']);
            if($_GET['tuda']=='next') $modifyCourse = '+';
            if($_GET['tuda']=='prev') $modifyCourse = '-';
            if(isset($modifyCourse)) {
                if($toolbarData['interval']==4) $dateStr = str_replace('-','-W',$toolbarData['date']);
                elseif($toolbarData['interval']==2) $dateStr = $toolbarData['date'].'01-01';
                else $dateStr = $toolbarData['date'];
                $date = new DateTime($dateStr);
                $date->modify($modifyCourse.$intConfArr['m']);
                $toolbarData['date'] = $date->format($intConfArr['f']);
            }
        }
      }
    }
    static function setQueryTool(&$fQuery) {
        global $user;
        $thumbupData = &fItems::getTagToolbarData();
        if($thumbupData['enabled']==1) {
            
            if(isset($thumbupData['searchStr'])) {
                if(!empty($thumbupData['searchStr'])) {
                    $fQuery->addFulltextSearch('i.text,i.enclosure,i.addon',$thumbupData['searchStr']);
                }
            }
            if(isset($thumbupData['usersWho'])) {
                if(!empty($thumbupData['usersWho'])) {
                    $usersNameArr = explode(',',$thumbupData['usersWho']);
                    foreach ($usersNameArr as $userName) {
                    	if($userId = $user->getUserIdByName($userName)) {
                    	    $validatedUserId[] = $userId;
                    	}
                    	else fError::addError(MESSAGE_USERNAME_NOTEXISTS.': '.$userName);
                    }
                    if(!empty($validatedUserId)) {
                        if(count($validatedUserId)>1) {
                            $fQuery->addWhere('userId in ('.implode(',',$validatedUserId).')');
                        } else $fQuery->addWhere('userId = '.$validatedUserId[0]);
                    }
                }
            }
            if($thumbupData['order']==5) $fQuery->setOrder('i.dateCreated desc');
            elseif ($thumbupData['order'] > 2) {
                if($thumbupData['interval']>1) {
                    $fQuery->setOrder('ihistory.valueSum desc');
                    $fQuery->replaceSelect('i.hit','ihistory.valueSum as hitsum');
                    $fQuery->addWhere('ihistory.historyType = '.$thumbupData['order']);
                } else $fQuery->setOrder('i.hit desc');
            } elseif ($thumbupData['order'] > 0) {
                $fQuery->setOrder('thumbs desc');
                if($thumbupData['interval']>1) {
                    $fQuery->replaceSelect('i.tag_weight','ihistory.valueSum as thumbs');
                    $fQuery->addWhere('ihistory.historyType = 1');
                } else $fQuery->replaceSelect('i.tag_weight','i.tag_weight as thumbs');
                if(isset($thumbupData['filter']))
                if($thumbupData['filter'] == 2) {
                    $fQuery->addWhere('it.userId="'.$user->gid.'"');
                    $fQuery->addJoin('join sys_pages_items_tag as it on it.itemId=i.itemId');
                } 
            }
            //-----------------------------
            //---by date
            if(!empty($thumbupData['date'])) {
              $intConfArr = fItems::getIntervalConf($thumbupData['interval']);
              $dateformat = $intConfArr['db'];
              $date = $thumbupData['date'];
              
              if($thumbupData['interval']==4) {
                list($year,$week) = explode("-",$date);
                $week--;
              }

              if($thumbupData['order']==0) {
                if($thumbupData['interval']==4) $date = sprintf("%04d-%02d",$year,$week);
                $fQuery->addWhere("date_format( i.dateCreated, '".$dateformat."' ) = '".$date."'");
              } else {
                if($thumbupData['interval']==4) $date = sprintf("%04d-W%02d",$year,$week);
                $fQuery->addWhere("ihistory.dateInt = '".$date."'");
              }
            }
            if($thumbupData['order']>0 && $thumbupData['interval']>1)
              $fQuery->addJoin('join sys_pages_items_history as ihistory on ihistory.itemId=i.itemId');
        }
    }
    function getItem($itemId) {
        $this->setWhere("i.itemId='".$itemId."'");
        $arr = $this->getContent();
        if(!empty($arr)) return $arr[0];
    }
    function saveItem($arrData) {
      $sItem = new fSqlSaveTool('sys_pages_items','itemId');  
      return $sItem->Save($arrData,array('dateCreated'));
    }
    function deleteItem($itemId) {
        $this->db->query("delete from sys_pages_items where itemId='".$itemId."'");
        $this->db->query("delete from sys_users_pocket where itemId='".$itemId."'");
        $this->db->query("delete from sys_pages_items_readed_reactions where itemId='".$itemId."'");
        $this->db->query("delete from sys_pages_items_hit where itemId='".$itemId."'");
        $this->db->query("delete from sys_pages_items_tag where itemId='".$itemId."'");
    }
    static function getItemUserId($itemId) {
        global $db;
        return $db->getOne("select userId from sys_pages_items where itemId='".$itemId."'");
    }
}