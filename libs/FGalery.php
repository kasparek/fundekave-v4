<?php
/**
 *TODO:
 *remove ROOT_GALERY_CACHE
 *remove ROOT_GALERY_CACHE_SYSTEM
 *replace ROOT_GALERY with conf->sourceUrlBase
 *add conf->targetUrlBase   
 *
 **/   
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
			$fGalery->itemVO->thumbWidth = $fGalery->conf['widthThumb'];
			$fGalery->itemVO->heightWidth = $fGalery->conf['heightThumb'];
			$thumbnailstyle = 2;
		} else {
			$thumbnailstyle = (int) $this->pageVO->getPageParam('enhancedsettings/thumbnailstyle');
			if($thumbnailstyle===false) $thumbnailstyle = 2;
			
			$fGalery->itemVO->thumbWidth = (String) $fGalery->pageVO->getPageParam('enhancedsettings/widthpx');
			$fGalery->itemVO->thumbHeight = (String) $fGalery->pageVO->getPageParam('enhancedsettings/heightpx');
			if(empty($fGalery->itemVO->thumbWidth)) $fGalery->itemVO->thumbWidth = $fGalery->conf['widthThumb'];
			if(empty($fGalery->itemVO->thumbHeight)) $fGalery->itemVO->thumbHeight = $fGalery->conf['heightThumb'];
		}

		//TODO: get client screen size
		$fGalery->itemVO->thumbUrl = $this->getTargetUrl(null,$fGalery->itemVO->thumbWidth.'x'.$fGalery->itemVO->thumbHeight,$thumbnailstyle==2?'crop':'prop');
		$fGalery->itemVO->detailUrl = $this->getTargetUrl(null,800,'prop');
		
		$fGalery->itemVO->detailUrlToGalery = FSystem::getUri('i='.$fGalery->itemVO->itemId,$fGalery->itemVO->pageId);
		$fGalery->itemVO->detailUrlToPopup = FSystem::getUri('i='.$fGalery->itemVO->itemId.'&width='.($width+60).'&height='.($height+60).'&u='.FUser::logon(),'','','pic.php');
		
		return $fGalery->itemVO;
	}
		
	/**
	 * get url of target
	 *
	 * @return string url
	 */
	function  getTargetUrl($root=null,$sideSize=null,$cutOption=null) {
		
		if(!$root) $root = $this->conf->targetUrlBase;
		
		if(!$sideSize) {
			$sideSize = $this->conf->sizeDefault;
		}
		if(!$cutOption) $cutOption = 'prop'; //---proportional resize
		
		return $root . $sideSize .'/'. $cutOption .'/'. $this->pageVO->galeryDir .'/'. (($this->itemVO)?($this->itemVO->enclosure):(''));
		
	}
			
	/**
	 * callback function when processing forum attached to gallery
	 * @return void
	 */
	//TODO:observer on invalidate from forum invalidate grup
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
	//TODO:not needed
	/*
	static function getRaw($itemId) {
		$galery = new FGalery();
		$galery->itemVO = new ItemVO($itemId, true, array('type'=>'galery'));
		$galery->pageVO = new PageVO($galery->itemVO->pageId,true);
		$galery->itemVO->hit();
		return file_get_contents( $galery->getDetailUrl(ROOT_GALERY) );
	}
*/
	
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
		$galdir = ROOT_GALERY . $this->pageVO->galeryDir.'/';
		$arrFiles = FFile::fileList($galdir,"png|jpg|jpeg|gif");
		
		$change = false;
		
		$arrNotInDB = array_diff($arrFiles,$arrFotoDetail);
		$arrItemIdsNotOnFtp = array_keys(array_diff($arrFotoDetail,$arrFiles));

		//---remove foto no longer in folder
		if(!empty($arrItemIdsNotOnFtp)) foreach ($arrItemIdsNotOnFtp as $itemId) {
			FGalery::removeFoto($itemId);
			$change = true;
		}
		
		$items = array();
		
		//---insert new foto to db
		if(!empty($arrNotInDB)) {
			foreach ($arrNotInDB as $file) {
				$this->itemVO = new ItemVO();
				$this->itemVO->pageId = $pageId;
				$this->itemVO->typeId = $this->pageVO->typeId;
				$this->itemVO->enclosure = $file;
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
				$this->itemVO->filesize = filesize($galdir.$file);
				$this->itemVO->text = '';
				$this->itemVO->hit = 0;
				$this->itemVO->dateStart = $this->pageVO->dateContent; 
				$this->itemVO->save();
				$gCountFotoNew++;
				$thumbPathArr = $this->getThumbPath(ROOT_GALERY_CACHE);
				if(!FGalery::isThumb($thumbPathArr['thumb'])) $this->createThumb($thumbPathArr);
				$items['new'][] = $this->itemVO->itemId;
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
					$this->itemVO = new ItemVO($fotoId,true,array('type'=>'ignore'));
					$this->itemVO->filesize = $newFilesize;
					$this->itemVO->save();
					$this->removeThumb();
					$change = true;
					$items['updated'][] = $fotoId; 
				}
			}
		}
		
		//---invalidate all cache places
		if($change == true) {
			//TODO:send notification to observer
			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('calendarlefthand');
		}

		//---update foto count on page
		$totalFoto = $gCountFotoNew + $gCountFoto;
		FDBTool::query("update sys_pages set cnt='".$totalFoto."',dateUpdated = now() where pageId='".$pageId."'");
		
		$items['total'] = $totalFoto;
		return $items;
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
			
			if(is_file(ROOT_GALERY . $galery->pageVO->galeryDir . '/' . $galery->itemVO->enclosure)) @unlink(ROOT_GALERY . $galery->pageVO->galeryDir . '/' . $galery->itemVO->enclosure);
			
			$url = $galery->getTargetUrl(null,0,'flush');
			//request url to do action
			file_get_contents( $url );

			FDBTool::query("delete from sys_pages_items where itemId='".$id."'");
			FDBTool::query("update sys_pages set dateUpdated = now(),cnt=cnt-1 where pageId='".$galery->itemVO->pageId."'");
			
			$galery->itemVO->delete();

			//TODO:---notify observer item deleted do additional action, clearing cache atd;
			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('calendarlefthand');
			return true;
		}
	}
	
	/**
	 * delete temporary thumbnail
	 * 
	 * @return void
	 */
	function removeThumb() {
		//thumbs size
		//TODO:suply size to flush not all
		$url = $galery->getTargetUrl(null,0,'flush');
		//request url to do action
		file_get_contents( $url );
		
		//system thumbs size
		//TODO:suply size to flush not all
		$url = $galery->getTargetUrl(null,0,'flush');
		//request url to do action
		file_get_contents( $url );
	}
	
	/**
	 * delete all thumbs
	 * 
	*/
	static function deleteThumbs( $pageId ) {
		$galery = new FGalery();
		$galery->pageVO = new PageVO($pageId, true);
		$url = $galery->getTargetUrl(null,0,'flush');
		//request url to do action
		file_get_contents( $url );
	} 
	
}