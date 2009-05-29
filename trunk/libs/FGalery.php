<?php
class FGalery {
	//---active galery page
	var $pageVO;
	//---active item
	var $itemVO;
	//---from config file - gallery part
	var $conf;
	
	var $_fotoId = 0;
	var $_galeryId = 0;
	
	//---system settings
	var $_thumbInSysRes = false;
	
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
		$conf = FConf::getInstance();
		$this->conf = $conf->a['galery'];
		
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
				$fPage = new FPages('galery',$user->userVO->userId);
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
		
			
		
			if($pageId!='') $this->gId = $pageId;

			$xml = new SimpleXMLElement($xmlString);
			$this->gPerpage = (String) $xml->enhancedsettings[0]->perpage;
			if(empty($this->gPerpage)) $this->gPerpage = $this->conf['perpage'];

			$this->gOrderItems = 0;
			if($xml->enhancedsettings[0]->orderitems) $this->gOrderItems = (String) $xml->enhancedsettings[0]->orderitems;
			
			
			
		
			
	}

	function prepare($arr) {
		global $user;
		print_r($arr);
		die();
		//TODO: fix me
		if(isset($arr['pageParams'])) {
			$this->parseXML($arr['pageParams'],$arr['pageId']);
		}
		$this->_fGaleryId = $arr['pageId'];
		$this->_fDir = $arr['galeryDir'];
		$this->_fGaleryName = $arr['pageName'];
		$this->_fId = $arr['itemId'];
		$this->_fDetail = $arr['enclosure'];

		
		
		//---check thumbnail
		if($this->_thumbInSysRes == true) {//---system resolution thumbnail
			$thumbPathArr = $this->getThumbPath(WEB_REL_CACHE_GALERY_SYSTEM);
			if(!FGalery::isThumb($thumbPathArr['thumb'])) $this->createThumb($thumbPathArr);
			$arr['thumbUrl'] = $thumbPathArr['url'];
		} else {
			if(!empty($arr['addon'])) {
				$arr['thumbUrl'] = WEB_REL_GALERY . $arr['galeryDir'].'/'.$arr['addon'];
			} else {
				$thumbPathArr = $this->getThumbPath();
				if(!FGalery::isThumb($thumbPathArr['thumb'])) {
					$this->createThumb($thumbPathArr);
				}
				$arr['thumbUrl'] = $thumbPathArr['url'];
			}
		}
		$arr['detailUrl'] = WEB_REL_GALERY . $this->_fDir . '/' . $arr['enclosure'];

		if(file_exists($arr['detailUrl'])) {
			list($width,$height) = getimagesize($arr['detailUrl']);
			$arr['detailWidth'] = $width;
			$arr['detailHeight'] = $height;
			$arr['detailUrlToGalery'] = '?k='.$arr['pageId'].'&amp;i='.$arr['itemId'];
			$arr['detailUrlToPopup'] = '/pic.php?u='.$user->userVO->userId.'&amp;i='.$this->_fId.'&amp;width='.($width+60).'&amp;height='.($height+60);
		} else {
			FError::addError('File not exists: '.$arr['detailUrl']);
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
			$fItems = new FItems();
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
			
			
			$this->_fHits = $item['hit'];
			$this->_fComment = $item['text'];
			$this->_fDate = $item['dateLocal'];
			$this->_fSize = $item['filesize'];
			$this->_fThumbDir = $item['thumbUrl'];
		}
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
		return WEB_REL_GALERY . $this->itemVO->galeryDir . '/' . $this->itemVO->enclosure;
	}
	
	/**
	 * return array with directions to thumb
	 *
	 * @param string $cacheDir - alternative
	 * @return array [path,filename,thumb,url]
	 */
	function getThumbPath($cacheDir = '') {
		$pathUrl = $this->getThumbCachePath($cacheDir);
		$pathDir = $pathUrl;
		 
		$arrFilename = explode('.',$this->itemVO->enclosure);
		$filenameExtStriped = implode('.',array_slice($arrFilename,0,count($arrFilename)-1));
		$filename = FSystem::safeText($filenameExtStriped) . '.jpg';

		return array(
    		'path' => $pathDir,
    		'filename' => $filename,
    		'thumb' => $pathDir . '/' . $filename,
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
			if(isset($params['quality'])) $quality = $params['quality']; else $quality = $this->conf['quality'];
			if(isset($params['thumbnailstyle'])) $thumbnailstyle = (int) $params['thumbnailstyle']; else $thumbnailstyle = (int) $this->pageVO->getPageParam('enhancedsettings/thumbnailstyle');
			if(isset($params['width'])) $width = (int) $params['width']; else $width = (int) ($this->pageVO->getPageParam('enhancedsettings/widthpx') < 10)?($this->conf['widthThumb']):((String) $this->pageVO->getPageParam('enhancedsettings/widthpx'));
			if(isset($params['height'])) $height = (int) $params['height']; else $height = (int) ($this->pageVO->getPageParam('enhancedsettings/heightpx') < 10)?($this->conf['heightThumb']):((String) $this->pageVO->getPageParam('enhancedsettings/heightpx'));

			$processParams = array(
			'quality'=>$quality,'width'=>$width,'height'=>$height
			//,'reflection'=>1
			//,'unsharpMask'=>1
			);
			if($thumbnailstyle==2) $processParams['crop'] = 1; else $processParams['proportional'] = 1;
			$fProcess = new FImgProcess($sourceImgUrl,$thumbPathArr['thumb'],$processParams);
		}
	}
	function deleteThumb($path) {
		if(isThumb($thumbPathArr['thumb'])) unlink($thumbPathArr['thumb']);
	}

	function fotoHit() {
	 if(!empty($this->itemVO->itemId)){
			FDBTool::query("update sys_pages_items set hit=hit+1 where itemId=".$this->itemVO->itemId);
			FDBTool::query("insert into sys_pages_items_hit (itemId,userId,dateCreated) values (".$this->itemVO->itemId.",".FUser::logon().",now())");
			$this->itemVO->hit++;
		}
	}
	static function callbackForumProcess() {
		//---clear cache
		$cache = FCache::getInstance('f');
		$cache->invalidateGroup('lastForumPost');
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
		if(!empty($galeryId)) {
			$this->pageVO = new PageVO();
			$this->pageVO->pageId = $galeryId;
			$this->pageVO->load();
		} else {
			$galeryId = $this->pageVO->pageId;
		}
		
		$gCountFoto = 0;
		$gCountFotoNew = 0;
		
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
		$gCountFoto = count($arrFotoDetail);
		$arrFiles = array();
		$galdir = WEB_REL_GALERY . $this->pageVO->galeryDir.'/';
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
		
		$change = false;

		if(!empty($arrNotInDB)) {
			foreach ($arrNotInDB as $file) {
				$itemVO = new ItemVO();
				$itemVO->pageId = $galeryId;
				$itemVO->typeId = $this->pageVO->typeId;
				$itemVO->enclosure = $file;
				$itemVO->dateStart = 'now()'; //TODO: take from exif if availble
				$itemVO->dateCreated = 'now()';
				$itemVO->filesize = filesize($galdir.$file);
				$itemVO->text = '';
				$itemVO->hit = 0;
				$itemVO->save();
								
				$gCountFotoNew++;

				$thumbPathArr = $this->getThumbPath();
				if(!FGalery::isThumb($thumbPathArr['thumb'])) $this->createThumb($thumbPathArr);
				
				$change = true;
			}
		}


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
		
		if($change == true) {
			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('calendarlefthand');
		}

		closedir($handle);
		FDBTool::query("update sys_pages set cnt='".($gCountFotoNew + $gCountFoto)."',date_updated = now() where pageId='".$galeryId."'");
	}


	static function removeFoto($id) {
		if(!empty($id)){
			$this->itemVO = new ItemVO();
			$this->itemVO->itemId = $id;
			$this->itemVO->load();
			
			$this->pageVO = new PageVO();
			$this->pageVO->pageId = $this->itemVO->pageId;
			$this->pageVO->load(); 
			
			if(!empty($this->_fThumbDir)) if(is_file($this->_fThumbDir)) unlink($this->_fThumbDir);
			if(is_file(WEB_REL_GALERY . $this->pageVO->galeryDir . '/' . $this->itemVO->enclosure)) unlink(WEB_REL_GALERY . $this->_fDir . '/' . $this->_fDetail);
			$this->removeThumb();

			FDBTool::query("delete from sys_pages_items_tag where itemId = '".$id."'");
			FDBTool::query("delete from sys_pages_items_hit where itemId='".$id."'");
			FDBTool::query("delete from sys_pages_items where itemId='".$id."'");
			FDBTool::query("update sys_pages set date_updated = now(),cnt=cnt-1 where pageId='".$this->itemVO->pageId."'");

			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('calendarlefthand');
		}
	}

	static function removeThumb() {
		$thumbPathArr = $this->getThumbPath();
		if(FGalery::isThumb(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) {
			if(!unlink(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) {
				FError::addError('Cannot delete thumb: '.ROOT.ROOT_WEB.$thumbPathArr['thumb']);
			}
		}
		//---delete system thumb
		$thumbPathArr = $this->getThumbPath(WEB_REL_CACHE_GALERY_SYSTEM);
		if(FGalery::isThumb(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) {
			if(@unlink(ROOT.ROOT_WEB.$thumbPathArr['thumb'])) {
				//FError::addError('Cannot delete system thumb: '.ROOT.ROOT_WEB.$thumbPathArr['thumb']);
			}
		}
	}
}