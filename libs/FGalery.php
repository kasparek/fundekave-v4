<?php
class FGalery {
	//---active galery page
	var $pageVO;
	//---active item
	var $itemVO;
	//---from config file - gallery part
	var $conf;
	
	function __construct($params=array()) {
		$conf = FConf::getInstance();
		$this->conf = $conf->a['galery'];
	}
		
	/**
	 * ordering in galery
	 * @return number
	 */
	function orderBy() {
		$orderBy = 0;
		$orderByXML = (String) $this->pageVO->getPageParam('enhancedsettings/orderitems');
		if( $orderByXML ) $orderBy = $orderByXML;
		return $orderBy;
	}
	
	/**
	 * for FItems
	 * prepare function - creating extra options for items type galery
	 * @param $itemVO
	 * @return itemVO
	 */
	static function prepare($itemVO = null) {
		$fGalery = new FGalery(); 
		$fGalery->itemVO = $itemVO;
		$fGalery->pageVO = new PageVO($itemVO->pageId,true);
		//---check thumbnail
		if($fGalery->itemVO->thumbInSysRes == true) {
			//---system resolution thumbnail
			$thumbPathArr = $fGalery->getThumbPath(WEB_REL_CACHE_GALERY_SYSTEM);
			if(!FGalery::isThumb($thumbPathArr['thumb'])) {
				$fGalery->createThumb($thumbPathArr
					,array('width'=>$fGalery->conf['widthThumb']
						,'height'=>$fGalery->conf['heightThumb'])
					);	
			}
			$fGalery->itemVO->thumbUrl = $thumbPathArr['url'];
			$fGalery->itemVO->thumbWidth = $fGalery->conf['widthThumb'];
			$fGalery->itemVO->heightWidth = $fGalery->conf['heightThumb'];
		} else {
			$thumbPathArr = $fGalery->getThumbPath();
			if(!FGalery::isThumb($thumbPathArr['thumb'])) {
				$fGalery->createThumb($thumbPathArr);
			}
			$fGalery->itemVO->thumbUrl = $thumbPathArr['url'];
			$fGalery->itemVO->thumbWidth = (String) $fGalery->pageVO->getPageParam('enhancedsettings/widthpx');
			$fGalery->itemVO->thumbHeight = (String) $fGalery->pageVO->getPageParam('enhancedsettings/heightpx');
			if(empty($fGalery->itemVO->thumbWidth)) $fGalery->itemVO->thumbWidth = $fGalery->conf['widthThumb'];
			if(empty($fGalery->itemVO->thumbHeight)) $fGalery->itemVO->thumbHeight = $fGalery->conf['heightThumb'];
		}
		$fGalery->itemVO->detailUrl = WEB_REL_GALERY . $fGalery->pageVO->galeryDir . '/' . $fGalery->itemVO->enclosure;

		if(file_exists( $fGalery->itemVO->detailUrl )) {
			list($width,$height) = getimagesize( $fGalery->itemVO->detailUrl );
			$fGalery->itemVO->detailWidth = $width;
			$fGalery->itemVO->detailHeight = $height;
			$fGalery->itemVO->detailUrlToGalery = FUser::getUri('i='.$fGalery->itemVO->itemId,$fGalery->itemVO->pageId);
			$fGalery->itemVO->detailUrlToPopup = FUser::getUri('i='.$fGalery->itemVO->itemId.'&width='.($width+60).'&height='.($height+60).'&u='.FUser::logon(),'','','pic.php');
		} else {
			FError::addError('File not exists: '.$fGalery->itemVO->detailUrl);
		}
		return $fGalery->itemVO;
	}
		
	/**
	 * return cache url
	 *
	 * @param string $cacheDir - alternative cache root
	 * @return string url
	 */
	function getThumbCachePath($cacheDir='') {
		return (($cacheDir!='') ? ( $cacheDir ):( WEB_REL_CACHE_GALERY )) . $this->pageVO->pageId . '-' . FSystem::safeText($this->pageVO->name);
	}
	
	/**
	 * get url of detail
	 *
	 * @return strinf url
	 */
	function  getDetailUrl() {
		return WEB_REL_GALERY . $this->pageVO->galeryDir . '/' . $this->itemVO->enclosure;
	}
	
	/**
	 * return array with directions to thumb
	 *
	 * @param string $cacheDir - alternative
	 * @return array [path,filename,thumb,url]
	 */
	function getThumbPath($cacheDir = '') {
		$pathUrl = $this->getThumbCachePath($cacheDir);
		$arrFilename = explode('.',$this->itemVO->enclosure);
		$filenameExtStriped = implode('.',array_slice($arrFilename,0,count($arrFilename)-1));
		$filename = FSystem::safeText($filenameExtStriped) . '.jpg';
		return array(
    		'path' => $pathUrl,
    		'filename' => $filename,
    		'thumb' => $pathUrl . '/' . $filename,
    		'url' => $pathUrl . '/' . $filename
		);
	}
	
	/**
	 * check if thumbnail exist
	 *
	 * @param string $path - url
	 * @return boolean
	 */
	static function isThumb($path) {
		return file_exists($path);
	}
	
	/**
	 * generate thumb
	 * @param $thumbPathArr
	 * @param $params
	 * @return void
	 */
	function createThumb($thumbPathArr,$params=array()) {
		//check
		if(!$this->isThumb($thumbPathArr['thumb'])) {
			if(!empty($thumbPathArr['path'])) {
				if(!is_dir($thumbPathArr['path'])) {
					FFile::makeDir($thumbPathArr['path']);
				}
			}
			//Create file
			if(isset($params['source'])) $sourceImgUrl = $params['source'];	else $sourceImgUrl = $this->getDetailUrl();
			if(isset($params['quality'])) $quality = $params['quality']; else $quality = $this->conf['quality'];
			if(isset($params['thumbnailstyle'])) $thumbnailstyle = (int) $params['thumbnailstyle']; else $thumbnailstyle = (int) $this->pageVO->getPageParam('enhancedsettings/thumbnailstyle');
			if(isset($params['width'])) $width = (int) $params['width']; else $width = (int) ($this->pageVO->getPageParam('enhancedsettings/widthpx') < 10)?($this->conf['widthThumb']):((String) $this->pageVO->getPageParam('enhancedsettings/widthpx'));
			if(isset($params['height'])) $height = (int) $params['height']; else $height = (int) ($this->pageVO->getPageParam('enhancedsettings/heightpx') < 10)?($this->conf['heightThumb']):((String) $this->pageVO->getPageParam('enhancedsettings/heightpx'));

			$processParams = array(
			'quality'=>$quality,'width'=>$width,'height'=>$height
			//,'reflection'=>1
			,'unsharpMask'=>1
			);
			if($thumbnailstyle==2) $processParams['crop'] = 1; else $processParams['proportional'] = 1;
			$fProcess = new FImgProcess($sourceImgUrl,$thumbPathArr['thumb'],$processParams);
		}
	}
		
	/**
	 * callback function when processing forum attached to gallery
	 * @return void
	 */
	static function callbackForumProcess() {
		//---clear cache
		$cache = FCache::getInstance('f');
		$cache->invalidateGroup('lastForumPost');
	}

	/**
	 * get RAW detail img data
	 * 
	 * @param $fotoId - item
	 * @return String - BINARY
	 */
	static function getRaw($itemId) {
		$galery = new FGalery();
		$galery->itemVO = new ItemVO($itemId, true, array('type'=>'galery'));
		$galery->pageVO = new PageVO($galery->itemVO->pageId,true);
		$galery->itemVO->hit();
		return file_get_contents( $galery->getDetailUrl() );
	}

	/**
	 * refresh data for galery in db by files in folder
	 * @param $pageId
	 * @return void
	 */
	function refreshImgToDb($pageId){
		if(!empty($pageId)) {
			$this->pageVO = new PageVO($pageId,true);
		} else {
			$pageId = $this->pageVO->pageId;
		}
		
		$gCountFoto = 0;
		$gCountFotoNew = 0;
		
		$fItems = new FItems('galery',false);
		$fItems->setWhere('pageId="'.$pageId.'"');
		$fItems->addWhere('itemIdTop is null');
		$totalItems = $fItems->getCount();
		$itemList = $fItems->getList();
		
		$arrFotoDetail = array();
		$arrFotoSize = array();
		$arrNames = array();
		if(!empty($itemList)) {
			foreach ($itemList as $itemVO) {
				$arrFotoDetail[$itemVO->itemId] = $itemVO->enclosure;
				$arrFotoSize[$itemVO->enclosure] = $itemVO->filesize;
				$arrNames[$itemVO->enclosure] = $itemVO->itemId;
			}
		}
		
		//---search folder
		$gCountFoto = count($arrFotoDetail);
		$arrFiles = array();
		$galdir = WEB_REL_GALERY . $this->pageVO->galeryDir.'/';
		$handle=opendir( $galdir . '/' );
		while ( false !== ($file = readdir( $handle )) ){
			if (preg_match("/((.jpeg)|(.jpg)|(.gif)|(.JPEG)|(.JPG)|(.GIF)$)/",$file)) {
				$arrFiles[] = $file;
			}
		}
		closedir($handle);

		$change = false;
		
		$arrNotInDB = array_diff($arrFiles,$arrFotoDetail);
		$arrItemIdsNotOnFtp = array_keys(array_diff($arrFotoDetail,$arrFiles));

		//---remove foto no longer in folder
		if(!empty($arrItemIdsNotOnFtp)) foreach ($arrItemIdsNotOnFtp as $itemId) {
			FGalery::removeFoto($itemId);
			$change = true;
		}
		
		//---insert new foto to db
		if(!empty($arrNotInDB)) {
			foreach ($arrNotInDB as $file) {
				$itemVO = new ItemVO();
				$itemVO->pageId = $pageId;
				$itemVO->typeId = $this->pageVO->typeId;
				$itemVO->enclosure = $file;
				/*
				$itemVO->dateCreated = 'now()';
				//---try exif
				
				$exif = @exif_read_data( $galdir.$file );
				if(!empty($exif)) {
					$itemVO->dateCreated = date("Y-m-d",$exif['FileDateTime']);
					if(!empty($exif['DateTimeOriginal'])) {
						//TODO: find a way to fix all exif formats
						//$itemVO->dateCreated = date("Y-m-d",$exif['DateTimeOriginal']);
					}
				}
				*/
				$itemVO->filesize = filesize($galdir.$file);
				$itemVO->text = '';
				$itemVO->hit = 0;
				$itemVO->save();
				$gCountFotoNew++;
				$this->itemVO = $itemVO;
				$thumbPathArr = $this->getThumbPath();
				if(!FGalery::isThumb($thumbPathArr['thumb'])) $this->createThumb($thumbPathArr);
				$change = true;
			}
		}

		//--- check if filesize changed so update thumb
		foreach ($arrFotoDetail as $k=>$v) {
			if(file_exists($galdir.$v)) {
				$newFilesize = filesize($galdir.$v);
				$oldFilesize = $arrFotoSize[$v];
				if($newFilesize != $oldFilesize) {
					//---delete thumb, update filesize
					$fotoId = $arrNames[$v];
					$itemVO = new ItemVO();
					$itemVO->itemId = $fotoId;
					$itemVO->load();
					$itemVO->filesize = $newFilesize;
					$itemVO->save();
					$this->removeThumb();
					$change = true;
				}
			}
		}
		
		//---invalidate all cache places
		if($change == true) {
			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('calendarlefthand');
		}

		//---update foto count on page
		FDBTool::query("update sys_pages set cnt='".($gCountFotoNew + $gCountFoto)."',dateUpdated = now() where pageId='".$pageId."'");
		return $gCountFotoNew;
	}

	/**
	 * remove foto from db
	 * @param $id - itemId
	 * @return void
	 */
	static function removeFoto($id) {
		if(!empty($id)) {
			$galery = new FGalery();
			$galery->itemVO = new ItemVO($id, true);
			$galery->pageVO = new PageVO($galery->itemVO->pageId, true);
			
			if(!empty($galery->itemVO->thumbUrl)) if(is_file($galery->itemVO->thumbUrl)) unlink($galery->itemVO->thumbUrl);
			if(is_file(WEB_REL_GALERY . $galery->pageVO->galeryDir . '/' . $galery->itemVO->enclosure)) unlink(WEB_REL_GALERY . $galery->pageVO->galeryDir . '/' . $galery->itemVO->enclosure);
			$galery->removeThumb();

			FDBTool::query("delete from sys_pages_items_tag where itemId = '".$id."'");
			FDBTool::query("delete from sys_pages_items_hit where itemId='".$id."'");
			FDBTool::query("delete from sys_pages_items where itemId='".$id."'");
			FDBTool::query("update sys_pages set dateUpdated = now(),cnt=cnt-1 where pageId='".$galery->itemVO->pageId."'");

			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('calendarlefthand');
		}
	}
	
	/**
	 * delete temporary thumbnail
	 * 
	 * @return void
	 */
	function removeThumb() {
		$thumbPathArr = $this->getThumbPath();
		if(FGalery::isThumb(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) {
			if(!unlink(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) {
				FError::addError('Cannot delete thumb: '.ROOT.ROOT_WEB.$thumbPathArr['thumb']);
			}
		}
		//---delete system thumb
		$thumbPathArr = $this->getThumbPath( WEB_REL_CACHE_GALERY_SYSTEM );
		if(FGalery::isThumb(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) {
			@unlink(ROOT.ROOT_WEB.$thumbPathArr['thumb']);
		}
	}
	/**
	 * delete all thumbs
	 * 
	*/
	static function deleteThumbs( $pageId ) {
		$galery = new FGalery();
		$galery->pageVO = new PageVO($pageId, true);
		$cachePath = ROOT . ROOT_WEB . $galery->getThumbCachePath();
		FSystem::rm_recursive($cachePath);
		$systemCachePath = ROOT . ROOT_WEB . $galery->getThumbCachePath( WEB_REL_CACHE_GALERY_SYSTEM );
		FSystem::rm_recursive($systemCachePath);
	} 
	
}