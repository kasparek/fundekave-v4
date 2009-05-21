<?php
class fGalery {
	//---from config file
	var $_widthThumb = 0;
	var $_heightThumb = 0;
	var $_widthDetail = 0;
	var $_quality = 0;
	var $_sheetX = 0;
	var $_sheetY = 0;
	var $_thumbTemplate = 'galery.thumb.tpl.html';
	var $_detailTemplate = 'galery.detail.tpl.html';
	var $_bckColorHex = 0;
	var $_perpage = 0;
	//---defaults - reseted with defs array
	var $_root = ROOT; //FIXME: deprecated - everything is relative
	var $_rootImg = WEB_REL_GALERY;
	var $_cacheDir = WEB_REL_CACHE_GALERY;
	var $_cacheDirSystemResolution = WEB_REL_CACHE_GALERY_SYSTEM;
	var $_imageFile = '';
	var $_destFile = '';
	var $_widthMax = 0;
	var $_heightMax = 0;
	var $_side = '';
	var $_onSheet = false;
	var $_showPoints=true;
	var $_showGalerylink=false;
	var $_showComment=true;
	var $_showTooltip=true;
	var $_fotoId = 0;
	var $_galeryId = 0;
	var $_popUp = false;
	var $_thumbnailstyle = 0;
	var $_thumbInSysRes = false;
	//---have to be set with initialization
	var $arrData;
	//--- one foto
	var $_fId = 0;
	var $_fGaleryId = 0;
	var $_fDir = '';
	var $_fName = '';
	var $_fDetail = '';
	var $_fDate = '';
	var $_fSize = 0; //filsize in B
	var $_fGaleryName = '';
	var $_fComment = '';
	var $_fHits = '';
	var $_fThumbDir = '';
	var $_fWidth = '';
	var $_fHeight = '';
	//--- galery
	var $gCountFoto = 0;
	var $gCountFotoNew = 0;
	//---one galery
	var $gId = 0;
	var $gCategory = 0;
	var $gDir = '';
	var $gName = '';
	var $gText = '';
	var $gDate = '';
	var $gAuthorUsrId = 0;
	var $gAuthorUsrname = '';
	var $gPublic = 0;
	var $gPerpage = 8;
	var $gOrderItems = 0;
	var $gPageParams = '';
	
	function __construct($params=array()) {
    global $conf;
    if($conf) {
      $this->set($conf['galery']);
    }
		$this->set($params);
	}
	function set($par,$val='') {
		if(!empty($par)){
			if(is_array($par)) {
				foreach ($par as $k=>$v) {
					$k='_'.$k;
					if(isset($this->$k)) $this->$k = $v;
				}	
			} else {
				$par='_'.$par;
				if(isset($this->$par)) $this->$par = $val;
			}
		}
	}
	function get($par) {
		$par='_'.$par;
		if(isset($this->$par)) return $this->$par;
	}
	function getGaleryData($id) {
		global $db,$user;
		if(!empty($id) || $this->gId!=$id) {
		    $this->gId = $id;
		    
		    if($id==$user->currentPageId) {
		        $this->gCategory = $user->currentPage['categoryId'];
                $this->gDir = $user->currentPage['galeryDir'];
                $this->gName = $user->currentPage['name'];
                $this->gText = $user->currentPage['description'];
                $this->gDate = $user->currentPage['dateContent'];
                $this->gAuthorUsrId = $user->currentPage['userIdOwner'];
                $this->gAuthorUsrname = $user->currentPage['authorContent'];
                $this->gPublic = $user->currentPage['public'];
                $this->gPageParams = $user->currentPage['pageParams'];
		    } else {
		        $fPage = new fPages('galery',$user->userVO->userId);
		        $fPage->primaryCol = 'p.pageId';
		        $fPage->setSelect('p.categoryId,p.galeryDir,p.name,
		        p.description,p.dateContent,p.userIdOwner,
		        p.public,p.authorContent,p.pageParams');
		        $arr = $fPage->get($id);
		        $this->gCategory = $arr[0];
				$this->gDir = $arr[1];
				$this->gName = $arr[2];
				$this->gText = $arr[3];
				$this->gDate = $arr[4];
				$this->gAuthorUsrId = $arr[5];
				$this->gAuthorUsrname = $arr[6];
				$this->gPublic = $arr[7];
				$this->gPageParams = $arr[8];
		    }
	 		
			$this->parseXML($this->gPageParams);
			
		}
	}
	function parseXML($xmlString,$pageId='') {
	  $parse = true;
	  
	  if($pageId!='') if($pageId == $this->gId) $parse = false;
	  if($this->_widthMax==0) $parse = true;
	  
	  if($parse==true) {
        if($pageId!='') $this->gId = $pageId;
	    
        $xml = new SimpleXMLElement($xmlString);
        $this->gPerpage = (String) $xml->enhancedsettings[0]->perpage;
        if(empty($this->gPerpage)) $this->gPerpage = $this->_perpage;
              
        $this->gOrderItems = 0;
        if($xml->enhancedsettings[0]->orderitems) $this->gOrderItems = (String) $xml->enhancedsettings[0]->orderitems;
        $this->_widthMax = ($xml->enhancedsettings[0]->widthpx < 10)?($this->_widthThumb):((String) $xml->enhancedsettings[0]->widthpx);
        $this->_heightMax = ($xml->enhancedsettings[0]->heightpx < 10)?($this->_heightThumb):((String) $xml->enhancedsettings[0]->heightpx);
        $this->_thumbnailstyle = (String) $xml->enhancedsettings[0]->thumbnailstyle;
	  }
	  
	}
	
	function prepare($arr) {
	   global $user;
	   if(isset($arr['pageParams'])) {
	     $this->parseXML($arr['pageParams'],$arr['pageId']);
	   }
          $this->_fGaleryId = $arr['pageId'];
          $this->_fDir = $arr['galeryDir'];
          $this->_fGaleryName = $arr['pageName'];
          $this->_fId = $arr['itemId'];
          $this->_fDetail = $arr['enclosure'];
          
          $this->_fWidth = $arr['width'] = $this->_widthMax;
          $this->_fHeight = $arr['height'] = $this->_heightMax;
          //---check thumbnail
          if($this->_thumbInSysRes == true) {//---system resolution thumbnail
            $thumbPathArr = $this->getThumbPath($this->_cacheDirSystemResolution);
    		    if(!fGalery::isThumb($thumbPathArr['thumb'])) $this->createThumb($thumbPathArr); 
  	        $arr['thumbUrl'] = $thumbPathArr['url'];
          } else {
  				  if(!empty($arr['addon'])) {
  					    $arr['thumbUrl'] = $this->_rootImg . $arr['galeryDir'].'/'.$arr['addon'];
  					} else {
  					    $thumbPathArr = $this->getThumbPath();
  					    if(!fGalery::isThumb($thumbPathArr['thumb'])) {
  					      $this->createThumb($thumbPathArr); 
  					    }
  					    $arr['thumbUrl'] = $thumbPathArr['url'];
  					}
					}
				  $arr['detailUrl'] = $this->_rootImg . $this->_fDir . '/' . $arr['enclosure'];
          
				  if(file_exists($arr['detailUrl'])) {
            list($width,$height) = getimagesize($arr['detailUrl']);
				    $arr['detailWidth'] = $width;
  				  $arr['detailHeight'] = $height;
  				  $arr['detailUrlToGalery'] = '?k='.$arr['pageId'].'&amp;i='.$arr['itemId'];
  				  $arr['detailUrlToPopup'] = '/pic.php?u='.$user->userVO->userId.'&amp;i='.$this->_fId.'&amp;width='.($width+60).'&amp;height='.($height+60);
				  } else {
            fError::addError('File not exists: '.$arr['detailUrl']);
          }
				  return $arr;
  }
	function getFoto($id='',$allGalery=false,$orderBy='') {
		global $db;
		if(!empty($id)) {
			if($allGalery) $this->_galeryId = $id;
			else $this->_fotoId = $id;
		}
		$doLoad = true;
		if($allGalery==false && !empty($this->arrData)) 
            foreach($this->arrData as $item) 
                if($item['itemId']==$id) {
                    $doLoad = false;
                    break;
                }
        
		if($doLoad) {
            $fItems = new fItems();
            $fItems->initData('galery'); 
            
            if($allGalery) $fItems->setWhere("p.pageId='".$this->_galeryId."'");
            else $fItems->setWhere("i.itemId='".$this->_fotoId."'");
            if($orderBy!='') $fItems->setOrder($orderBy);
            $fItems->getData();
            
            $this->arrData = &$fItems->arrData;
            
            if(count($this->arrData)>0) {
                $this->getGaleryData($this->arrData[0]['pageId']);
            	foreach ($this->arrData as $k=>$arr) {
            	  $this->arrData[$k] = $item = $this->prepare($arr);
            	}
            }
        }
        if($allGalery==false) {
    		$this->_fGaleryId = $item['pageId'];
            $this->_fDir = $item['galeryDir'];
            $this->_fGaleryName = $item['pageName'];
            $this->_fId = $item['itemId'];
            $this->_fDetail = $item['enclosure'];
            $this->_fWidth = $item['width'];
            $this->_fHeight = $item['height'];
            $this->_fHits = $item['hit'];
            $this->_fComment = $item['text'];
            $this->_fDate = $item['dateLocal'];
            $this->_fSize = $item['filesize'];
    		$this->_fThumbDir = $item['thumbUrl'];
	   }
	}
	
	function getThumbCachePath($cacheDir='') {
	    return (($cacheDir!='')?($cacheDir):($this->_cacheDir)) . (($this->_fGaleryId)?($this->_fGaleryId):($this->gId)) . '-' . fSystem::safeText((($this->_fGaleryName)?($this->_fGaleryName):($this->gName)));
	}
	function  getDetailUrl() {
    return $this->_rootImg . $this->_fDir . '/' . $this->_fDetail;
  }
	function getThumbPath($cacheDir = '') {
	    $pathUrl = $this->getThumbCachePath($cacheDir);
	    $pathDir = $pathUrl;
	    
	    $arrFilename = explode('.',$this->_fDetail);
	    $filenameExtStriped = implode('.',array_slice($arrFilename,0,count($arrFilename)-1));
	    $filename = fSystem::safeText($filenameExtStriped) . '.jpg';
		
	    return array(
    		'path' => $pathDir,
    		'filename' => $filename,
    		'thumb' => $pathDir . '/' . $filename,
    		'url' => $pathUrl . '/' . $filename
    	);
	}
	static function isThumb($path) {
	    return file_exists($path);
	}
	function createThumb($thumbPathArr,$params=array()) {
		//check
		if(!$this->isThumb($thumbPathArr['thumb'])) {
		 	if(!empty($thumbPathArr['path'])) {
		 	    if(!is_dir($thumbPathArr['path'])) {
		 	        mkdir($thumbPathArr['path'],0777);
		 	    }
		 	}
			//Create file
			if(isset($params['source'])) $sourceImgUrl = $params['source'];	else $sourceImgUrl = $this->getDetailUrl();
			if(isset($params['quality'])) $quality = $params['quality']; else $quality = $this->_quality;
			if(isset($params['thumbnailstyle'])) $thumbnailstyle = (int) $params['thumbnailstyle']; else $thumbnailstyle = (int) $this->_thumbnailstyle;
			if(isset($params['width'])) $width = (int) $params['width']; else $width = (int) $this->_fWidth;
			if(isset($params['height'])) $height = (int) $params['height']; else $height = (int) $this->_fHeight;
			
			$processParams = array(
			'quality'=>$quality,'width'=>$width,'height'=>$height
      //,'reflection'=>1
      //,'unsharpMask'=>1
			);
			if($thumbnailstyle==2) $processParams['crop'] = 1; else $processParams['proportional'] = 1;
			$fProcess = new fImgProcess($sourceImgUrl,$thumbPathArr['thumb'],$processParams);
		}
	}
    function deleteThumb($path) {
        if(isThumb($thumbPathArr['thumb'])) unlink($thumbPathArr['thumb']);
    }
	
	function fotoHit() {
	 global $user,$db;
    if(!empty($this->_fId)){
			$db->query("update sys_pages_items set hit=hit+1 where itemId=".$this->_fId);
			$db->query("insert into sys_pages_items_hit (itemId,userId,dateCreated) values (".$this->_fId.",".$user->userVO->userId.",now())");
			$this->_fHits++;
		}
  }
    static function callbackForumProcess() {
        //---clear cache
        global $user;
        $user->cacheRemove('fotodetail');
        $user->cacheRemove('lastForumPost');
    }
	function printDetail($itemId) {
		global $db,$conf,$user;
		
		fForum::process($itemId,"fGalery::callbackForumProcess");
		$fItems = new fItems();
		$itemId = $fItems->initDetail($itemId);
		
		if(!empty($itemId)) $this->getFoto($itemId);
		if(!empty($this->_fId)) {
			//---get pid for linkback
			$this->fotoHit();
			if(!$ret = $user->cacheGet('fotodetail',$itemId)) {
			  $orderBy = $user->pageVO->getPageParam('enhancedsettings/orderitems');
			  $arrItemId = $db->getCol("select itemId from sys_pages_items where pageId='".$this->_fGaleryId."' order by ".((($orderBy==0)?('enclosure'):('dateCreated'))));
			  
  			$arr = array_chunk($arrItemId,$this->gPerpage);
  			foreach ($arr as $k=>$arrpage) {
  				if(in_array($this->_fId,$arrpage)) {
  					$pid = $k + 1;
  					break;
  				}
  			}
  			$this->_widthMax = $this->_widthDetail;
  			$this->_heightMax = $this->_widthDetail;
  			$tpl = new fTemplateIT($this->_detailTemplate);
  			$backLink = '?k='.$user->currentPageId.'&amp;'.$conf['pager']['urlVar'].'='.$pid;
  			$tpl->setVariable("LINKBACKTOP",$backLink);
  			  			
  			$tpl->setVariable("IMGALT", $this->_fGaleryName.' '.$this->_fDetail);
  			$tpl->setVariable("IMGDIR", $this->getDetailUrl());
  			if($this->_showComment && !empty($this->_fComment)) $tpl->setVariable("INFO",$this->_fComment);
  			
        if(!empty($this->_fName)) {
          $user->currentPage["name"] = $this->_fName . ' - ' . $user->currentPage["name"];
        } else {
          $user->currentPage["name"] = $this->_fDetail . ' - ' . $user->currentPage["name"];
        }
  			
  			$tpl->setVariable("HITS",$this->_fHits);
  			if($user->idkontrol) {
  			    $tpl->setVariable('TAG',fItems::getTag($itemId,$user->userVO->userId,'galery'));
  			    $tpl->setVariable('POCKET',fPocket::getLink($itemId));
  			}
  			
  		  $arrImgId = fSystem::array_neighbor($this->_fId,$arrItemId);
  			
  		  $fItems->initData('galery');
  		  $fItems->setWhere('i.itemId in ('.$arrImgId['prev'].','.$arrImgId['next'].')');
  		  
  			$fItems->showRating = false;
  			$fItems->showTooltip = false;
  			$fItems->openPopup = false;
  			$fItems->showText = false;
  			$fItems->showTag = false;
  			$fItems->showPocketAdd = false;
  			$fItems->xajaxSwitch = true;
        
  			$fItems->getData();
  			
  			if(!empty($arrImgId['prev'])) {
  			  $fItems->parse($arrImgId['prev']);
  			  $tpl->setVariable("THUMBPREVIOUS",$fItems->show());
  			}
  			if(!empty($arrImgId['next'])) {
  			  $fItems->parse($arrImgId['next']);
  			  $tpl->setVariable("THUMBNEXT",$fItems->show());
  			  $tpl->touchBlock('nextlinkclose');
  			  if($user->idkontrol) $tpl->touchBlock('xajaxSwitch');
  			  $tpl->setVariable('NEXTLINK',$user->getUri('i='.$arrImgId['next']));
  			}
  			
  			//TODO: comments in galery are switched offf in this release
  			//$tpl->setVariable('COMMENTS',fForum::show($itemId,$user->idkontrol,$fItems->itemIdInside,array('formAtEnd'=>$true,'showHead'=>false)));
        
  			$ret = $tpl->get();
        $user->cacheSave($ret);
      }
			return $ret;
		}
	}
	function getPopup($fotoId) {
        $this->getFoto($fotoId);
        $this->fotoHit();
        $tpl = new fTemplateIT('galery.popup.tpl.html');
        $tpl->setVariable('FOTOURL',$this->getDetailUrl());
        return $tpl->get();
    }
    
    function getRaw($fotoId) {
        $this->getFoto($fotoId);
        $this->fotoHit();
        return file_get_contents( $this->getDetailUrl() );
    }
			
	function refreshImgToDb($galeryId){
		global $db;
		$this->getGaleryData($galeryId);
		$this->gCountFoto = 0;
		$this->gCountFotoNew = 0;
		if(empty($galeryId)) $galeryId = $this->_fGaleryId;
		$this->getFoto($galeryId,true);

		$arrFotoDetail = array();
		$arrNames = array();
		if(!empty($this->arrData)) {
    		foreach ($this->arrData as $arr) {
    			$arrFotoDetail[$arr['itemId']] = $arr['enclosure'];
    			$arrFotoSize[$arr['enclosure']] = $arr['filesize'];
    			$arrNames[$arr['enclosure']] = $arr['itemId'];
    		}
		}
		$this->gCountFoto = count($arrFotoDetail);
		$arrFiles = array();
		$galdir = $this->_rootImg . $this->gDir.'/';
		$handle=opendir($galdir.'/');
        while (false!==($file = readdir($handle))){
	    	if (preg_match("/((.jpeg)|(.jpg)|(.gif)|(.JPEG)|(.JPG)|(.GIF)$)/",$file)) {
	           $arrFiles[] = $file;
	    	}
        }
        
        $arrNotInDB = array_diff($arrFiles,$arrFotoDetail);
        $arrItemIdsNotOnFtp = array_keys(array_diff($arrFotoDetail,$arrFiles));
        if(!empty($arrItemIdsNotOnFtp)) foreach ($arrItemIdsNotOnFtp as $itemId) {
            $this->removeFoto($itemId);
        }

        if(!empty($arrNotInDB)) {
            foreach ($arrNotInDB as $file) {

                $this->_fId = 0;
                $this->_fGaleryId = $galeryId;
                $this->_fDetail = $file;
                $this->_fDate = 'now()';
                $this->_fSize = filesize($galdir.$file);
                $this->_fDir = $this->gDir;
                $this->_fGaleryName = $this->gName;
                $this->_fWidth = (empty($this->_widthMax))?($this->_widthThumb):($this->_widthMax);
                $this->_fHeight = (empty($this->_heightMax))?($this->_heightThumb):($this->_heightMax);
                $this->_fComment = '';
                $this->_fHits = 0;

                if(file_exists($galdir.'/nahled/'.$file)) {
                    $this->_fThumbDir = 'nahled/'.$file;
                } else $this->_fThumbDir = '';
                $update = true;
                $this->gCountFotoNew++;

                $thumbPathArr = $this->getThumbPath();
                if(!fGalery::isThumb($thumbPathArr['thumb'])) $this->createThumb($thumbPathArr);
                $this->_fId = $this->updateFoto();

            }
        }
		
		
		foreach ($arrFotoDetail as $k=>$v) {
			if(file_exists($galdir.$v)) {
			    $newFilesize = filesize($galdir.$v);
			    $oldFilesize = $arrFotoSize[$v];
			    if($newFilesize != $oldFilesize) {
	                //---delete thumb, update filesize
	                $fotoId = $arrNames[$v];
	                $this->getfoto($fotoId);
	                $this->_fSize = $newFilesize;
	                $this->removeThumb();
	                $this->updateFoto();
	            }
			}
		}
		
		closedir($handle);
		$db->query("update sys_pages set cnt='".($this->gCountFotoNew + $this->gCountFoto)."',date_updated = now() where pageId='".$galeryId."'");
	}
	function updateFoto() {
	 global $user;
	    $notQuoted = array();
	    $fSave = new fSqlSaveTool('sys_pages_items','itemId');
	    $arr = array('dateCreated'=>$this->_fDate,'text'=>$this->_fComment,'hit'=>$this->_fHits,'filesize'=>$this->_fSize);
	    if(!empty($this->_fId)) $arr['itemId'] = $this->_fId;
	    else {
	        $notQuoted = array('dateCreated');
	        $arr['addon'] = $this->_fThumbDir;
	        $arr['enclosure'] = $this->_fDetail;
	        $arr['pageId'] = $this->_fGaleryId;
	        $arr['typeId'] = 'galery';
	        $arr['userId'] = $user->userVO->userId;
	        $arr['name'] = $user->userVO->name;
	    }
	    if($newfid = $fSave->save($arr,$notQuoted)) {
	        $this->_fId = $newfid;
	        $user->cacheRemove('calendarlefthand');
	        return $newfid;
	    }
	}
	function removeFoto($id) {
		global $db,$user;
		if(!empty($id)){
		    $this->getFoto($id);
			if(!empty($this->_fThumbDir)) if(is_file($this->_fThumbDir)) unlink($this->_fThumbDir);
			if(is_file($this->_rootImg . $this->_fDir . '/' . $this->_fDetail)) unlink($this->_rootImg . $this->_fDir . '/' . $this->_fDetail);
			$this->removeThumb();
			
			$db->query("delete from sys_pages_items_tag where itemId = '".$id."'");
			$db->query("delete from sys_pages_items_hit where itemId='".$id."'");
			$db->query("delete from sys_pages_items where itemId='".$id."'");
			$db->query("update sys_pages set date_updated = now(),cnt=cnt-1 where pageId='".$this->_fGaleryId."'");
      $user->cacheRemove('calendarlefthand');
		}
	}
	function removeThumb() {
	    $thumbPathArr = $this->getThumbPath();
	    if(fGalery::isThumb(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) if(!unlink(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) fError::addError('Cannot delete thumb: '.ROOT.ROOT_WEB.$thumbPathArr['thumb']);
	    //---delete system thumb
		$thumbPathArr = $this->getThumbPath($this->_cacheDirSystemResolution);
	    if(fGalery::isThumb(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) { 
        if(@unlink(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) {
          //fError::addError('Cannot delete system thumb: '.ROOT.ROOT_WEB.$thumbPathArr['thumb']);
        }
      }
	    if(!fError::isError()) return true;
	}
}