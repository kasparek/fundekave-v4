<?php
/**
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
		$thumbCut = $fGalery->conf['thumbCut'];
		
		if($fGalery->itemVO->thumbInSysRes == false) {
			$thumbCut = $fGalery->pageVO->getProperty('thumbCut',$thumbCut,true);
		}

		$fGalery->itemVO->thumbUrl = $fGalery->getTargetUrl(null,$thumbCut);

		//get optional sizes list
		$sideOptionList = explode(',',FConf::get('image_conf','sideOptions'));

		//get closest lower
		$user = FUser::getInstance();

		$maxWidth = $user->userVO->clientWidth;
		if(empty($maxWidth)) $maxWidth = FConf::get('image_conf','sideDefault');
		else $maxWidth = $maxWidth - $fGalery->conf['clientSpace'];

		//get closest valid width
		foreach ($sideOptionList as $fib) {
			if($maxWidth - $fib > 0) {
				$diff[$fib] = (int) $maxWidth - $fib;
			}
		}
		$fibs = array_flip($diff);
		$sideParam = $fibs[min($diff)];

		$fGalery->itemVO->detailUrl = $fGalery->getTargetUrl(null,$sideParam.'/prop');


		$fGalery->itemVO->detailUrlToGalery = FSystem::getUri('i='.$fGalery->itemVO->itemId,$fGalery->itemVO->pageId);

		return $fGalery->itemVO;
	}

	/**
	 * get url of target
	 *
	 * @return string url
	 */
	function  getTargetUrl($root=null,$thumbCut=null) {

		if($root===null) {
			$root = 'http://' . $this->conf["ftpServer"] .'/'. $this->conf['targetUrlBase'];
		}

		if($thumbCut===null) {
			$sideSize = $this->conf['thumbCut'];
		}
		
		return $root . $thumbCut .'/'. $this->pageVO->galeryDir .'/'. (($this->itemVO)?($this->itemVO->enclosure):(''));
	}
		
	/**
	 * refresh data for galery in db by files in folder
	 * @param $pageId
	 * @return void
	 */
	function refreshImgToDb($pageId){
		FError::write_log('FGalery::refreshImgToDb '.$pageId);
		if(!empty($pageId)) {
			$this->pageVO = new PageVO($pageId,true);
		} else {
			$pageId = $this->pageVO->pageId;
		}

		$gCountFoto = 0;
		$gCountFotoNew = 0;

		$fItems = new FItems('galery',false);
		$fItems->setWhere('pageId="'.$pageId.'"');
		$fItems->addWhere('(itemIdTop is null or itemIdTop=0)');
		$itemList = $fItems->getList();
		$totalItems = count($itemList);

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
		$galdir = $this->conf['sourceServerBase'] . $this->pageVO->galeryDir.'/';
		$ffile = new FFile(FConf::get("galery","ftpServer"),FConf::get("galery","ftpUser"),FConf::get("galery","ftpPass"));
		$arrFiles = $ffile->fileList($galdir,"png|jpg|jpeg|gif");

		$change = false;

		$arrNotInDB = array_diff($arrFiles,$arrFotoDetail);
		$arrItemIdsNotOnFtp = array_keys(array_diff($arrFotoDetail,$arrFiles));

		//---remove foto no longer in folder
		$removed=0;
		if(!empty($arrItemIdsNotOnFtp)) {
			foreach ($arrItemIdsNotOnFtp as $itemId) {
				FGalery::removeFoto($itemId);
				$change = true;
				$removed++;
			}
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
					$this->flush();
					$change = true;
					$items['updated'][] = $fotoId;
				}
			}
		}

		//---invalidate all cache places
		if($change == true) {
			//TODO:send notification to observer
			//FCommand::run('itemChanged');
			//$cache = FCache::getInstance('f');
			//$cache->invalidateGroup('calendarlefthand');
		}

		//---update foto count on page
		$totalFoto = $gCountFotoNew + $gCountFoto;
		FDBTool::query("update sys_pages set cnt='".$totalFoto."',dateUpdated = now() where pageId='".$pageId."'");

		$items['total'] = $totalFoto;
		FError::write_log('FGalery::refreshImgToDb COMPLETE '.$pageId.' inserted:'.(isset($items['new']) ? count($items['new']) : 0).' updated:'.( isset($items['updated']) ? count($items['updated']) : 0).' removed: '.$removed);
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
				
			$galery->flush();
			
			$file = new FFile();	
			if($file->is_file($galery->conf['sourceServerBase'] . $galery->pageVO->galeryDir . '/' . $galery->itemVO->enclosure)) { 
				$file->unlink($galery->conf['sourceServerBase'] . $galery->pageVO->galeryDir . '/' . $galery->itemVO->enclosure);
			}
				
			FDBTool::query("delete from sys_pages_items where itemId='".$id."'");
			FDBTool::query("update sys_pages set dateUpdated = now(),cnt=cnt-1 where pageId='".$galery->itemVO->pageId."'");
				
			$galery->itemVO->delete();

			//TODO:---notify observer item deleted do additional action, clearing cache atd;
			//FCommand::run('itemDeleted');
			//$cache = FCache::getInstance('f');
			//$cache->invalidateGroup('calendarlefthand');
				
			return true;
		}
	}

	/**
	 * delete all cached images
	 *
	 */
	function flush( $resolution=0 ) {
		if(!is_array($resolution)) $resolution = array($resolution);
		foreach($resolution as $side) {
			$url = $this->getTargetUrl(null,$side,'flush');
			//request url to do action
			file_get_contents( $url );
		}
	}

}